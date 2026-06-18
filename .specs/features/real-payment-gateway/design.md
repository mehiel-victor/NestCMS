# Feature Design: Real Payment Gateway Integration

## Architecture

Backend remains the single source of truth for commerce and orchestration. Frontend continues to call internal endpoints only.

### Backend Components

- `public/index.php`: route registration and webhook ingress validation.
- `src/Services/CheckoutService.php`:
  - remains responsible for creating orders;
  - now delegates payment intent creation to `PaymentOrchestrator`.
- `src/Services/PaymentOrchestrator.php`:
  - orchestrates create/payment/confirm/refund/review operations;
  - applies idempotency keys and correlation IDs.
- `src/Services/PaymentProviderRegistry.php`:
  - resolves default/fallback provider by configuration.
- `src/Payments/PaymentProviderInterface.php`:
  - operations: `createCharge`, `fetchStatus`, `createRefund`, `verifyWebhookSignature`.
- `src/Payments/Adapters/*`:
  - provider-specific implementations;
  - adapters for development/test providers to keep local execution deterministic.
- `src/Repositories/PaymentTransactionRepository.php`: persistent transaction and status timeline.
- `src/Repositories/PaymentEventRepository.php`: immutable webhook/event trail.
- `src/Repositories/PaymentRefundRepository.php`: partial/full refund records.
- `src/Services/WebhookIngestService.php`:
  - signature validation;
  - duplicate detection (`provider_event_id`);
  - safe state transitions.
- `src/Services/PaymentAuditService.php`:
  - append-only logs, payload hashes, request correlation.

### Data Model Extensions

- `payment_transactions`
  - `id`, `order_id`, `provider`, `provider_transaction_id`, `payment_method`, `amount`, `currency`,
    `provider_status`, `transaction_status`, `idempotency_key`, `last_error`, timestamps.
- `payment_events`
  - `id`, `transaction_id`, `provider_event_id`, `provider`, `event_type`, `provider_status`, `payload_hash`,
    `webhook_payload`, `processed_at`, `created_at`.
- `payment_refunds`
  - `id`, `transaction_id`, `provider_refund_id`, `amount`, `reason`, `status`, `processed_by`, timestamps.
- `payment_idempotency_keys`
  - `id`, `idempotency_key`, `order_fingerprint`, `status`, `result_payload`, `created_at`.
- `manual_payment_reviews`
  - `id`, `order_id`, `actor`, `decision`, `notes`, `risk_level`, `created_at`.

## API Surface

- Keep existing:
  - `POST /api/checkout` (existing contract preserved).
  - `GET /api/orders` (response extension only, no shape break).
- Add:
  - `POST /api/payments/{orderId}/create` (internal orchestration helper if `/api/checkout` does not remain sole path).
  - `POST /api/payments/webhooks/{provider}` (provider callback endpoint).
  - `POST /api/orders/{orderId}/refunds` (admin-initiated partial/full refund).
  - `GET /api/payments/pending-report` (admin pending-payment report).
  - `POST /api/orders/{orderId}/payment-review` (manual review marker, optional).

## Event Processing

- Checkout path:
  1. Checkout request accepted by `/api/checkout`.
  2. Order persisted with operational status.
  3. `PaymentOrchestrator` creates a charge with provider adapter.
  4. Transaction is persisted and returned with payment instructions and reference.
- Webhook path:
  1. Public webhook endpoint validates signature and provider.
  2. Handler normalizes event and checks `provider_event_id` idempotency.
  3. Raw payload is stored in `payment_events`.
  4. Financial status transitions applied via `WebhookIngestService`.
  5. Audit/service logs updated; webhook response returns acknowledged.

## Safety and Integrity

- `idempotency_key` for charge creation is generated from request context and customer/cart fingerprint.
- Duplicate webhook events are no-ops except for logging/metrics and audit retention.
- Operations are additive to existing order model so current order operations are not blocked.
- Chargebacks only set a risk signal flag and do not alter business status directly.
