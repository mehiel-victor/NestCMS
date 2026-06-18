<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PaymentTransactionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT
                pt.*,
                pik.result_payload
            FROM payment_idempotency_keys pik
            INNER JOIN payment_transactions pt ON pt.id = pik.transaction_id
            WHERE pik.idempotency_key = :idempotency_key
            ORDER BY pik.created_at DESC
            LIMIT 1
            SQL
        );
        $statement->execute(['idempotency_key' => $idempotencyKey]);
        $transaction = $statement->fetch();

        return $transaction === false ? null : $this->castTransaction($transaction);
    }

    public function findByProviderTransactionId(string $providerTransactionId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM payment_transactions WHERE provider_transaction_id = :provider_transaction_id LIMIT 1');
        $statement->execute(['provider_transaction_id' => $providerTransactionId]);
        $transaction = $statement->fetch();

        return $transaction === false ? null : $this->castTransaction($transaction);
    }

    public function findByOrderId(int $orderId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM payment_transactions WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1'
        );
        $statement->execute(['order_id' => $orderId]);
        $transaction = $statement->fetch();

        return $transaction === false ? null : $this->castTransaction($transaction);
    }

    public function create(array $payload): array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO payment_transactions (
                order_id, provider, provider_transaction_id, idempotency_key, payment_method,
                amount, currency, provider_status, payment_status, last_error
            )
            VALUES (
                :order_id, :provider, :provider_transaction_id, :idempotency_key, :payment_method,
                :amount, :currency, :provider_status, :payment_status, :last_error
            )
            RETURNING *
            SQL
        );
        $statement->execute([
            'order_id' => $payload['order_id'],
            'provider' => $payload['provider'],
            'provider_transaction_id' => $payload['provider_transaction_id'],
            'idempotency_key' => $payload['idempotency_key'],
            'payment_method' => $payload['payment_method'],
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'provider_status' => $payload['provider_status'],
            'payment_status' => $payload['payment_status'],
            'last_error' => $payload['last_error'] ?? null,
        ]);

        $transaction = $statement->fetch();
        if (!$transaction) {
            throw new \RuntimeException('Failed to create payment transaction.');
        }

        $this->recordIdempotency(
            (int) $transaction['id'],
            (int) $transaction['order_id'],
            $payload['idempotency_key'],
            [
            'provider' => $payload['provider'],
            'provider_transaction_id' => $payload['provider_transaction_id'],
            'payment_reference' => $payload['payment_reference'] ?? null,
            'instructions' => $payload['instructions'] ?? null,
            ]
        );

        return $this->castTransaction($transaction);
    }

    public function setStatuses(
        int $transactionId,
        string $providerStatus,
        string $paymentStatus,
        ?string $lastError = null
    ): array {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            UPDATE payment_transactions
            SET provider_status = :provider_status,
                payment_status = :payment_status,
                last_error = :last_error,
                updated_at = now()
            WHERE id = :id
            RETURNING *
            SQL
        );
        $statement->execute([
            'provider_status' => $providerStatus,
            'payment_status' => $paymentStatus,
            'last_error' => $lastError,
            'id' => $transactionId,
        ]);

        $transaction = $statement->fetch();
        return $transaction === false ? [] : $this->castTransaction($transaction);
    }

    public function upsertPendingReportReference(int $transactionId, array $payload): void
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            UPDATE payment_transactions
            SET provider_status = :provider_status,
                payment_status = :payment_status,
                last_error = :last_error,
                updated_at = now()
            WHERE id = :id
            SQL
        );
        $statement->execute([
            'provider_status' => $payload['provider_status'] ?? 'pending',
            'payment_status' => $payload['payment_status'] ?? 'pending',
            'last_error' => $payload['last_error'] ?? null,
            'id' => $transactionId,
        ]);
    }

    public function listPendingOlderThanMinutes(int $minutes): array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT
                pt.order_id,
                pt.id AS transaction_id,
                pt.provider,
                pt.provider_status,
                pt.payment_status,
                pt.amount,
                pt.currency,
                pt.created_at AS transaction_created_at,
                o.email,
                o.customer_name,
                o.payment_method
            FROM payment_transactions pt
            INNER JOIN orders o ON o.id = pt.order_id
            WHERE pt.payment_status IN ('pending', 'processing')
              AND pt.created_at <= now() - (:minutes || ' minutes')::interval
            ORDER BY pt.created_at ASC
            SQL
        );
        $statement->bindValue('minutes', $minutes, PDO::PARAM_INT);
        $statement->execute();

        return array_map(function (array $row): array {
            $row['order_id'] = (int) $row['order_id'];
            $row['transaction_id'] = (int) $row['transaction_id'];
            $row['amount'] = (float) $row['amount'];

            return $row;
        }, $statement->fetchAll());
    }

    private function recordIdempotency(int $transactionId, int $orderId, string $idempotencyKey, array $resultPayload): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO payment_idempotency_keys (idempotency_key, transaction_id, order_id, result_payload) VALUES (:idempotency_key, :transaction_id, :order_id, CAST(:result_payload AS jsonb))'
        );
        $statement->execute([
            'idempotency_key' => $idempotencyKey,
            'transaction_id' => $transactionId,
            'order_id' => $orderId,
            'result_payload' => json_encode($resultPayload, JSON_THROW_ON_ERROR),
        ]);
    }

    private function castTransaction(array $transaction): array
    {
        $transaction['id'] = (int) $transaction['id'];
        $transaction['order_id'] = (int) $transaction['order_id'];
        $transaction['amount'] = (float) $transaction['amount'];
        $transaction['result_payload'] = isset($transaction['result_payload']) ? json_decode((string) $transaction['result_payload'], true) ?: [] : [];

        return $transaction;
    }
}
