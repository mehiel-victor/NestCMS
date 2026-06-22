# PRD: Demo Positioning and Copy

## Purpose

Make the frontend-only demo honest, legible, and credible to real reviewers.

## Positioning

NestCMS is a frontend-only commerce operations prototype. It demonstrates how a DTC commerce CMS could feel, not a production commerce backend.

## Required Messaging

The product and documentation must clearly state:

- Data is seeded and simulated.
- Actions update browser/session state.
- Payments are simulated.
- Recovery emails are simulated.
- No real order, email, payment, shipment, invoice, or external integration is created.

## Copy Rules

Use:

- "Demo"
- "Mock"
- "Simulado"
- "Sessão demo"
- "Simular checkout"
- "Simular envio"

Avoid:

- "Cobrar"
- "Enviar e-mail" without "simulado"
- "Gateway real"
- "Webhook recebido"
- "Conta segura" when there is no backend auth
- "Persistido" unless local/session storage is actually used

## Acceptance Criteria

- README, PRD, and UI copy do not promise full-stack behavior.
- The first demo screen makes the frontend-only scope clear without turning into a marketing page.
- Buttons with no external side effect use simulated wording.
- Reviewer can explain the scope correctly after one pass through the app.

## Non-Goals

- Hide the mock nature of the app.
- Pretend the app has production integrations.
- Present backend code as required runtime for the public demo.
