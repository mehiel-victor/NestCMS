<?php

declare(strict_types=1);

namespace App\Services;

use App\AuthException;
use App\ForbiddenException;
use App\Repositories\AuthAuditRepository;
use App\Repositories\AuthRateLimitRepository;
use App\Repositories\AuthSessionRepository;
use App\Repositories\InviteeRepository;
use App\Repositories\MagicTokenRepository;

use InvalidArgumentException;

final class AuthService
{
    private const ALLOWED_ROLES = ['admin', 'operator', 'finance'];

    public function __construct(
        private readonly InviteeRepository $invitees,
        private readonly MagicTokenRepository $magicTokens,
        private readonly AuthSessionRepository $sessions,
        private readonly AuthRateLimitRepository $rateLimits,
        private readonly AuthAuditRepository $audit,
    ) {
    }

    public function requestMagicLink(string $email, string $ipAddress, string $userAgent, string $path): array
    {
        $normalizedEmail = $this->normalizeEmail($email);

        $window = max(1, $this->envInt('AUTH_RATE_LIMIT_WINDOW_SECONDS', 60));
        $maxIp = max(1, $this->envInt('AUTH_RATE_LIMIT_MAX_PER_IP', 6));
        $maxEmail = max(1, $this->envInt('AUTH_RATE_LIMIT_MAX_PER_EMAIL', 4));

        $ipAllowed = $this->rateLimits->isAllowed("magic_request:ip:{$ipAddress}", $window, $maxIp);
        $emailAllowed = true;
        if ($normalizedEmail !== '') {
            $emailAllowed = $this->rateLimits->isAllowed("magic_request:email:{$normalizedEmail}", $window, $maxEmail);
        }

        if (!$ipAllowed || !$emailAllowed) {
            $this->audit->record([
                'event_type' => 'request_magic_link',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'POST',
                'details' => ['reason' => 'rate_limit', 'email' => $normalizedEmail],
            ]);

            return [
                'message' => 'Se o e-mail estiver cadastrado, enviamos um link de acesso.',
            ];
        }

        $invitee = $normalizedEmail === '' ? null : $this->invitees->findActiveByEmail($normalizedEmail);
        $response = ['message' => 'Se o e-mail estiver cadastrado, enviamos um link de acesso.'];

        if ($invitee !== null) {
            $magicToken = bin2hex(random_bytes(32));
            $magicTokenHash = $this->hashToken($magicToken);
            $expiresAt = $this->dateFromMinutes($this->envInt('AUTH_MAGIC_LINK_TTL_MINUTES', 10));

            $this->magicTokens->create(
                (int) $invitee['id'],
                $magicTokenHash,
                $expiresAt,
                $ipAddress,
                $userAgent
            );

            $frontendBase = rtrim($this->envString('AUTH_MAGIC_LINK_BASE_URL', 'http://localhost:3000'), '/');
            $callbackUrl = $frontendBase . '/auth/callback?token=' . urlencode($magicToken);

            error_log('[NestCMS] Magic link generated for ' . $normalizedEmail . ': ' . $callbackUrl);

            $this->audit->record([
                'invitee_id' => $invitee['id'],
                'event_type' => 'request_magic_link',
                'outcome' => 'allowed',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'POST',
                'details' => ['invitee_id' => $invitee['id'], 'role' => $invitee['role']],
            ]);

            if ($this->envBool('MAGIC_LINK_DEBUG_RETURN_TOKEN', false)) {
                $response['debug_token'] = $magicToken;
                $response['debug_callback_url'] = $callbackUrl;
            }
        } else {
            $this->audit->record([
                'event_type' => 'request_magic_link',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'POST',
                'details' => ['reason' => 'unknown_or_inactive_email'],
            ]);
        }

        return $response;
    }

