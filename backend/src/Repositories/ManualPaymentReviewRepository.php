<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ManualPaymentReviewRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(
        int $orderId,
        string $actor,
        string $decision,
        ?string $notes,
        string $riskLevel
    ): array {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO manual_payment_reviews (order_id, actor, decision, notes, risk_level)
            VALUES (:order_id, :actor, :decision, :notes, :risk_level)
            RETURNING *
            SQL
        );
        $statement->execute([
            'order_id' => $orderId,
            'actor' => $actor,
            'decision' => $decision,
            'notes' => $notes,
            'risk_level' => $riskLevel,
        ]);

        $review = $statement->fetch();
        if (!$review) {
            throw new \RuntimeException('Failed to create payment review.');
        }

        return $this->castReview($review);
    }

    public function latestByOrder(int $orderId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM manual_payment_reviews WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1'
        );
        $statement->execute(['order_id' => $orderId]);
        $review = $statement->fetch();

        return $review === false ? null : $this->castReview($review);
    }

    private function castReview(array $review): array
    {
        $review['id'] = (int) $review['id'];
        $review['order_id'] = (int) $review['order_id'];

        return $review;
    }
}
