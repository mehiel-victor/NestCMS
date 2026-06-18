<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PaymentRefundRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $transactionId, string $providerRefundId, float $amount, string $reason, string $status, ?string $createdBy): array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO payment_refunds (transaction_id, provider_refund_id, amount, reason, status, created_by)
            VALUES (:transaction_id, :provider_refund_id, :amount, :reason, :status, :created_by)
            RETURNING *
            SQL
        );
        $statement->execute([
            'transaction_id' => $transactionId,
            'provider_refund_id' => $providerRefundId,
            'amount' => $amount,
            'reason' => $reason,
            'status' => $status,
            'created_by' => $createdBy,
        ]);

        $refund = $statement->fetch();
        if (!$refund) {
            throw new \RuntimeException('Failed to create payment refund.');
        }

        return $this->castRefund($refund);
    }

    public function totalRefunded(int $transactionId): float
    {
        $statement = $this->pdo->prepare(
            "SELECT COALESCE(sum(amount), 0) FROM payment_refunds WHERE transaction_id = :transaction_id AND status != 'failed'"
        );
        $statement->execute(['transaction_id' => $transactionId]);

        return (float) $statement->fetchColumn();
    }

    public function listByTransaction(int $transactionId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM payment_refunds WHERE transaction_id = :transaction_id ORDER BY created_at DESC'
        );
        $statement->execute(['transaction_id' => $transactionId]);

        return array_map([$this, 'castRefund'], $statement->fetchAll());
    }

    private function castRefund(array $refund): array
    {
        $refund['id'] = (int) $refund['id'];
        $refund['transaction_id'] = (int) $refund['transaction_id'];
        $refund['amount'] = (float) $refund['amount'];

        return $refund;
    }
}