    public function consumeMagicLink(string $token, string $ipAddress, string $userAgent, string $path): array
    {
        $rawToken = trim($token);
        if ($rawToken === '') {
            throw new AuthException('Invalid or expired magic token.', 401);
        }

        $tokenHash = $this->hashToken($rawToken);
        $magicToken = $this->magicTokens->findByHash($tokenHash);

        if ($magicToken === null) {
            $this->audit->record([
                'event_type' => 'consume_magic',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'GET',
                'details' => ['reason' => 'invalid_magic_token'],
            ]);
            throw new AuthException('Invalid or expired magic token.', 401);
        }

        if (($magicToken['used_at'] ?? null) !== null) {
            $this->audit->record([
                'invitee_id' => (int) $magicToken['invitee_id'],
                'event_type' => 'consume_magic',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'GET',
                'details' => ['reason' => 'magic_token_already_used'],
            ]);
            throw new AuthException('Magic token already used.', 401);
        }

        if (($magicToken['revoked_at'] ?? null) !== null) {
            $this->audit->record([
                'invitee_id' => (int) $magicToken['invitee_id'],
                'event_type' => 'consume_magic',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'GET',
                'details' => ['reason' => 'magic_token_revoked'],
            ]);
            throw new AuthException('Magic token revoked.', 401);
        }

        if (($magicToken['status'] ?? '') !== 'active') {
            $this->audit->record([
                'event_type' => 'consume_magic',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'GET',
                'details' => ['reason' => 'invitee_not_active'],
            ]);
            throw new AuthException('Invited user not active.', 403);
        }

        if ($this->isExpired((string) $magicToken['expires_at'])) {
            $this->audit->record([
                'invitee_id' => (int) $magicToken['invitee_id'],
                'event_type' => 'consume_magic',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'GET',
                'details' => ['reason' => 'magic_token_expired'],
            ]);
            throw new AuthException('Magic token expired.', 401);
        }

        $session = $this->createSession(
            (int) $magicToken['invitee_id'],
            (string) $magicToken['role'],
            $ipAddress,
            $userAgent,
            $path
        );
        $this->magicTokens->markUsed((int) $magicToken['id']);

        $this->audit->record([
            'invitee_id' => (int) $magicToken['invitee_id'],
            'session_id' => (int) $session['id'],
            'event_type' => 'consume_magic',
            'outcome' => 'allowed',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'request_path' => $path,
            'request_method' => 'GET',
            'details' => ['session_id' => $session['id'], 'role' => $magicToken['role']],
        ]);

        return [
            'access_token' => $session['access_token_raw'],
            'refresh_token' => $session['refresh_token_raw'],
            'user' => [
                'id' => (int) $magicToken['invitee_id'],
                'email' => (string) $magicToken['email'],
                'role' => (string) $magicToken['role'],
            ],
            'access_expires_at' => (string) $session['access_expires_at'],
            'refresh_expires_at' => (string) $session['refresh_expires_at'],
            'session_id' => (string) $session['session_id'],
        ];
    }

    public function me(string $accessToken, string $ipAddress, string $userAgent, string $path): array
    {
        $session = $this->getSessionByAccessToken($accessToken, $ipAddress, $userAgent, $path, true, 'GET');

        return [
            'user' => [
                'id' => (int) $session['invitee_id'],
                'email' => (string) $session['email'],
                'role' => (string) $session['role'],
            ],
            'session' => [
                'id' => (int) $session['id'],
                'session_id' => (string) $session['session_id'],
                'access_expires_at' => (string) $session['access_expires_at'],
                'refresh_expires_at' => (string) $session['refresh_expires_at'],
            ],
        ];
    }

