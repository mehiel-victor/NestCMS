<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CatalogRepository;

final class CatalogService
{
    public function __construct(private readonly CatalogRepository $catalog)
    {
    }

    public function listProducts(): array
    {
        return $this->catalog->listProducts();
    }

    public function createProduct(array $payload): array
    {
        return $this->catalog->createProduct($payload);
    }
}

