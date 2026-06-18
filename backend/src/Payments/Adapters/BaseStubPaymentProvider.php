<?php

declare(strict_types=1);

namespace App\Payments\Adapters;

use App\Payments\PaymentProviderInterface;

final class BaseStubPaymentProvider implements PaymentProviderInterface
{
    private const METHODS = ['pix', 'credit_card', 'boleto', 'apple_pay', 'google_pay'];

    public function __construct(
        private readonly string $providerName,
        private readonly string $providerPrefix,
        private readonly string $signatureSecretEnv,
        private readonly string $label,
        private readonly string $paymentBrand,
    ) {
    }

    public function name(): string
    {
        return $this->providerName;
    }

    public function supports(string $paymentMethod): bool
    {
        return in_array($paymentMethod, self::METHODS, true);
    }

    public function createCharge(
        int $orderId,
        float $amount,
        string $currency,
        string $paymentMethod,
        array $metadata
    ): array {
        $transactionId = $this->providerPrefix . '_' . bin2hex(random_bytes(12));
        $baseReference = strtoupper($paymentMethod) . '-' . substr($transactionId, 0, 12);

        return [
            'provider_transaction_id' => $transactionId,
            'provider' => $this->name(),
            'provider_status' => $this->defaultProviderStatus($paymentMethod),
            'payment_reference' => $baseReference,
            'instructions' => $this->instructions($paymentMethod, $baseReference, $metadata),
            'provider_payload' => [
                'order_id' => $orderId,
                'currency' => $currency,
                'amount' => $amount,
                'method' => $paymentMethod,
                'provider' => $this->name(),
                'brand' => $this->paymentBrand,
            ],
        ];
    }

    public function createRefund(string $providerTransactionId, float $amount, string $reason): array
    {
        return [
            'provider_refund_id' => $this->providerPrefix . '_ref_' . substr($providerTransactionId, strlen($this->providerPrefix) + 1) . '_' . bin2hex(random_bytes(4)),
            'provider_status' => 'processing',
            'amount' => $amount,
            'reason' => $reason,
        ];
    }

    public function verifyWebhookSignature(string $rawPayload, string $signature): bool
    {
        $secret = $this->webhookSecret();
        if ($secret === '') {
            return true;
        }

        return hash_equals(hash_hmac('sha256', $rawPayload, $secret), $signature);
    }

    public function normalizeWebhookPayload(array $payload): array
    {
        $eventId = (string) ($payload['event_id'] ?? $payload['id'] ?? '');
        $eventType = (string) ($payload['event_type'] ?? $payload['type'] ?? '');
        $providerTransactionId = (string) ($payload['provider_transaction_id'] ?? $payload['transaction_id'] ?? '');
        $providerStatus = (string) ($payload['provider_status'] ?? $payload['status'] ?? '');

        return [
            'provider_event_id' => $eventId !== '' ? $eventId : $this->providerPrefix . '_' . bin2hex(random_bytes(10)),
            'event_type' => $eventType !== '' ? $eventType : 'payment.updated',
            'provider_transaction_id' => $providerTransactionId,
            'provider_status' => $providerStatus,
            'event_payload' => $payload,
        ];
    }

    private function defaultProviderStatus(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'credit_card', 'apple_pay', 'google_pay' => 'processing',
            default => 'pending',
        };
    }

    private function instructions(string $paymentMethod, string $reference, array $metadata): array
    {
        return match ($paymentMethod) {
            'pix' => [
                'reference' => $reference,
                'instructions' => $this->label . ' PIX: use a referencia enviada para concluir o pagamento.',
                'qr_code' => $metadata['pix_qr_code'] ?? '00020101021226860014BR.GOV.BCB.PIX...' . strtoupper($reference),
            ],
            'boleto' => [
                'reference' => $reference,
                'instructions' => $this->label . ' Boleto: pague ate o vencimento.',
                'barcode' => $metadata['boleto_barcode'] ?? '34191790010104351004791020000012345678901234567',
            ],
            default => [
                'reference' => $reference,
                'instructions' => 'Use o checkout interno da plataforma para concluir o pagamento via ' . $this->paymentBrand . '.',
            ],
        };
    }

    private function webhookSecret(): string
    {
        $env = $_ENV[$this->signatureSecretEnv] ?? getenv($this->signatureSecretEnv);

        return is_string($env) ? $env : '';
    }
}
