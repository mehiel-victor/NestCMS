<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AnalyticsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function revenueSeries(): array
    {
        $statement = $this->pdo->query(
            <<<'SQL'
            SELECT
                day::date AS date,
                COALESCE(sum(o.total), 0) AS revenue,
                count(o.id) AS orders
            FROM generate_series(current_date - interval '13 days', current_date, interval '1 day') day
            LEFT JOIN orders o ON o.created_at::date = day::date
            GROUP BY day
            ORDER BY day
            SQL
        );

        return array_map(fn (array $row): array => [
            'date' => $row['date'],
            'revenue' => (float) $row['revenue'],
            'orders' => (int) $row['orders'],
        ], $statement->fetchAll());
    }

    public function bestSellers(): array
    {
        $statement = $this->pdo->query(
            <<<'SQL'
            SELECT
                product_title,
                sku,
                sum(quantity) AS units,
                sum(total) AS revenue
            FROM order_items
            GROUP BY product_title, sku
            ORDER BY units DESC, revenue DESC
            LIMIT 5
            SQL
        );

        return array_map(fn (array $row): array => [
            'product_title' => $row['product_title'],
            'sku' => $row['sku'],
            'units' => (int) $row['units'],
            'revenue' => (float) $row['revenue'],
        ], $statement->fetchAll());
    }

    public function highMarginProducts(): array
    {
        $statement = $this->pdo->query(
            <<<'SQL'
            SELECT title, margin_percent, price
            FROM products
            ORDER BY margin_percent DESC, price DESC
            LIMIT 5
            SQL
        );

        return array_map(fn (array $row): array => [
            'title' => $row['title'],
            'margin_percent' => (float) $row['margin_percent'],
            'price' => (float) $row['price'],
        ], $statement->fetchAll());
    }

    public function customerLtv(): array
    {
        $statement = $this->pdo->query(
            <<<'SQL'
            SELECT
                c.name,
                c.email,
                c.ltv + COALESCE(sum(o.total), 0) AS ltv
            FROM customers c
            LEFT JOIN orders o ON o.customer_id = c.id
            GROUP BY c.id
            ORDER BY ltv DESC
            LIMIT 5
            SQL
        );

        return array_map(fn (array $row): array => [
            'name' => $row['name'],
            'email' => $row['email'],
            'ltv' => (float) $row['ltv'],
        ], $statement->fetchAll());
    }

    public function funnel(): array
    {
        $row = $this->pdo->query(
            <<<'SQL'
            SELECT
                COALESCE(sum(visits), 0) AS visits,
                COALESCE(sum(carts), 0) AS carts,
                COALESCE(sum(checkouts), 0) AS checkouts,
                COALESCE(sum(revenue), 0) AS revenue
            FROM traffic_events
            WHERE event_date >= current_date - interval '30 days'
            SQL
        )->fetch();

        $visits = (int) $row['visits'];
        $checkouts = (int) $row['checkouts'];

        return [
            'visits' => $visits,
            'carts' => (int) $row['carts'],
            'checkouts' => $checkouts,
            'revenue' => (float) $row['revenue'],
            'conversion_rate' => $visits > 0 ? round(($checkouts / $visits) * 100, 2) : 0,
        ];
    }

    public function trafficSources(): array
    {
        $statement = $this->pdo->query(
            <<<'SQL'
            SELECT
                source,
                medium,
                sum(visits) AS visits,
                sum(carts) AS carts,
                sum(checkouts) AS checkouts,
                sum(revenue) AS revenue
            FROM traffic_events
            WHERE event_date >= current_date - interval '30 days'
            GROUP BY source, medium
            ORDER BY revenue DESC
            LIMIT 8
            SQL
        );

        return array_map(fn (array $row): array => [
            'source' => $row['source'],
            'medium' => $row['medium'],
            'visits' => (int) $row['visits'],
            'carts' => (int) $row['carts'],
            'checkouts' => (int) $row['checkouts'],
            'revenue' => (float) $row['revenue'],
        ], $statement->fetchAll());
    }
}

