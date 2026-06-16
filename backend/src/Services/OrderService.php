<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepository;

final class OrderService
{
    public function __construct(private readonly OrderRepository $orders)
    {
    }

    public function listOrders(): array
    {
        return $this->orders->listOrders();
    }

    public function updateStatus(int $orderId, string $status): array
    {
        return $this->orders->updateStatus($orderId, $status);
    }
}

