<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AuthSessionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $payload): array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO auth_sessions (
                invitee_id,
                session_id,
                access_token_hash,
                refresh_token_hash,
                role,
                access_expires_at,
                refresh_expires_at,
                created_ip,
                created_user_agent,
                last_ip,
                last_user_agent
            )
            VALUES (
                :invitee_id,
                :session_id,
                :access_token_hash,
                :refresh_token_hash,
                :role,
                :access_expires_at::timestamptz,
                :refresh_expires_at::timestamptz,
                :created_ip,
                :created_user_agent,
                :last_ip,
                :last_user_agent
            )
            RETURNING *
            SQL
        );

        $statement->execute([
            'invitee_id' => $payload['invitee_id'],
            'session_id' => $payload['session_id'],
            'access_token_hash' => $payload['access_token_hash'],
            'refresh_token_hash' => $payload['refresh_token_hash'],
            'role' => $payload['role'],
            'access_expires_at' => $payload['access_expires_at'],
            'refresh_expires_at' => $payload['refresh_expires_at'],
            'created_ip' => $payload['ip_address'] !== '' ? $payload['ip_address'] : null,
            'created_user_agent' => $payload['user_agent'] !== '' ? $payload['user_agent'] : null,
            'last_ip' => $payload['ip_address'] !== '' ? $payload['ip_address'] : null,
            'last_user_agent' => $payload['user_agent'] !== '' ? $payload['user_agent'] : null,
        ]);

        return $this->cast($statement->fetch());
    }

    public function findByAccessTokenHash(string $accessTokenHash): ?array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT
                s.id,
                s.invitee_id,
                s.session_id,
                s.access_token_hash,
                s.refresh_token_hash,
                s.role,
                s.access_expires_at,
                s.refresh_expires_at,
                s.revoked_at,
                s.revoked_reason,
                s.last_seen_at,
                s.created_at,
                s.created_ip,
                s.created_user_agent,
                s.last_ip,
                s.last_user_agent,
                i.email
            FROM auth_sessions s
            INNER JOIN auth_invitees i ON i.id = s.invitee_id
            WHERE s.access_token_hash = :hash
              AND s.revoked_at IS NULL
            LIMIT 1
            SQL
        );

        $statement->execute(['hash' => $accessTokenHash]);
        $session = $statement->fetch();

        return $session === false ? null : $this->cast($session);
    }

    public function findByRefreshTokenHash(string $refreshTokenHash): ?array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT
                s.id,
                s.invitee_id,
                s.session_id,
                s.access_token_hash,
                s.refresh_token_hash,
                s.role,
                s.access_expires_at,
                s.refresh_expires_at,
                s.revoked_at,
                s.revoked_reason,
                s.last_seen_at,
                s.created_at,
                s.created_ip,
                s.created_user_agent,
                s.last_ip,
                s.last_user_agent,
                i.email
            FROM auth_sessions s
            INNER JOIN auth_invitees i ON i.id = s.invitee_id
            WHERE s.refresh_token_hash = :hash
              AND s.revoked_at IS NULL
            LIMIT 1
            SQL
        );

        $statement->execute(['hash' => $refreshTokenHash]);
        $session = $statement->fetch();

        return $session === false ? null : $this->cast($session);
    }

    public function updateLastAccess(int $sessionId, string $ipAddress, string $userAgent): void
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            UPDATE auth_sessions
            SET last_seen_at = now(),
                last_ip = :last_ip,
                last_user_agent = :last_user_agent
            WHERE id = :id
            SQL
        );

        $statement->execute([
            'id' => $sessionId,
            'last_ip' => $ipAddress !== '' ? $ipAddress : null,
            'last_user_agent' => $userAgent !== '' ? $userAgent : null,
        ]);
    }

    public function rotateRefreshToken(int $sessionId, string $oldRefreshTokenHash, string $newAccessTokenHash, string $newRefreshTokenHash, string $accessExpiresAt, string $refreshExpiresAt, string $ipAddress, string $userAgent): ?array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            UPDATE auth_sessions
            SET access_token_hash = :new_access_token_hash,
                refresh_token_hash = :new_refresh_token_hash,
                access_expires_at = :access_expires_at::timestamptz,
                refresh_expires_at = :refresh_expires_at::timestamptz,
                last_seen_at = now(),
                last_ip = :last_ip,
                last_user_agent = :last_user_agent
            WHERE id = :session_id
              AND refresh_token_hash = :current_refresh_token_hash
              AND revoked_at IS NULL
            RETURNING *
            SQL
        );

        $statement->execute([
            'session_id' => $sessionId,
            'current_refresh_token_hash' => $oldRefreshTokenHash,
            'new_access_token_hash' => $newAccessTokenHash,
            'new_refresh_token_hash' => $newRefreshTokenHash,
            'access_expires_at' => $accessExpiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
            'last_ip' => $ipAddress !== '' ? $ipAddress : null,
            'last_user_agent' => $userAgent !== '' ? $userAgent : null,
        ]);

        $session = $statement->fetch();
        return $session === false ? null : $this->cast($session);
    }

    public function revokeById(int $sessionId, string $reason): void
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            UPDATE auth_sessions
            SET revoked_at = now(),
                revoked_reason = :reason
            WHERE id = :id
              AND revoked_at IS NULL
            SQL
        );

        $statement->execute([
            'id' => $sessionId,
            'reason' => $reason,
        ]);
    }

    private function cast(array $session): array
    {
        $session['id'] = (int) $session['id'];
        $session['invitee_id'] = (int) $session['invitee_id'];

        return $session;
    }
}