    public function refresh(string $refreshToken, string $ipAddress, string $userAgent, string $path): array
    {
        $token = trim($refreshToken);
        if ($token === '') {
            throw new AuthException('Missing refresh token.', 401);
        }

        $tokenHash = $this->hashToken($token);
        $session = $this->sessions->findByRefreshTokenHash($tokenHash);

        if ($session === null) {
            $this->audit->record([
                'event_type' => 'refresh',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'POST',
                'details' => ['reason' => 'invalid_refresh_token'],
            ]);

            throw new AuthException('Invalid refresh token.', 401);
        }

        if ((new \DateTimeImmutable((string) $session['refresh_expires_at'])) <= new \DateTimeImmutable()) {
            $this->revokeSession((int) $session['id'], 'refresh_expired', $path, 'POST', $ipAddress, $userAgent);
            throw new AuthException('Session expired.', 401);
        }

        $rotateRefresh = $this->isRefreshRotationEnabled();
        $newAccessToken = $this->issueToken(32);
        $newAccessHash = $this->hashToken($newAccessToken);
        $newRefreshToken = $rotateRefresh ? $this->issueToken(48) : $token;
        $newRefreshHash = $rotateRefresh ? $this->hashToken($newRefreshToken) : $tokenHash;

        $accessExpiresAt = $this->dateFromMinutes($this->envInt('AUTH_ACCESS_TOKEN_TTL_MINUTES', 15));
        $refreshExpiresAt = $this->dateFromDays($this->envInt('AUTH_REFRESH_TOKEN_TTL_DAYS', 7));

        $rotated = $this->sessions->rotateRefreshToken(
            (int) $session['id'],
            $tokenHash,
            $newAccessHash,
            $newRefreshHash,
            $accessExpiresAt,
            $refreshExpiresAt,
            $ipAddress,
            $userAgent
        );

        if ($rotated === null) {
            $this->audit->record([
                'invitee_id' => (int) $session['invitee_id'],
                'session_id' => (int) $session['id'],
                'event_type' => 'refresh',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'POST',
                'details' => ['reason' => 'token_revoked_or_already_rotated'],
            ]);

            throw new AuthException('Refresh token expired or already used.', 401);
        }

        $this->audit->record([
            'invitee_id' => (int) $session['invitee_id'],
            'session_id' => (int) $session['id'],
            'event_type' => 'refresh',
            'outcome' => 'allowed',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'request_path' => $path,
            'request_method' => 'POST',
            'details' => [
                'session_id' => $session['id'],
                'refresh_rotation_enabled' => $rotateRefresh,
            ],
        ]);

        return [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'user' => [
                'id' => (int) $session['invitee_id'],
                'email' => (string) $session['email'],
                'role' => (string) $session['role'],
            ],
            'access_expires_at' => (string) $rotated['access_expires_at'],
            'refresh_expires_at' => (string) $rotated['refresh_expires_at'],
            'session_id' => (string) $rotated['session_id'],
        ];
    }

    public function logout(?string $accessToken, ?string $refreshToken, string $ipAddress, string $userAgent, string $path): bool
    {
        $session = null;

        if (trim((string) $accessToken) !== '') {
            $session = $this->sessions->findByAccessTokenHash($this->hashToken(trim((string) $accessToken)));
        } elseif (trim((string) $refreshToken) !== '') {
            $session = $this->sessions->findByRefreshTokenHash($this->hashToken(trim((string) $refreshToken)));
        }

        if ($session === null) {
            $this->audit->record([
                'event_type' => 'logout',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => 'POST',
                'details' => ['reason' => 'session_not_found'],
            ]);

            throw new AuthException('Session not found.', 401);
        }

        $this->revokeSession((int) $session['id'], 'logout', $path, 'POST', $ipAddress, $userAgent, (int) $session['invitee_id']);

        return true;
    }

    public function requireRole(array $session, array $allowedRoles, string $ipAddress = '', string $userAgent = '', string $path = '', string $method = 'GET'): void
    {
        if ($allowedRoles === []) {
            return;
        }

        $role = (string) ($session['role'] ?? '');
        if (!in_array($role, $allowedRoles, true)) {
            $this->audit->record([
                'invitee_id' => (int) ($session['invitee_id'] ?? 0),
                'session_id' => (int) ($session['id'] ?? 0),
                'event_type' => 'require_role',
                'outcome' => 'denied',
                'ip_address' => $ipAddress === '' ? null : $ipAddress,
                'user_agent' => $userAgent === '' ? null : $userAgent,
                'request_path' => $path === '' ? null : $path,
                'request_method' => strtoupper($method),
                'details' => ['required_roles' => $allowedRoles, 'actual_role' => $role],
            ]);
            throw new ForbiddenException('Access forbidden for this role.');
        }
    }

