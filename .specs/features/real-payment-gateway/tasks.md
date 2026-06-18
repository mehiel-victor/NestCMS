# Tasks: Real Payment Gateway Integration

## T1 Provider Abstraction and Contract

- Status: Not started
- Requirements: REQ-PAY-018, REQ-PAY-025
- Depends on: none
- Reuses: Existing service/repository layer conventions from `mvp-ecommerce`.
- Done when: `PaymentProvider` interface and adapter registry are defined and injectable; a sandbox adapter exists for local/dev.
- Verification:
  - New interfaces/adapters compile with existing DI/autoload patterns.
  - Unit seam test proves provider can be swapped without changing checkout endpoints.
  - Basic documentation captures provider selection and rollback behavior.

## T2 Schema and Persistence for Financial Trail

- Status: Not started
- Requirements: REQ-PAY-011, REQ-PAY-012, REQ-PAY-013, REQ-PAY-016, REQ-PAY-021, REQ-PAY-023, REQ-PAY-024
- Depends on: T1
- Reuses: `backend/database/migrations` and existing transactional style.
- Done when:
  - New tables for transactions, events, refunds, idempotency keys, and manual reviews are added.
  - Existing order schema supports decoupled operational and financial status.
- Verification:
  - Migration files include indexes for `order_id`, `provider_event_id`, and `idempotency_key`.
  - Basic seed and test fixtures exercise event retention and idempotency scenarios.

## T3 Checkout Idempotent Charge Creation

- Status: Not started
- Requirements: REQ-PAY-001, REQ-PAY-002, REQ-PAY-004, REQ-PAY-005, REQ-PAY-006, REQ-PAY-011, REQ-PAY-015
- Depends on: T1, T2
- Reuses: Existing `/api/checkout` request pipeline and carts.
- Done when:
  - Checkout keeps current UX and returns a persisted payment reference/instruction.
  - Duplicate submits with same `idempotency_key` never create duplicated provider transactions.
- Verification:
  - API test for double submit with same payload asserts single charge and one transaction row.
  - Resume flow test confirms same reference is returned for in-flight payment.

## T4 Webhook Ingestion and State Machine

- Status: Not started
- Requirements: REQ-PAY-003, REQ-PAY-008, REQ-PAY-014, REQ-PAY-016, REQ-PAY-020, REQ-PAY-023
- Depends on: T2, T3
- Reuses: `public/index.php` routing and middleware.
- Done when:
  - Webhook endpoint validates provider signature, normalizes event types, and updates `payment_status`/`provider_status` safely.
  - Chargeback events set risk status only, without changing operational status.
- Verification:
  - Tests for valid/invalid signature, replay events, out-of-order transitions.
  - Duplicate event test proves no state mutation when `provider_event_id` repeats.

## T5 Refund and Chargeback Workflows

- Status: Not started
- Requirements: REQ-PAY-009, REQ-PAY-010, REQ-PAY-022
- Depends on: T4
- Reuses: Existing order model and admin authorization model.
- Done when:
  - Partial and full refund APIs create immutable refund records and provider references.
- Verification:
  - API tests for valid/invalid refund amounts and status preconditions.
  - Chargeback simulation updates risk state without changing operational state.

## T6 Admin Visibility and Manual Review

- Status: Not started
- Requirements: REQ-PAY-007, REQ-PAY-017, REQ-PAY-021
- Depends on: T2, T4
- Reuses: Existing dashboard query patterns and status cards.
- Done when:
  - `/api/orders` includes financial status and audit-ready fields as additive data only.
  - Manual review endpoint writes immutable notes with actor and risk decision.
- Verification:
  - Contract tests validate `/api/orders` shape compatibility.
  - Manual review test confirms status transition remains operationally unchanged.

## T7 Pending Payment Observability

- Status: Not started
- Requirements: REQ-PAY-021, REQ-PAY-024
- Depends on: T2
- Reuses: pending order/report endpoint style.
- Done when:
  - Report endpoint/CLI returns pending payment aging with thresholds and counts.
- Verification:
  - API test with seeded aging data returns correct ordering and threshold boundaries.

## T8 End-to-End API Verification

- Status: Not started
- Requirements: REQ-PAY-001..025
- Depends on: T1, T2, T3, T4, T5, T6, T7
- Reuses: Test setup and fixtures from existing project.
- Done when:
  - A single verification pass covers checkout, webhook, refund, idempotency, and contract stability.
- Verification:
  - Contract tests for `/api/orders` and `/api/checkout` remain backward-compatible.
  - API tests for providers validate at least two event transitions and retry scenarios.
