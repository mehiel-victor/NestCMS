<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class InviteeRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findActiveByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT id, email, role, status, created_at, updated_at
            FROM auth_invitees
            WHERE lower(email) = :email
              AND status = 'active'
            LIMIT 1
            SQL
        );

        $statement->execute(['email' => strtolower($email)]);
        $invitee = $statement->fetch();

        return $invitee === false ? null : $this->cast($invitee);
    }

    private function cast(array $invitee): array
    {
        $invitee['id'] = (int) $invitee['id'];

        return $invitee;
    }
}

