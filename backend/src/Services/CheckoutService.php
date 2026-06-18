<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CatalogRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\OrderRepository;
use InvalidArgumentException;
use PDO;

final class CheckoutService
{
    private const PAYMENT_METHODS = ['credit_card', 'boleto', 'pix', 'apple_pay', 'google_pay'];

    public function __construct(
        private readonly PDO $pdo,
        private readonly CatalogRepository $catalog,
        private readonly InventoryRepository $inventory,
        private readonly OrderRepository $orders,
        private readonly PaymentService $payments
    ) {
    }

    public function createOrder(array $payload): array
    {
        $customer = $payload['customer'] ?? [];
        $email = strtolower(trim((string) ($customer['email'] ?? $payload['email'] ?? '')));
        $name = trim((string) ($customer['name'] ?? $payload['customer_name'] ?? 'Guest Customer'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid customer email is required.');
        }

        $items = $payload['items'] ?? [];
        if (!is_array($items) || $items === []) {
            throw new InvalidArgumentException('Checkout requires at least one item.');
        }

        $paymentMethod = (string) ($payload['payment_method'] ?? 'pix');
        if (!in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            throw new InvalidArgumentException('Unsupported payment method.');
        }

        $idempotencyKey = $this->payments->composeIdempotencyKey($payload, $email);
        $existingTransaction = $this->payments->getTransactionByIdempotency($idempotencyKey);
        if ($existingTransaction !== null && (int) $existingTransaction['order_id'] > 0) {
            $existingOrder = $this->orders->findById((int) $existingTransaction['order_id']);
            if ($existingOrder !== null) {
                return $this->formatCheckoutResponse(
                    $existingOrder,
                    array_merge($existingTransaction, ['idempotency_key' => $idempotencyKey])
                );
            }
        }

        $variantIds = array_map(fn (array $item): int => (int) ($item['variant_id'] ?? 0), $items);
        $variants = $this->catalog->findVariants(array_values(array_unique(array_filter($variantIds))));

        $this->pdo->beginTransaction();

        try {
            $orderItems = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $variantId = (int) ($item['variant_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 1);

                if ($quantity <= 0 || !isset($variants[$variantId])) {
                    throw new InvalidArgumentException('Invalid checkout item.');
                }

                $variant = $variants[$variantId];
                if ($variant['stock'] < $quantity) {
                    throw new InvalidArgumentException('Insufficient stock for SKU ' . $variant['sku'] . '.');
                }

                $unitPrice = (float) $variant['price'];
                $total = round($unitPrice * $quantity, 2);
                $subtotal += $total;
                $orderItems[] = [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ];
            }

            $coupon = $this->coupon((string) ($payload['coupon_code'] ?? ''));
            $shippingTotal = $this->shippingTotal((string) ($payload['shipping_method'] ?? 'standard'), $orderItems);
            $discountTotal = $this->discountTotal($coupon, $subtotal, $shippingTotal);

            if (($coupon['discount_type'] ?? '') === 'free_shipping') {
                $shippingTotal = 0.0;
            }

            $total = max(0, round($subtotal - $discountTotal + $shippingTotal, 2));
            $customerId = $this->upsertCustomer($email, $name, $total);

            $orderStatement = $this->pdo->prepare(
                <<<'SQL'
                INSERT INTO orders (
                    customer_id, email, customer_name, status, payment_method, shipping_method,
                    coupon_code, subtotal, discount_total, shipping_total, total, utm_source, metadata
                )
                VALUES (
                    :customer_id, :email, :customer_name, 'received', :payment_method, :shipping_method,
                    :coupon_code, :subtotal, :discount_total, :shipping_total, :total, :utm_source, CAST(:metadata AS jsonb)
                )
                RETURNING id
                SQL
            );
                $orderStatement->execute([
                    'customer_id' => $customerId,
                    'email' => $email,
                    'customer_name' => $name,
                    'payment_method' => $paymentMethod,
                    'shipping_method' => (string) ($payload['shipping_method'] ?? 'standard'),
                'coupon_code' => $coupon['code'] ?? null,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_total' => $shippingTotal,
                'total' => $total,
                'utm_source' => $payload['utm_source'] ?? null,
                    'metadata' => json_encode([
                        'create_account_after_purchase' => (bool) ($payload['create_account'] ?? false),
                        'upsell_ids' => $payload['upsell_ids'] ?? [],
                        'cross_sell_ids' => $payload['cross_sell_ids'] ?? [],
                        'payment_simulated' => true,
                    ], JSON_THROW_ON_ERROR),
                ]);

            $orderId = (int) $orderStatement->fetchColumn();

            foreach ($orderItems as $item) {
                $variant = $item['variant'];
                $line = $this->pdo->prepare(
                    <<<'SQL'
                    INSERT INTO order_items (order_id, variant_id, product_title, sku, quantity, unit_price, total)
                    VALUES (:order_id, :variant_id, :product_title, :sku, :quantity, :unit_price, :total)
                    SQL
                );
                $line->execute([
                    'order_id' => $orderId,
                    'variant_id' => $variant['id'],
                    'product_title' => $variant['product_title'],
                    'sku' => $variant['sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);

                $this->inventory->decrementStock((int) $variant['id'], (int) $item['quantity'], 'checkout_order_' . $orderId);
            }

            $this->recordTrafficCheckout((string) ($payload['utm_source'] ?? 'direct'), $total);
            $this->markRecoverableCartConverted($email);
            $payment = $this->payments->createTransaction(
                $orderId,
                $total,
                'BRL',
                $paymentMethod,
                $idempotencyKey,
                ['customer_email' => $email, 'customer_name' => $name]
            );

            $this->pdo->commit();

            return $this->formatCheckoutResponse(
                [
                    'id' => $orderId,
                    'status' => 'received',
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'shipping_total' => $shippingTotal,
                    'total' => $total,
                    'payment_method' => $paymentMethod,
                ],
                array_merge($payment, [
                    'idempotency_key' => $idempotencyKey,
                    'items' => array_map(fn (array $item): array => [
                        'sku' => $item['variant']['sku'],
                        'product_title' => $item['variant']['product_title'],
                        'quantity' => $item['quantity'],
                        'total' => $item['total'],
                    ], $orderItems),
                ])
            );
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function formatCheckoutResponse(array $order, array $payment): array
    {
        $resultPayload = $payment['result_payload'] ?? [];
        $instructions = $payment['instructions'] ?? $resultPayload['instructions'] ?? null;
        $reference = $payment['payment_reference'] ?? $resultPayload['payment_reference'] ?? null;

        return [
            'order_id' => (int) $order['id'],
            'status' => (string) $order['status'],
            'subtotal' => (float) $order['subtotal'],
            'discount_total' => (float) $order['discount_total'],
            'shipping_total' => (float) $order['shipping_total'],
            'total' => (float) $order['total'],
            'payment_method' => (string) $order['payment_method'],
            'items' => array_map(fn (array $item): array => [
                'sku' => $item['sku'] ?? '',
                'product_title' => $item['product_title'] ?? '',
                'quantity' => (int) $item['quantity'],
                'total' => (float) $item['total'],
            ], $order['items'] ?? []),
            'payment_status' => (string) ($payment['payment_status'] ?? 'pending'),
            'provider_status' => (string) ($payment['provider_status'] ?? ''),
            'provider' => (string) ($payment['provider'] ?? ''),
            'payment_provider' => (string) ($payment['provider'] ?? ''),
            'payment_provider_status' => (string) ($payment['provider_status'] ?? ''),
            'provider_transaction_id' => (string) ($payment['provider_transaction_id'] ?? ''),
            'payment_reference' => (string) $reference,
            'payment_instructions' => $instructions,
            'idempotency_key' => (string) ($payment['idempotency_key'] ?? ''),
        ];
    }

    private function coupon(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        $statement = $this->pdo->prepare(
            <<<'SQL'
            SELECT *
            FROM coupons
            WHERE code = :code
            AND active = true
            AND (starts_at IS NULL OR starts_at <= now())
            AND (ends_at IS NULL OR ends_at >= now())
            SQL
        );
        $statement->execute(['code' => $code]);
        $coupon = $statement->fetch();

        if (!$coupon) {
            throw new InvalidArgumentException('Coupon is invalid or expired.');
        }

        $coupon['amount'] = (float) $coupon['amount'];

        return $coupon;
    }

    private function shippingTotal(string $shippingMethod, array $items): float
    {
        $allDigital = array_reduce(
            $items,
            fn (bool $carry, array $item): bool => $carry && (bool) $item['variant']['is_digital'],
            true
        );

        if ($allDigital) {
            return 0.0;
        }

        return match ($shippingMethod) {
            'express' => 29.90,
            'pickup' => 0.0,
            default => 18.90,
        };
    }

    private function discountTotal(?array $coupon, float $subtotal, float $shippingTotal): float
    {
        if ($coupon === null) {
            return 0.0;
        }

        return match ($coupon['discount_type']) {
            'percentage' => round($subtotal * ((float) $coupon['amount'] / 100), 2),
            'fixed' => min($subtotal, (float) $coupon['amount']),
            'free_shipping' => $shippingTotal,
            default => 0.0,
        };
    }

    private function upsertCustomer(string $email, string $name, float $orderTotal): int
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO customers (email, name, ltv)
            VALUES (:email, :name, :ltv)
            ON CONFLICT (email)
            DO UPDATE SET name = EXCLUDED.name, ltv = customers.ltv + EXCLUDED.ltv
            RETURNING id
            SQL
        );
        $statement->execute(['email' => $email, 'name' => $name, 'ltv' => $orderTotal]);

        return (int) $statement->fetchColumn();
    }

    private function recordTrafficCheckout(string $source, float $revenue): void
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO traffic_events (source, medium, campaign, visits, carts, checkouts, revenue, event_date)
            VALUES (:source, 'checkout', 'checkout-mvp', 0, 0, 1, :revenue, current_date)
            SQL
        );
        $statement->execute([
            'source' => $source !== '' ? $source : 'direct',
            'revenue' => $revenue,
        ]);
    }

    private function markRecoverableCartConverted(string $email): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE carts SET status = 'converted', updated_at = now() WHERE email = :email AND status IN ('active', 'abandoned')"
        );
        $statement->execute(['email' => $email]);
    }
}
