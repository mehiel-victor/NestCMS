<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MarketingRepository;

final class MarketingService
{
    public function __construct(private readonly MarketingRepository $marketing)
    {
    }

    public function abandonedCarts(): array
    {
        return $this->marketing->abandonedCarts();
    }

    public function sendRecovery(int $cartId): array
    {
        return $this->marketing->sendRecovery($cartId);
    }
}

