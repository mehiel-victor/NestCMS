<?php

declare(strict_types=1);

namespace App\Services;

use App\Payments\PaymentProviderInterface;
use App\Payments\PaymentProviderRegistry;
use App\Repositories\ManualPaymentReviewRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentEventRepository;
use App\Repositories\PaymentRefundRepository;
use App\Repositories\PaymentTransactionRepository;
use PDOException;
use InvalidArgumentException;

final class PaymentService
{
    public function __construct(
        private readonly PaymentTransactionRepository $transactions,
        private readonly PaymentEventRepository $events,
        private readonly PaymentRefundRepository $refunds,
        private readonly ManualPaymentReviewRepository $reviews,
        private readonly OrderRepository $orders,
        private readonly PaymentProviderInterface $provider,
        private readonly PaymentProviderRegistry $providerRegistry
    ) {
    }

    public function getTransactionByIdempotency(string $idempotencyKey): ?array
    {
        return $this->transactions->findByIdempotencyKey($idempotencyKey);
    }

    public function createTransaction(
        int $orderId,
        float $amount,
        string $currency,
        string $paymentMethod,
        string $idempotencyKey,
        array $metadata = []
    ): array {
        if (!$this->provider->supports($paymentMethod)) {
            throw new InvalidArgumentException('Unsupported payment method.');
        }

        $existing = $this->transactions->findByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            if ((int) $existing['order_id'] !== $orderId) {
                throw new InvalidArgumentException('Duplicate idempotency key already used for another order.');
            }

            return $this->mergeTransactionContext($existing, $orderId);
        }

        $charge = $this->provider->createCharge($orderId, $amount, $currency, $paymentMethod, $metadata);

        $providerStatus = (string) ($charge['provider_status'] ?? 'pending');
        $transactionStatus = $this->translateProviderStatus($providerStatus);

        $transaction = $this->transactions->create([
            'order_id' => $orderId,
            'provider' => (string) ($charge['provider'] ?? $this->provider->name()),
            'provider_transaction_id' => (string) $charge['provider_transaction_id'],
            'idempotency_key' => $idempotencyKey,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'currency' => $currency,
            'provider_status' => $providerStatus,
            'payment_status' => $transactionStatus,
            'last_error' => null,
        ]);

        $this->orders->updatePaymentState(
            $orderId,
            $transactionStatus,
            (string) ($charge['provider'] ?? $this->provider->name()),
            $providerStatus,
            (int) $transaction['id'],
            null
        );

