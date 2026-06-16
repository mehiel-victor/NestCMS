<?php

declare(strict_types=1);

namespace App\Repositories;

use InvalidArgumentException;
use PDO;

final class CatalogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listProducts(): array
    {
        $statement = $this->pdo->query(
            <<<'SQL'
            SELECT
                p.*,
                c.name AS category_name,
                co.name AS collection_name,
                COALESCE((
                    SELECT json_agg(row_to_json(v))
                    FROM (
                        SELECT id, sku, option_name, option_value, price, stock, low_stock_threshold, is_digital
                        FROM product_variants
                        WHERE product_id = p.id
                        ORDER BY id
                    ) v
                ), '[]'::json) AS variants,
                COALESCE((
                    SELECT json_agg(row_to_json(m))
                    FROM (
                        SELECT id, media_type, url, title, sort_order
                        FROM product_media
                        WHERE product_id = p.id
                        ORDER BY sort_order, id
                    ) m
                ), '[]'::json) AS media
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN collections co ON co.id = p.collection_id
            ORDER BY p.updated_at DESC, p.id DESC
            SQL
        );

        return array_map([$this, 'decodeProduct'], $statement->fetchAll());
    }

    public function createProduct(array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Product title is required.');
        }

        $variants = $payload['variants'] ?? [];
        if (!is_array($variants) || count($variants) === 0) {
            throw new InvalidArgumentException('At least one product variant is required.');
        }

        $this->pdo->beginTransaction();

        try {
            $slug = $this->uniqueSlug($title);
            $statement = $this->pdo->prepare(
                <<<'SQL'
                INSERT INTO products (
                    title, slug, description, product_type, visibility, price, compare_at_price,
                    margin_percent, category_id, collection_id, custom_fields, scheduled_at
                )
                VALUES (
                    :title, :slug, :description, :product_type, :visibility, :price, :compare_at_price,
                    :margin_percent, :category_id, :collection_id, CAST(:custom_fields AS jsonb), :scheduled_at
                )
                RETURNING *
                SQL
            );

            $statement->execute([
                'title' => $title,
                'slug' => $slug,
                'description' => (string) ($payload['description'] ?? ''),
                'product_type' => $payload['product_type'] ?? 'physical',
                'visibility' => $payload['visibility'] ?? 'draft',
                'price' => (float) ($payload['price'] ?? 0),
                'compare_at_price' => $payload['compare_at_price'] ?? null,
                'margin_percent' => (float) ($payload['margin_percent'] ?? 45),
                'category_id' => $payload['category_id'] ?? null,
                'collection_id' => $payload['collection_id'] ?? null,
                'custom_fields' => json_encode($payload['custom_fields'] ?? [], JSON_THROW_ON_ERROR),
                'scheduled_at' => $payload['scheduled_at'] ?? null,
            ]);

            $product = $statement->fetch();
            $warehouseId = $this->defaultWarehouseId();

            foreach ($variants as $variant) {
                $variantStatement = $this->pdo->prepare(
                    <<<'SQL'
                    INSERT INTO product_variants (
                        product_id, sku, option_name, option_value, price, stock, low_stock_threshold, is_digital
                    )
                    VALUES (
                        :product_id, :sku, :option_name, :option_value, :price, :stock, :low_stock_threshold, :is_digital
                    )
                    RETURNING id
                    SQL
                );

                $stock = (int) ($variant['stock'] ?? 0);
                $variantStatement->execute([
                    'product_id' => $product['id'],
                    'sku' => strtoupper(trim((string) ($variant['sku'] ?? ''))),
                    'option_name' => $variant['option_name'] ?? 'Default',
                    'option_value' => $variant['option_value'] ?? 'Default',
                    'price' => (float) ($variant['price'] ?? $payload['price'] ?? 0),
                    'stock' => $stock,
                    'low_stock_threshold' => (int) ($variant['low_stock_threshold'] ?? 5),
                    'is_digital' => (bool) ($variant['is_digital'] ?? ($payload['product_type'] ?? '') === 'digital'),
                ]);

                $variantId = (int) $variantStatement->fetchColumn();
                $this->createInitialInventory($variantId, $warehouseId, $stock);
            }

            foreach (($payload['media'] ?? []) as $index => $media) {
                if (!is_array($media) || empty($media['url'])) {
                    continue;
                }

                $mediaStatement = $this->pdo->prepare(
                    <<<'SQL'
                    INSERT INTO product_media (product_id, media_type, url, title, sort_order)
                    VALUES (:product_id, :media_type, :url, :title, :sort_order)
                    SQL
                );

                $mediaStatement->execute([
                    'product_id' => $product['id'],
                    'media_type' => $media['media_type'] ?? 'image',
                    'url' => $media['url'],
                    'title' => $media['title'] ?? null,
                    'sort_order' => (int) ($media['sort_order'] ?? $index + 1),
                ]);
            }

            $this->pdo->commit();

            return $this->findProduct((int) $product['id']);
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function findProduct(int $id): array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT
                p.*,
                c.name AS category_name,
                co.name AS collection_name,
                COALESCE((
                    SELECT json_agg(row_to_json(v))
                    FROM (
                        SELECT id, sku, option_name, option_value, price, stock, low_stock_threshold, is_digital
                        FROM product_variants
                        WHERE product_id = p.id
                        ORDER BY id
                    ) v
                ), '[]'::json) AS variants,
                COALESCE((
                    SELECT json_agg(row_to_json(m))
                    FROM (
                        SELECT id, media_type, url, title, sort_order
                        FROM product_media
                        WHERE product_id = p.id
                        ORDER BY sort_order, id
                    ) m
                ), '[]'::json) AS media
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN collections co ON co.id = p.collection_id
            WHERE p.id = :id
            SQL
        );

        $statement->execute(['id' => $id]);
        $product = $statement->fetch();

        if (!$product) {
            throw new InvalidArgumentException('Product not found.');
        }

        return $this->decodeProduct($product);
    }

    public function findVariants(array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $statement = $this->pdo->prepare(
            <<<SQL
            SELECT
                pv.*,
                p.title AS product_title,
                p.product_type,
                p.margin_percent
            FROM product_variants pv
            INNER JOIN products p ON p.id = pv.product_id
            WHERE pv.id IN ({$placeholders})
            SQL
        );

        $statement->execute(array_values($variantIds));
        $variants = [];

        foreach ($statement->fetchAll() as $variant) {
            $variants[(int) $variant['id']] = $this->castVariant($variant);
        }

        return $variants;
    }

    public function productCount(): int
    {
        return (int) $this->pdo->query('SELECT count(*) FROM products')->fetchColumn();
    }

    public function publishedCount(): int
    {
        return (int) $this->pdo->query("SELECT count(*) FROM products WHERE visibility = 'published'")->fetchColumn();
    }

    private function uniqueSlug(string $title): string
    {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
        $base = $base !== '' ? $base : 'product';
        $slug = $base;
        $suffix = 2;

        $statement = $this->pdo->prepare('SELECT count(*) FROM products WHERE slug = :slug');
        while (true) {
            $statement->execute(['slug' => $slug]);
            if ((int) $statement->fetchColumn() === 0) {
                return $slug;
            }

            $slug = $base . '-' . $suffix;
            $suffix++;
        }
    }

    private function defaultWarehouseId(): int
    {
        $warehouseId = $this->pdo->query('SELECT id FROM warehouses ORDER BY id LIMIT 1')->fetchColumn();

        if (!$warehouseId) {
            throw new InvalidArgumentException('Create a warehouse before adding inventory.');
        }

        return (int) $warehouseId;
    }

    private function createInitialInventory(int $variantId, int $warehouseId, int $quantity): void
    {
        $level = $this->pdo->prepare(
            'INSERT INTO inventory_levels (variant_id, warehouse_id, quantity) VALUES (:variant_id, :warehouse_id, :quantity)'
        );
        $level->execute([
            'variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
        ]);

        if ($quantity !== 0) {
            $movement = $this->pdo->prepare(
                'INSERT INTO inventory_movements (variant_id, warehouse_id, delta_quantity, reason) VALUES (:variant_id, :warehouse_id, :delta_quantity, :reason)'
            );
            $movement->execute([
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'delta_quantity' => $quantity,
                'reason' => 'initial_stock',
            ]);
        }
    }

    private function decodeProduct(array $product): array
    {
        $product['id'] = (int) $product['id'];
        $product['price'] = (float) $product['price'];
        $product['compare_at_price'] = $product['compare_at_price'] !== null ? (float) $product['compare_at_price'] : null;
        $product['margin_percent'] = (float) $product['margin_percent'];
        $product['category_id'] = $product['category_id'] !== null ? (int) $product['category_id'] : null;
        $product['collection_id'] = $product['collection_id'] !== null ? (int) $product['collection_id'] : null;
        $product['custom_fields'] = json_decode((string) $product['custom_fields'], true) ?: [];
        $product['variants'] = array_map([$this, 'castVariant'], json_decode((string) $product['variants'], true) ?: []);
        $product['media'] = json_decode((string) $product['media'], true) ?: [];

        return $product;
    }

    private function castVariant(array $variant): array
    {
        $variant['id'] = (int) $variant['id'];
        $variant['product_id'] = isset($variant['product_id']) ? (int) $variant['product_id'] : null;
        $variant['price'] = (float) $variant['price'];
        $variant['stock'] = (int) $variant['stock'];
        $variant['low_stock_threshold'] = (int) $variant['low_stock_threshold'];
        $variant['is_digital'] = (bool) $variant['is_digital'];
        $variant['margin_percent'] = isset($variant['margin_percent']) ? (float) $variant['margin_percent'] : null;

        return $variant;
    }
}
