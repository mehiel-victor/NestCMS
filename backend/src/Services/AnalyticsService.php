<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AnalyticsRepository;

final class AnalyticsService
{
    public function __construct(private readonly AnalyticsRepository $analytics)
    {
    }

    public function revenueReport(): array
    {
        return [
            'series' => $this->analytics->revenueSeries(),
            'funnel' => $this->analytics->funnel(),
            'best_sellers' => $this->analytics->bestSellers(),
            'high_margin_products' => $this->analytics->highMarginProducts(),
            'customer_ltv' => $this->analytics->customerLtv(),
            'traffic_sources' => $this->analytics->trafficSources(),
        ];
    }
}

