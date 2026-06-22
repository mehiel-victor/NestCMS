# PRD: NestCMS Frontend-Only Mock Demo

## Lifecycle Status

- Discovery: complete for scope correction.
- Prototyping: complete through the current Nuxt demo UI.
- Handoff: this PRD is the source of truth for the frontend-only direction.
- Development: scope is locked to frontend-only mock/demo behavior.

## Situation

NestCMS is a portfolio-grade commerce operations demo for a DTC founder persona, Maria. The current repository already has a Nuxt interface with dashboard, catalog, checkout, order, recovery, analytics, demo login, and seeded browser data.

The intended product direction is now explicit: the app must run as a frontend-only experience using mock data. It should demonstrate product thinking, UI architecture, state transitions, and commerce workflow fluency without requiring a hosted API, database, real payment gateway, email provider, shipping provider, or authentication service.

## Complication

The previous documentation mixed two incompatible stories:

- a full-stack commerce CMS with PHP, PostgreSQL, real payment orchestration, webhooks, refunds, audit trails, and Docker runtime;
- a public portfolio demo that runs from Vercel with in-browser seeded data.

That mismatch makes the product look unfinished instead of intentionally scoped. A reviewer may try to evaluate real payments, real email recovery, persistent inventory, or backend APIs and conclude the app is broken, even if the actual goal is a self-contained demo.

## Action

Reframe NestCMS as a frontend-only mock demo and make every user-facing flow complete inside the browser:

- demo profile access instead of production authentication;
- local/mock catalog creation and listing;
- mock checkout that creates an order, updates local stock, and shows simulated payment state;
- mock abandoned-cart recovery that changes visible local state;
- mock order status, refund, and chargeback actions;
- analytics derived from seeded and locally mutated mock state;
- clear copy that says "simulate" where no external side effect happens.

## Result

Success means a portfolio reviewer can open the app, enter demo mode, exercise the main commerce workflows, and understand that all data is simulated by design. The app should feel coherent and complete as a prototype, not like a production system missing integrations.

## Product Goal

Deliver a polished frontend-only commerce operations demo that proves the core NestCMS workflows without backend dependencies.

## Primary Persona

Maria is a DTC founder reviewing whether NestCMS could support her commerce operations. In the demo, she is not trying to run real operations; she is evaluating whether the product model, workflow design, and interface make sense.

Secondary persona: a technical recruiter, hiring manager, or engineering peer reviewing the project as portfolio evidence.

## Scope

### In Scope

- Nuxt frontend application.
- Browser-only demo data and state.
- Seeded demo profiles: admin, operator, finance.
- Dashboard KPIs, order list, stock alerts, recovery queue, and analytics.
- Product creation in mock state.
- Checkout simulation with local order creation and stock decrement.
- Simulated PIX/card/boleto status and instructions.
- Simulated recovery send state.
- Simulated order advancement, chargeback, and refund state changes.
- Responsive desktop/mobile UI.
- Clear empty, loading, success, and error states.
- Documentation that states there is no real backend requirement for the demo.

### Out of Scope

- Real payment capture.
- Real PIX QR code generation.
- Real card tokenization or PCI scope.
- Real webhooks.
- Real email delivery.
- Real auth, password recovery, magic-link delivery, or SSO.
- Persistent database.
- Docker as a required demo runtime.
- Shipping quotes, fulfillment, labels, fiscal documents, ERP, CRM, or warehouse integrations.
- Multi-tenant production CMS behavior.

## User Stories

1. As a portfolio reviewer, I want to enter the app without backend setup so I can evaluate the product quickly.
2. As Maria, I want to see seeded commerce data so I understand the operational dashboard immediately.
3. As Maria, I want to create a demo product so I can see how catalog state changes in the UI.
4. As Maria, I want to simulate checkout so I can see order creation, stock decrement, and payment status without a real gateway.
5. As Maria, I want to simulate recovery send so I can understand abandoned-cart workflow intent.
6. As an operator, I want to advance order status so I can see the operational lifecycle.
7. As finance, I want to simulate refund or chargeback state so I can evaluate financial status separation.
8. As a reviewer, I want visible copy to distinguish simulated actions from real side effects.

## Functional Requirements

