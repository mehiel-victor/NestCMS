<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AnalyticsRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\MarketingRepository;
use App\Repositories\OrderRepository;

final class DashboardService
{
    public function __construct(
        private readonly CatalogRepository $catalog,
        private readonly InventoryRepository $inventory,
        private readonly OrderRepository $orders,
        private readonly MarketingRepository $marketing,
        private readonly AnalyticsRepository $analytics
    ) {
    }

    public function summary(): array
    {
        $monthRevenue = $this->orders->monthRevenue();
        $previousRevenue = $this->orders->previousMonthRevenue();
        $revenueDelta = $previousRevenue > 0
            ? round((($monthRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
            : 100.0;

        return [
            'kpis' => [
                'month_revenue' => $monthRevenue,
                'previous_month_revenue' => $previousRevenue,
                'revenue_delta_percent' => $revenueDelta,
                'orders_today' => $this->orders->todayOrderCount(),
                'average_order_value' => $this->orders->averageOrderValue(),
                'total_orders' => $this->orders->totalOrderCount(),
                'products' => $this->catalog->productCount(),
                'published_products' => $this->catalog->publishedCount(),
                'abandoned_carts' => $this->marketing->abandonedCount(),
                'conversion_rate' => $this->analytics->funnel()['conversion_rate'],
            ],
            'low_stock' => $this->inventory->lowStock(),
            'recent_orders' => $this->orders->listOrders(8),
            'inventory_movements' => $this->inventory->movementHistory(8),
            'analytics' => [
                'series' => $this->analytics->revenueSeries(),
                'best_sellers' => $this->analytics->bestSellers(),
                'traffic_sources' => $this->analytics->trafficSources(),
            ],
        ];
    }
}

