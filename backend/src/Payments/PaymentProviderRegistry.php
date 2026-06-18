<?php

declare(strict_types=1);

namespace App\Payments;

use App\Payments\Adapters\MercadoPagoPaymentProvider;
use App\Payments\Adapters\FakePaymentProvider;
use App\Payments\Adapters\PagarMePaymentProvider;
use App\Payments\Adapters\StripePaymentProvider;
use InvalidArgumentException;

final class PaymentProviderRegistry
{
    /** @var array<int, string> */
    private array $fallbackProviders;

    public function __construct(
        private readonly string $defaultProvider = 'mock',
        array $fallbackProviders = ['mock']
    ) {
        $normalizedFallback = array_map([$this, 'normalizeProviderName'], $fallbackProviders);
        $normalizedFallback[] = $this->normalizeProviderName($this->defaultProvider);
        $this->fallbackProviders = array_values(array_unique(array_filter($normalizedFallback, static fn (string $provider): bool => $provider !== '')));
    }

    public function forName(?string $providerName = null, bool $allowFallback = true): PaymentProviderInterface
    {
        $name = $this->normalizeProviderName($providerName ?? $this->defaultProvider);
        $candidates = [$name];
        if ($allowFallback) {
            $candidates = array_merge($candidates, $this->fallbackProviders);
        }
        $candidates = array_values(array_unique(array_filter($candidates)));

        $errors = [];
        foreach ($candidates as $candidate) {
            $provider = $this->resolveProvider($candidate);
            if ($provider !== null) {
                return $provider;
            }
            $errors[] = $candidate;
        }

        throw new InvalidArgumentException(
            'Unsupported payment provider' . ($errors !== [] ? ': ' . implode(', ', $errors) : '') . '.'
        );
    }

    public function default(): PaymentProviderInterface
    {
        return $this->forName($this->defaultProvider);
    }

    private function resolveProvider(string $normalizedProvider): ?PaymentProviderInterface
    {
        return match ($normalizedProvider) {
            'mock', 'fake', 'stub' => new FakePaymentProvider(),
            'stripe' => new StripePaymentProvider(),
            'mercado_pago', 'mercadopago' => new MercadoPagoPaymentProvider(),
            'pagar_me', 'pagarme' => new PagarMePaymentProvider(),
            default => null,
        };
    }

    private function normalizeProviderName(string $providerName): string
    {
        $normalized = strtolower(trim(str_replace('-', '_', $providerName)));
        if (in_array($normalized, ['mercadopago', 'mercado_pago'], true)) {
            return 'mercado_pago';
        }
        if (in_array($normalized, ['pagarme', 'pagar_me'], true)) {
            return 'pagar_me';
        }

        return $normalized;
    }
}