- REQ-FE-001: The app must run from a static/frontend deployment without a hosted backend.
- REQ-FE-002: Demo access must be available through seeded profiles or a profile picker.
- REQ-FE-003: Mock data must include products, variants, stock, orders, carts, and analytics.
- REQ-FE-004: Product creation must update the visible catalog state in the same browser session.
- REQ-FE-005: Checkout simulation must create a visible order and decrement mock stock for physical products.
- REQ-FE-006: Checkout simulation must show a simulated payment status and instructions for PIX/card/boleto.
- REQ-FE-007: Public checkout must only offer products marked as published in mock state.
- REQ-FE-008: Abandoned-cart recovery must update visible mock recovery status and timestamp.
- REQ-FE-009: Order status advancement must update visible mock order state.
- REQ-FE-010: Simulated refund and chargeback actions must update visible financial state without changing operational state unless explicitly selected.
- REQ-FE-011: Analytics must be recalculated or clearly labeled when based on seeded sample data.
- REQ-FE-012: UI copy must not imply real email, real payment, real fulfillment, or persistent backend effects.
- REQ-FE-013: Local browser/session reset behavior must be predictable and documented.
- REQ-FE-014: The app must include loading, empty, success, and error states for all mock workflows.
- REQ-FE-015: Mobile and desktop layouts must remain usable for the main demo path.

## Non-Functional Requirements

- NFR-001: No secret keys or private backend URLs are required for the demo.
- NFR-002: The app must degrade gracefully if browser storage is unavailable.
- NFR-003: Mock state should be deterministic enough for repeatable reviews.
- NFR-004: The main demo path should be understandable within five minutes.
- NFR-005: TypeScript contracts should keep mock entities consistent across pages and components.

## Data Requirements

Mock data must cover:

- at least three products;
- physical, digital, and bundle product types;
- published, draft, and scheduled visibility states;
- variants with SKU, price, stock, and low-stock threshold;
- at least two orders with different operational and payment states;
- at least two abandoned carts;
- traffic sources and daily revenue series;
- admin, operator, and finance demo profiles.

## Business Rules

- Browser mock state is the source of truth for the frontend-only demo.
- Published products may appear in checkout; draft and scheduled products may appear only in admin/catalog contexts.
- Simulated checkout must never collect real card data.
- Simulated payment methods are labels and state transitions only.
- Simulated recovery must never send an actual email.
- Refund and chargeback are financial state demonstrations, not gateway operations.
- Demo profiles are not production accounts.
- A page refresh may reset state unless local persistence is explicitly implemented and documented.

## UI and State Requirements

Every major flow must include:

- initial seeded state;
- loading state;
- empty state;
- success state;
- error state;
- copy that clarifies simulated behavior.

Specific wording should prefer:

- "Simular checkout" over "Cobrar";
- "Simular envio" over "Enviar e-mail";
- "Pagamento simulado" over "Pagamento aprovado" when no real provider exists;
- "Sessão demo" over "Conta segura" when running without backend auth.

## Acceptance Criteria

- AC-001: A reviewer can open the app from a frontend-only deployment and complete the main demo without backend setup.
- AC-002: Demo login/profile selection creates a usable local session.
- AC-003: Creating a product adds it to catalog state without a network dependency.
- AC-004: Simulated checkout creates a new order, updates visible stock, and shows simulated payment details.
- AC-005: Draft and scheduled products are not available in public checkout.
- AC-006: Simulated recovery changes visible cart recovery status.
- AC-007: Order advancement updates operational state.
- AC-008: Refund/chargeback simulation updates financial state and preserves operational state unless the user separately advances it.
- AC-009: The UI never implies that real payment, email, shipping, tax, or persistence occurred.
- AC-010: README and product docs describe the frontend-only/mock constraint clearly.
- AC-011: Typecheck/build pass for the frontend.
- AC-012: Manual QA covers desktop and mobile for dashboard, catalog, checkout, orders, and recovery.

## Analytics and Tracking

No production analytics integration is required. If event tracking is added later, it must be mock/local by default and must not send visitor data without a separate validated PRD.

Recommended local demo events:

- `demo_profile_selected`
- `demo_product_created`
- `demo_checkout_simulated`
- `demo_recovery_simulated`
- `demo_order_advanced`
- `demo_financial_state_simulated`

## Open Questions

- Should future reviewer feedback create a follow-up PRD or be handled as small copy/UX patches?
