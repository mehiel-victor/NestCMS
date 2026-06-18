<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PaymentEventRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function hasProviderEvent(string $provider, string $providerEventId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM payment_events WHERE provider = :provider AND provider_event_id = :provider_event_id LIMIT 1'
        );
        $statement->execute([
            'provider' => $provider,
            'provider_event_id' => $providerEventId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function create(int $transactionId, string $provider, array $payload): int
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO payment_events (
                transaction_id, provider_event_id, provider, event_type, provider_status, payload_hash, webhook_payload
            )
            VALUES (
                :transaction_id, :provider_event_id, :provider, :event_type, :provider_status, :payload_hash, CAST(:webhook_payload AS jsonb)
            )
            RETURNING id
            SQL
        );
        $statement->execute([
            'transaction_id' => $transactionId,
            'provider_event_id' => $payload['provider_event_id'],
            'provider' => $provider,
            'event_type' => $payload['event_type'],
            'provider_status' => $payload['provider_status'] ?? null,
            'payload_hash' => hash('sha256', json_encode($payload['event_payload'] ?? [], JSON_THROW_ON_ERROR)),
            'webhook_payload' => json_encode($payload['event_payload'] ?? [], JSON_THROW_ON_ERROR),
        ]);

        return (int) $statement->fetchColumn();
    }
}
