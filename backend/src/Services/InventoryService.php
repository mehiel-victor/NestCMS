<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InventoryRepository;

final class InventoryService
{
    public function __construct(private readonly InventoryRepository $inventory)
    {
    }

    public function lowStock(): array
    {
        return $this->inventory->lowStock();
    }
}

