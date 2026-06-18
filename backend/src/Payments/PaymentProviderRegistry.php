<?php

declare(strict_types=1);

namespace App\Payments;

use App\Payments\Adapters\FakePaymentProvider;
use InvalidArgumentException;

final class PaymentProviderRegistry
{
    public function __construct(
        private readonly string $defaultProvider = 'mock'
    ) {
    }

    public function forName(?string $providerName = null): PaymentProviderInterface
    {
        $name = $providerName !== null ? strtolower(trim($providerName)) : $this->defaultProvider;

        return match ($name) {
            'mock', 'fake', 'stub' => new FakePaymentProvider(),
            default => throw new InvalidArgumentException('Unsupported payment provider.'),
        };
    }

    public function default(): PaymentProviderInterface
    {
        return $this->forName($this->defaultProvider);
    }
}
