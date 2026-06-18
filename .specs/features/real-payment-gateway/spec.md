# Feature Spec: Real Payment Gateway Integration (PIX/Card/Boleto)

## Scope

Substituir o fluxo simulado atual de pagamento por uma camada de provedores reais com abstração interna, sem alterar a experiência atual de checkout/painel.

## Requirements

- REQ-PAY-001: The visitor checkout experience keeps existing fields and flow when choosing payment method.
- REQ-PAY-002: Checkout can expose payment instructions/reference after order creation so the visitor can complete payment outside the panel.
- REQ-PAY-003: The visitor can see clear payment status (`pending`, `processing`, `approved`, `failed`, `refunded`, `chargeback`) after checkout and in follow-up polling.
- REQ-PAY-004: Checkout finalization is idempotent and resubmission does not create duplicate charges.
- REQ-PAY-005: A visitor can resume payment from a persisted charge reference without starting a new checkout flow.
- REQ-PAY-006: Operational order status (`received`, `processing`, `shipped`, `delivered`, `returned`) remains independent from financial status.
- REQ-PAY-007: Admin panel displays `payment_status`, latest `provider_status`, and actionable risk signals alongside order data.
- REQ-PAY-008: Provider webhook events update order financial state asynchronously and deterministically.
- REQ-PAY-009: The admin can record partial refunds for paid orders.
- REQ-PAY-010: The admin can record full refunds for paid orders.
- REQ-PAY-011: Every transaction persists `provider`, `transaction_id`, `amount`, `currency`, `payment_method`, and `provider_status`.
- REQ-PAY-012: Payment lifecycle events persist per-step `provider_status` values for reconciliation.
- REQ-PAY-013: Raw webhook payloads are persisted for audit/forensics.
- REQ-PAY-014: Webhook processing supports approved, failed, canceled, and chargeback-like event families without bypassing safeguards.
- REQ-PAY-015: Charge creation is idempotent by `idempotency_key` and customer/cart hash.
- REQ-PAY-016: Webhook processing is idempotent by `provider_event_id` and ignores duplicates safely.
- REQ-PAY-017: Duplicate webhook events do not mutate state multiple times and remain auditable.
- REQ-PAY-018: Support manual payment review for dispute situations without blocking normal admin workflows.
- REQ-PAY-019: Implement and inject a `PaymentProvider` interface to allow provider swaps.
- REQ-PAY-020: Keep `/api/orders` contract stable for existing frontend consumers.
- REQ-PAY-021: Webhook errors and critical mutations must emit structured logs with correlation/request identifiers.
- REQ-PAY-022: Admin can report and filter orders pending payment longer than configured SLA.
- REQ-PAY-023: Chargebacks mark risk state but do not alter operational order status.
- REQ-PAY-024: API-level test suite validates provider seams, webhooks, and idempotency behaviors.
- REQ-PAY-025: Roll out providers incrementally, with configurable primary and fallback selection.

## Acceptance Criteria

- AC-001: A visitor can complete checkout with PIX/card/boleto through existing UI components and receive provider-specific instructions.
- AC-002: Double-clicking checkout submit does not create duplicate charges.
- AC-003: Reopening checkout for the same pending charge resumes using the same payment reference.
- AC-004: Approval/rejection webhook events update payment status without manual refresh only via existing poll/update flow.
- AC-005: Duplicate webhook callbacks with same `provider_event_id` are ignored and logged.
- AC-006: Partial and total refunds are possible only for eligible paid orders and create immutable refund records.
- AC-007: Admin sees a clear distinction between operational status and financial status.
- AC-008: Webhook payload and audit logs contain enough data to reconstruct event sequence and troubleshooting context.
- AC-009: `/api/orders` response shape for storefront and panel clients remains backward compatible except for additive fields.
- AC-010: API-level tests prove gateway abstraction and webhook contract behavior against at least one adapter and one fake.
