<?php

declare(strict_types=1);

namespace App\Repositories;

use InvalidArgumentException;
use PDO;

final class MarketingRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function abandonedCarts(): array
    {
        $statement = $this->pdo->query(
            <<<'SQL'
            SELECT
                c.*,
                COALESCE(sum(ci.quantity * ci.unit_price), 0) AS cart_total,
                COALESCE((
                    SELECT json_agg(row_to_json(i))
                    FROM (
                        SELECT
                            ci.id,
                            ci.quantity,
                            ci.unit_price,
                            pv.sku,
                            p.title AS product_title
                        FROM cart_items ci
                        INNER JOIN product_variants pv ON pv.id = ci.variant_id
                        INNER JOIN products p ON p.id = pv.product_id
                        WHERE ci.cart_id = c.id
                        ORDER BY ci.id
                    ) i
                ), '[]'::json) AS items,
                (
                    SELECT max(sent_at)
                    FROM email_events ee
                    WHERE ee.cart_id = c.id
                    AND ee.event_type = 'abandoned_cart_recovery'
                ) AS last_recovery_sent_at
            FROM carts c
            LEFT JOIN cart_items ci ON ci.cart_id = c.id
            WHERE c.status = 'abandoned'
            AND c.updated_at <= now() - interval '1 hour'
            GROUP BY c.id
            ORDER BY c.updated_at ASC
            SQL
        );

        return array_map([$this, 'castCart'], $statement->fetchAll());
    }

    public function sendRecovery(int $cartId): array
    {
        $cartStatement = $this->pdo->prepare('SELECT * FROM carts WHERE id = :id');
        $cartStatement->execute(['id' => $cartId]);
        $cart = $cartStatement->fetch();

        if (!$cart) {
            throw new InvalidArgumentException('Cart not found.');
        }

        $event = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO email_events (cart_id, email, event_type, payload)
            VALUES (:cart_id, :email, 'abandoned_cart_recovery', CAST(:payload AS jsonb))
            RETURNING id, cart_id, email, event_type, sent_at, payload
            SQL
        );
        $event->execute([
            'cart_id' => $cartId,
            'email' => $cart['email'],
            'payload' => json_encode([
                'provider' => 'simulated',
                'recovery_url' => '/checkout?recovery=' . $cart['recovery_token'],
            ], JSON_THROW_ON_ERROR),
        ]);

        $created = $event->fetch();
        $created['id'] = (int) $created['id'];
        $created['cart_id'] = (int) $created['cart_id'];
        $created['payload'] = json_decode((string) $created['payload'], true) ?: [];

        return $created;
    }

    public function abandonedCount(): int
    {
        return (int) $this->pdo
            ->query("SELECT count(*) FROM carts WHERE status = 'abandoned' AND updated_at <= now() - interval '1 hour'")
            ->fetchColumn();
    }

    private function castCart(array $cart): array
    {
        $cart['id'] = (int) $cart['id'];
        $cart['cart_total'] = (float) $cart['cart_total'];
        $cart['items'] = array_map(function (array $item): array {
            $item['id'] = (int) $item['id'];
            $item['quantity'] = (int) $item['quantity'];
            $item['unit_price'] = (float) $item['unit_price'];

            return $item;
        }, json_decode((string) $cart['items'], true) ?: []);

        return $cart;
    }
}
