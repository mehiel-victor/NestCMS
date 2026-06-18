<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MagicTokenRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $inviteeId, string $tokenHash, string $expiresAt, string $ipAddress, string $userAgent): void
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO auth_magic_tokens (
                invitee_id,
                token_hash,
                expires_at,
                ip_address,
                user_agent
            )
            VALUES (:invitee_id, :token_hash, :expires_at::timestamptz, :ip_address, :user_agent)
            SQL
        );

        $statement->execute([
            'invitee_id' => $inviteeId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'ip_address' => $ipAddress !== '' ? $ipAddress : null,
            'user_agent' => $userAgent !== '' ? $userAgent : null,
        ]);
    }

    public function findValidByHash(string $tokenHash): ?array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT
                mt.id,
                mt.invitee_id,
                mt.token_hash,
                mt.expires_at,
                mt.used_at,
                mt.revoked_at,
                mt.ip_address,
                mt.user_agent,
                i.email,
                i.role,
                i.status
            FROM auth_magic_tokens mt
            INNER JOIN auth_invitees i ON i.id = mt.invitee_id
            WHERE mt.token_hash = :token_hash
              AND mt.used_at IS NULL
              AND mt.revoked_at IS NULL
              AND mt.expires_at > now()
              AND i.status = 'active'
            LIMIT 1
            SQL
        );

        $statement->execute(['token_hash' => $tokenHash]);
        $row = $statement->fetch();

        return $row === false ? null : $this->cast($row);
    }

    public function findByHash(string $tokenHash): ?array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT
                mt.id,
                mt.invitee_id,
                mt.token_hash,
                mt.expires_at,
                mt.used_at,
                mt.revoked_at,
                mt.ip_address,
                mt.user_agent,
                i.email,
                i.role,
                i.status
            FROM auth_magic_tokens mt
            INNER JOIN auth_invitees i ON i.id = mt.invitee_id
            WHERE mt.token_hash = :token_hash
            LIMIT 1
            SQL
        );

        $statement->execute(['token_hash' => $tokenHash]);
        $row = $statement->fetch();

        return $row === false ? null : $this->cast($row);
    }

    public function markUsed(int $tokenId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE auth_magic_tokens SET used_at = now() WHERE id = :id'
        );
        $statement->execute(['id' => $tokenId]);
    }

    private function cast(array $token): array
    {
        $token['id'] = (int) $token['id'];
        $token['invitee_id'] = (int) $token['invitee_id'];

        return $token;
    }
}