    public function getSessionByAccessToken(string $accessToken, string $ipAddress, string $userAgent, string $path, bool $touch = true, string $method = 'GET'): array
    {
        $rawToken = trim($accessToken);
        if ($rawToken === '') {
            throw new AuthException('Missing access token.', 401);
        }

        $tokenHash = $this->hashToken($rawToken);
        $session = $this->sessions->findByAccessTokenHash($tokenHash);
        if ($session === null) {
            $this->audit->record([
                'event_type' => 'token_access',
                'outcome' => 'denied',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'request_path' => $path,
                'request_method' => strtoupper($method),
                'details' => ['reason' => 'invalid_access_token'],
            ]);

            throw new AuthException('Invalid or missing access token.', 401);
        }

        if ((new \DateTimeImmutable((string) $session['access_expires_at'])) <= new \DateTimeImmutable()) {
            $this->revokeSession((int) $session['id'], 'access_expired', $path, strtoupper($method), $ipAddress, $userAgent, (int) $session['invitee_id']);
            throw new AuthException('Access token expired.', 401);
        }

        if ($touch) {
            $this->sessions->updateLastAccess((int) $session['id'], $ipAddress, $userAgent);
        }

        return $session;
    }

    private function createSession(int $inviteeId, string $role, string $ipAddress, string $userAgent, string $path): array
    {
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException('Invalid role.');
        }

        $accessToken = $this->issueToken(32);
        $refreshToken = $this->issueToken(48);
        $accessTokenHash = $this->hashToken($accessToken);
        $refreshTokenHash = $this->hashToken($refreshToken);

        $session = $this->sessions->create([
            'invitee_id' => $inviteeId,
            'session_id' => $this->issueToken(24),
            'access_token_hash' => $accessTokenHash,
            'refresh_token_hash' => $refreshTokenHash,
            'role' => $role,
            'access_expires_at' => $this->dateFromMinutes($this->envInt('AUTH_ACCESS_TOKEN_TTL_MINUTES', 15)),
            'refresh_expires_at' => $this->dateFromDays($this->envInt('AUTH_REFRESH_TOKEN_TTL_DAYS', 7)),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        $session['access_token_raw'] = $accessToken;
        $session['refresh_token_raw'] = $refreshToken;

        $this->audit->record([
            'invitee_id' => $inviteeId,
            'session_id' => (int) $session['id'],
            'event_type' => 'session_issue',
            'outcome' => 'allowed',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'request_path' => $path,
            'request_method' => 'GET',
            'details' => ['session_id' => $session['session_id']],
        ]);

        return $session;
    }

    private function revokeSession(int $sessionId, string $reason, string $path, string $method, string $ipAddress, string $userAgent, ?int $inviteeId = null): void
    {
        $this->sessions->revokeById($sessionId, $reason);
        $this->audit->record([
            'invitee_id' => $inviteeId,
            'session_id' => $sessionId,
            'event_type' => 'revoke_session',
            'outcome' => 'revoked',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'request_path' => $path,
            'request_method' => $method,
            'details' => ['reason' => $reason],
        ]);
    }

    private function hashToken(string $token): string
    {
        $salt = $this->envString('AUTH_TOKEN_HASH_SALT', 'nestcms-auth');
        return hash('sha256', $salt . $token);
    }

    private function normalizeEmail(string $email): string
    {
        $normalized = strtolower(trim($email));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        return $normalized;
    }

    private function issueToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    private function dateFromMinutes(int $minutes): string
    {
        return (new \DateTimeImmutable())->add(new \DateInterval('PT' . max(1, $minutes) . 'M'))->format(DATE_ATOM);
    }

    private function dateFromDays(int $days): string
    {
        return (new \DateTimeImmutable())->add(new \DateInterval('P' . max(1, $days) . 'D'))->format(DATE_ATOM);
    }

    private function isExpired(string $dateTime): bool
    {
        try {
            return (new \DateTimeImmutable($dateTime)) <= new \DateTimeImmutable();
        } catch (\Throwable) {
            return true;
        }
    }

    private function isRefreshRotationEnabled(): bool
    {
        return $this->envBool('AUTH_REFRESH_ROTATION_ENABLED', true);
    }

    private function envString(string $key, string $default): string
    {
        $value = $_ENV[$key] ?? getenv($key);
        return $value === false || $value === null || $value === '' ? $default : (string) $value;
    }

    private function envInt(string $key, int $default): int
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        $parsed = (int) $value;
        return $parsed > 0 ? $parsed : $default;
    }

    private function envBool(string $key, bool $default): bool
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}
