<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AuthAuditRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(array $payload): void
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO auth_audit_events (
                invitee_id,
                session_id,
                event_type,
                outcome,
                ip_address,
                user_agent,
                request_path,
                request_method,
                details
            )
            VALUES (
                :invitee_id,
                :session_id,
                :event_type,
                :outcome,
                :ip_address,
                :user_agent,
                :request_path,
                :request_method,
                CAST(:details AS jsonb)
            )
            SQL
        );

        $statement->execute([
            'invitee_id' => $payload['invitee_id'] ?? null,
            'session_id' => $payload['session_id'] ?? null,
            'event_type' => $payload['event_type'],
            'outcome' => $payload['outcome'],
            'ip_address' => $payload['ip_address'] ?? null,
            'user_agent' => $payload['user_agent'] ?? null,
            'request_path' => $payload['request_path'] ?? null,
            'request_method' => $payload['request_method'] ?? null,
            'details' => json_encode($payload['details'] ?? [], JSON_THROW_ON_ERROR),
        ]);
    }
}

