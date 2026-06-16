<?php

declare(strict_types=1);

namespace App\Repositories;

use InvalidArgumentException;
use PDO;

final class InventoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function lowStock(): array
    {
        $statement = $this->pdo->query(
            <<<'SQL'
            SELECT
                pv.id AS variant_id,
                pv.sku,
                pv.option_name,
                pv.option_value,
                pv.low_stock_threshold,
                p.id AS product_id,
                p.title AS product_title,
                COALESCE(sum(il.quantity), 0) AS quantity,
                json_agg(
                    json_build_object(
                        'warehouse', w.name,
                        'code', w.code,
                        'quantity', il.quantity
                    )
                    ORDER BY w.id
                ) FILTER (WHERE w.id IS NOT NULL) AS locations
            FROM product_variants pv
            INNER JOIN products p ON p.id = pv.product_id
            LEFT JOIN inventory_levels il ON il.variant_id = pv.id
            LEFT JOIN warehouses w ON w.id = il.warehouse_id
            WHERE pv.low_stock_threshold > 0
            GROUP BY pv.id, p.id
            HAVING COALESCE(sum(il.quantity), 0) <= pv.low_stock_threshold
            ORDER BY quantity ASC, pv.sku ASC
            SQL
        );

        return array_map(function (array $row): array {
            $row['variant_id'] = (int) $row['variant_id'];
            $row['product_id'] = (int) $row['product_id'];
            $row['low_stock_threshold'] = (int) $row['low_stock_threshold'];
            $row['quantity'] = (int) $row['quantity'];
            $row['locations'] = json_decode((string) ($row['locations'] ?? '[]'), true) ?: [];

            return $row;
        }, $statement->fetchAll());
    }

    public function decrementStock(int $variantId, int $quantity, string $reason): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive.');
        }

        $levels = $this->pdo->prepare(
            <<<'SQL'
            SELECT id, warehouse_id, quantity
            FROM inventory_levels
            WHERE variant_id = :variant_id
            ORDER BY quantity DESC, warehouse_id ASC
            SQL
        );
        $levels->execute(['variant_id' => $variantId]);

        $remaining = $quantity;
        foreach ($levels->fetchAll() as $level) {
            if ($remaining === 0) {
                break;
            }

            $available = (int) $level['quantity'];
            if ($available <= 0) {
                continue;
            }

            $delta = min($available, $remaining);
            $update = $this->pdo->prepare(
                'UPDATE inventory_levels SET quantity = quantity - :quantity, updated_at = now() WHERE id = :id'
            );
            $update->execute(['quantity' => $delta, 'id' => $level['id']]);

            $movement = $this->pdo->prepare(
                <<<'SQL'
                INSERT INTO inventory_movements (variant_id, warehouse_id, delta_quantity, reason)
                VALUES (:variant_id, :warehouse_id, :delta_quantity, :reason)
                SQL
            );
            $movement->execute([
                'variant_id' => $variantId,
                'warehouse_id' => $level['warehouse_id'],
                'delta_quantity' => -$delta,
                'reason' => $reason,
            ]);

            $remaining -= $delta;
        }

        if ($remaining > 0) {
            throw new InvalidArgumentException('Insufficient inventory for variant ' . $variantId . '.');
        }

        $variant = $this->pdo->prepare('UPDATE product_variants SET stock = stock - :quantity WHERE id = :id');
        $variant->execute(['quantity' => $quantity, 'id' => $variantId]);
    }

    public function movementHistory(int $limit = 20): array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT
                im.id,
                im.delta_quantity,
                im.reason,
                im.created_at,
                pv.sku,
                p.title AS product_title,
                w.name AS warehouse
            FROM inventory_movements im
            INNER JOIN product_variants pv ON pv.id = im.variant_id
            INNER JOIN products p ON p.id = pv.product_id
            INNER JOIN warehouses w ON w.id = im.warehouse_id
            ORDER BY im.created_at DESC
            LIMIT :limit
            SQL
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['delta_quantity'] = (int) $row['delta_quantity'];

            return $row;
        }, $statement->fetchAll());
    }
}

