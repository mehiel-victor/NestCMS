# Project State

## Decisions

- Use a lean custom PHP API with PDO instead of a full framework to keep the MVP small and easy to run.
- Use PostgreSQL migrations and seed data in `backend/database`.
- Treat payment, shipping, fiscal, and email providers as integration stubs in the MVP.
- Use Chakra UI Vue through `@chakra-ui/vue-next`, currently documented as Vue 3 support, with SCSS for domain-specific layout polish.
- Publish the MVP to the public GitHub repository `mehiel-victor/NestCMS`, because the local GitHub CLI is authenticated as `mehiel-victor`.
- Introduce a real payments milestone with an internal `PaymentProvider` abstraction before direct frontend-gateway integration.
- Persist immutable payment audit artifacts (`provider_status`, `provider_event_id`, `webhook_payload`, correlation metadata) on every webhook/payment mutation.
- Preserve `/api/orders` response contract as the stable frontend integration surface.
- Added issue-1 execution branch support for multiple provider adapters (`mock`, `stripe`, `mercado_pago`, `pagar_me`) with deterministic fallback configuration through `PAYMENT_PROVIDER` and `PAYMENT_PROVIDER_FALLBACK`.

## Blockers

- Docker is not installed on this machine, so `docker compose up --build` could not be executed locally in this session.
- Payment provider certification/PCI posture, provider account onboarding, and webhook secrets are external dependencies for end-to-end production validation.

## Deferred Ideas

- Add queue-backed abandoned cart email dispatch.
- Add async media processing.
- Add periodic provider reconciliation job for unresolved pending payments (after webhook-driven path is stable).
- Add tax invoice status machine.

## Preferences

- Keep implementation docs and commands in Portuguese-friendly wording for the user.
