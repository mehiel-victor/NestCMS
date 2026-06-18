<?php

declare(strict_types=1);

namespace App\Payments;

interface PaymentProviderInterface
{
    public function name(): string;

    public function supports(string $paymentMethod): bool;

    public function createCharge(
        int $orderId,
        float $amount,
        string $currency,
        string $paymentMethod,
        array $metadata
    ): array;

    public function createRefund(string $providerTransactionId, float $amount, string $reason): array;

    public function verifyWebhookSignature(string $rawPayload, string $signature): bool;

    public function normalizeWebhookPayload(array $payload): array;
}
