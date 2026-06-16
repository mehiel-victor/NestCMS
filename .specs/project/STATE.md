# Project State

## Decisions

- Use a lean custom PHP API with PDO instead of a full framework to keep the MVP small and easy to run.
- Use PostgreSQL migrations and seed data in `backend/database`.
- Treat payment, shipping, fiscal, and email providers as integration stubs in the MVP.
- Use Chakra UI Vue through `@chakra-ui/vue-next`, currently documented as Vue 3 support, with SCSS for domain-specific layout polish.
- Publish the MVP to the public GitHub repository `mehiel-victor/NestCMS`, because the local GitHub CLI is authenticated as `mehiel-victor`.

## Blockers

- Docker is not installed on this machine, so `docker compose up --build` could not be executed locally in this session.

## Deferred Ideas

- Add queue-backed abandoned cart email dispatch.
- Add async media processing.
- Add real payment webhooks and order reconciliation.
- Add tax invoice status machine.

## Preferences

- Keep implementation docs and commands in Portuguese-friendly wording for the user.