        return $this->mergeTransactionContext($transaction, $orderId, $charge);
    }

    public function handleWebhook(
        string $providerName,
        array $payload,
        string $rawPayload,
        string $signature,
        ?string $requestId = null
    ): array
    {
        $requestTraceId = is_string($requestId) ? $requestId : null;
        $webhookProvider = $this->providerRegistry->forName($providerName, false);

        if (!$webhookProvider->verifyWebhookSignature($rawPayload, $signature)) {
            $this->logWebhookEvent('invalid_signature', [
                'provider' => $providerName,
                'request_id' => $requestTraceId,
            ]);
            throw new InvalidArgumentException('Invalid webhook signature.');
        }

        $normalized = $webhookProvider->normalizeWebhookPayload($payload);
        $providerEventId = (string) $normalized['provider_event_id'];
        $providerStatus = (string) ($normalized['provider_status'] ?? '');
        $providerTransactionId = (string) $normalized['provider_transaction_id'];

        $transaction = $this->transactions->findByProviderTransactionId($providerTransactionId);
        if (!$transaction) {
            throw new InvalidArgumentException('Payment transaction not found.');
        }

        if ($this->events->hasProviderEvent($providerName, $providerEventId)) {
            $this->logWebhookEvent('duplicate_event', [
                'provider' => $providerName,
                'provider_event_id' => $providerEventId,
                'request_id' => $requestTraceId,
            ]);

            return [
                'status' => 'duplicate',
                'order_id' => $transaction['order_id'],
                'payment_status' => $transaction['payment_status'],
            ];
        }

        try {
            $this->events->create((int) $transaction['id'], $providerName, array_merge(
                $normalized,
                ['event_payload' => $payload]
            ));
        } catch (PDOException $exception) {
            if ($this->isDuplicateEventError($exception)) {
                $this->logWebhookEvent('duplicate_event_db', [
                    'provider' => $providerName,
                    'provider_event_id' => $providerEventId,
                    'request_id' => $requestTraceId,
                ]);
                return [
                    'status' => 'duplicate',
                    'order_id' => $transaction['order_id'],
                    'payment_status' => $transaction['payment_status'],
                ];
            }
            throw $exception;
        }

        $nextPaymentStatus = $this->translateProviderStatus($providerStatus);
        $updated = $this->transactions->setStatuses((int) $transaction['id'], $providerStatus, $nextPaymentStatus);

        $this->logWebhookEvent('processed', [
            'provider' => $providerName,
            'provider_event_id' => $providerEventId,
            'request_id' => $requestTraceId,
            'order_id' => $transaction['order_id'],
            'previous_status' => $transaction['payment_status'],
            'next_status' => $nextPaymentStatus,
        ]);

        $this->orders->updatePaymentState(
            (int) $transaction['order_id'],
            (string) $nextPaymentStatus,
            (string) $transaction['provider'],
            $providerStatus,
            (int) $transaction['id'],
            null
        );

        return [
            'status' => 'processed',
            'order_id' => $transaction['order_id'],
            'transaction' => $updated,
            'provider_event_id' => $providerEventId,
        ];
    }

    public function refund(int $orderId, float $amount, string $reason, ?string $actor = null): array
    {
        $order = $this->orders->findById($orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Order not found.');
        }

        $transaction = $this->transactions->findByOrderId($orderId);
        if (!$transaction) {
            throw new InvalidArgumentException('Order has no payment transaction.');
        }

        $maxRefund = (float) $transaction['amount'];
        $alreadyRefunded = $this->refunds->totalRefunded((int) $transaction['id']);
        if ($amount <= 0 || $amount > $maxRefund - $alreadyRefunded) {
            throw new InvalidArgumentException('Invalid refund amount.');
        }

        $allowedStatuses = ['approved', 'partially_refunded', 'processing'];
        if (!in_array($transaction['payment_status'], $allowedStatuses, true)) {
            throw new InvalidArgumentException('Order payment status is not eligible for refund.');
        }

        $providerRefund = $this->provider->createRefund((string) $transaction['provider_transaction_id'], $amount, $reason);

        $refund = $this->refunds->create(
            (int) $transaction['id'],
            (string) ($providerRefund['provider_refund_id'] ?? ''),
            (float) $providerRefund['amount'],
            (string) ($providerRefund['reason'] ?? $reason),
            (string) ($providerRefund['provider_status'] ?? 'processing'),
            $actor
        );

        $newTotal = $alreadyRefunded + $amount;
        $nextPaymentStatus = $newTotal >= $maxRefund ? 'refunded' : 'partially_refunded';

        $updated = $this->transactions->setStatuses(
            (int) $transaction['id'],
            (string) ($providerRefund['provider_status'] ?? 'processing'),
            $nextPaymentStatus
        );
        $this->orders->updatePaymentState(
            $orderId,
            $nextPaymentStatus,
            (string) $transaction['provider'],
            (string) ($providerRefund['provider_status'] ?? 'processing'),
            (int) $transaction['id'],
            null
        );

        return [
            'refund' => $refund,
            'transaction' => $updated,
            'previous_refunded' => $alreadyRefunded,
            'new_refunded' => $newTotal,
            'order_id' => $orderId,
            'processed_by' => $actor,
        ];
    }

    public function addManualReview(int $orderId, string $actor, string $decision, ?string $notes, string $riskLevel): array
    {
        $order = $this->orders->findById($orderId);
        if ($order === null) {
            throw new InvalidArgumentException('Order not found.');
        }

        $decisionSafe = trim($decision);
        if ($decisionSafe === '') {
            throw new InvalidArgumentException('Review decision is required.');
        }

        $review = $this->reviews->create($orderId, $actor, $decisionSafe, $notes, $riskLevel);
        if (strtolower($decisionSafe) === 'chargeback') {
            $this->orders->updatePaymentState(
                $orderId,
                'chargeback',
                $order['payment_provider'] ?? null,
                $order['payment_provider_status'] ?? null,
                $order['payment_transaction_id'] ? (int) $order['payment_transaction_id'] : null,
                null
            );
        }

        return [
            'order_id' => $orderId,
            'review' => $review,
        ];
    }

    public function pendingReport(int $minutes): array
    {
        return $this->transactions->listPendingOlderThanMinutes($minutes);
    }

    public function composeIdempotencyKey(array $payload, string $email): string
    {
        if (($payload['idempotency_key'] ?? '') !== '') {
            return (string) $payload['idempotency_key'];
        }

        $items = array_map(
            function (array $item): string {
                $variantId = (string) (int) ($item['variant_id'] ?? 0);
                $quantity = (string) (int) ($item['quantity'] ?? 1);

                return "{$variantId}:{$quantity}";
            },
            $payload['items'] ?? []
        );
        sort($items, SORT_STRING);

        $fingerprint = [
            'email' => strtolower(trim($email)),
            'payment_method' => $payload['payment_method'] ?? 'pix',
            'shipping_method' => $payload['shipping_method'] ?? 'standard',
            'coupon_code' => $payload['coupon_code'] ?? '',
            'items' => $items,
        ];

        return 'manual_' . hash('sha256', json_encode($fingerprint, JSON_THROW_ON_ERROR));
    }

    private function translateProviderStatus(string $providerStatus): string
    {
        return match ($providerStatus) {
            'approved', 'succeeded', 'paid', 'captured', 'completed' => 'approved',
            'processing' => 'processing',
            'authorized' => 'processing',
            'pending' => 'pending',
            'refunded', 'partially_refunded' => 'partially_refunded',
            'chargeback' => 'chargeback',
            'failed', 'declined', 'canceled', 'cancelled', 'expired' => 'failed',
            default => 'pending',
        };
    }

    private function mergeTransactionContext(array $transaction, int $orderId, array $providerPayload = []): array
    {
        $resultPayload = is_array($transaction['result_payload'] ?? null) ? $transaction['result_payload'] : [];
        return array_merge($transaction, [
            'order_id' => $orderId,
            'instructions' => $providerPayload['instructions'] ?? ($resultPayload['instructions'] ?? null),
            'payment_reference' => $providerPayload['payment_reference'] ?? ($resultPayload['payment_reference'] ?? null),
        ]);
    }

    private function isDuplicateEventError(PDOException $exception): bool
    {
        return $exception->getCode() === '23505'
            || str_contains((string) $exception->getMessage(), 'payment_events_provider_event_id_key');
    }

    private function logWebhookEvent(string $event, array $payload): void
    {
        error_log(
            json_encode([
                'event' => $event,
                'component' => 'payment_webhook',
                'provider' => $payload['provider'] ?? null,
                'provider_event_id' => $payload['provider_event_id'] ?? null,
                'order_id' => $payload['order_id'] ?? null,
                'request_id' => $payload['request_id'] ?? null,
                'previous_status' => $payload['previous_status'] ?? null,
                'next_status' => $payload['next_status'] ?? null,
                'created_at' => gmdate('c'),
            ], JSON_THROW_ON_ERROR)
        );
    }
}
