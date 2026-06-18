<?php

declare(strict_types=1);

namespace App\Repositories;

use InvalidArgumentException;
use PDO;

final class OrderRepository
{
    private const ALLOWED_STATUSES = ['received', 'processing', 'shipped', 'delivered', 'returned'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listOrders(int $limit = 25): array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT
                o.*,
                COALESCE((
                    SELECT json_agg(row_to_json(i))
                    FROM (
                        SELECT id, variant_id, product_title, sku, quantity, unit_price, total
                        FROM order_items
                        WHERE order_id = o.id
                        ORDER BY id
                    ) i
                ), '[]'::json) AS items
            FROM orders o
            ORDER BY o.created_at DESC
            LIMIT :limit
            SQL
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map([$this, 'castOrder'], $statement->fetchAll());
    }

    public function updateStatus(int $orderId, string $status): array
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid order status.');
        }

        $statement = $this->pdo->prepare('UPDATE orders SET status = :status WHERE id = :id RETURNING *');
        $statement->execute(['status' => $status, 'id' => $orderId]);
        $order = $statement->fetch();

        if (!$order) {
            throw new InvalidArgumentException('Order not found.');
        }

        $order['items'] = '[]';

        return $this->castOrder($order);
    }

    public function findById(int $orderId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id');
        $statement->execute(['id' => $orderId]);
        $order = $statement->fetch();

        if (!$order) {
            return null;
        }

        $order['items'] = '[]';
        return $this->castOrder($order);
    }

    public function updatePaymentState(
        int $orderId,
        ?string $paymentStatus,
        ?string $provider,
        ?string $providerStatus,
        ?int $paymentTransactionId,
        ?string $lastError = null
    ): array {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            UPDATE orders
            SET payment_status = COALESCE(:payment_status, payment_status),
                payment_provider = COALESCE(:payment_provider, payment_provider),
                payment_provider_status = COALESCE(:payment_provider_status, payment_provider_status),
                payment_transaction_id = COALESCE(:payment_transaction_id, payment_transaction_id)
            WHERE id = :id
            RETURNING *
            SQL
        );
        $statement->execute([
            'payment_status' => $paymentStatus,
            'payment_provider' => $provider,
            'payment_provider_status' => $providerStatus,
            'payment_transaction_id' => $paymentTransactionId,
            'id' => $orderId,
        ]);

        $order = $statement->fetch();
        if (!$order) {
            throw new InvalidArgumentException('Order not found.');
        }

        $order['items'] = '[]';
        return $this->castOrder($order);
    }

    public function todayOrderCount(): int
    {
        return (int) $this->pdo
            ->query("SELECT count(*) FROM orders WHERE created_at::date = current_date")
            ->fetchColumn();
    }

    public function monthRevenue(): float
    {
        return (float) $this->pdo
            ->query("SELECT COALESCE(sum(total), 0) FROM orders WHERE date_trunc('month', created_at) = date_trunc('month', now())")
            ->fetchColumn();
    }

    public function previousMonthRevenue(): float
    {
        return (float) $this->pdo
            ->query("SELECT COALESCE(sum(total), 0) FROM orders WHERE date_trunc('month', created_at) = date_trunc('month', now() - interval '1 month')")
            ->fetchColumn();
    }

    public function averageOrderValue(): float
    {
        return (float) $this->pdo->query('SELECT COALESCE(avg(total), 0) FROM orders')->fetchColumn();
    }

    public function totalOrderCount(): int
    {
        return (int) $this->pdo->query('SELECT count(*) FROM orders')->fetchColumn();
    }

    private function castOrder(array $order): array
    {
        $order['id'] = (int) $order['id'];
        $order['customer_id'] = $order['customer_id'] !== null ? (int) $order['customer_id'] : null;
        $order['subtotal'] = (float) $order['subtotal'];
        $order['discount_total'] = (float) $order['discount_total'];
        $order['shipping_total'] = (float) $order['shipping_total'];
        $order['total'] = (float) $order['total'];
        $order['metadata'] = json_decode((string) $order['metadata'], true) ?: [];
        $order['payment_status'] = $order['payment_status'];
        $order['payment_provider'] = $order['payment_provider'];
        $order['payment_provider_status'] = $order['payment_provider_status'];
        $order['payment_transaction_id'] = $order['payment_transaction_id'] !== null ? (int) $order['payment_transaction_id'] : null;
        $order['items'] = array_map(function (array $item): array {
            $item['id'] = (int) $item['id'];
            $item['variant_id'] = $item['variant_id'] !== null ? (int) $item['variant_id'] : null;
            $item['quantity'] = (int) $item['quantity'];
            $item['unit_price'] = (float) $item['unit_price'];
            $item['total'] = (float) $item['total'];

            return $item;
        }, json_decode((string) ($order['items'] ?? '[]'), true) ?: []);

        return $order;
    }
}
