# Project State

## Decisions

- Use a lean custom PHP API with PDO instead of a full framework to keep the MVP small and easy to run.
- Use PostgreSQL migrations and seed data in `backend/database`.
- Treat payment, shipping, fiscal, and email providers as integration stubs in the MVP.
- Use Chakra UI Vue through `@chakra-ui/vue-next`, currently documented as Vue 3 support, with SCSS for domain-specific layout polish.

## Blockers

- Local shell execution is currently unavailable in this Codex session, so automated installs, tests, `git`, and `gh` commands may need a later retry or user-side execution.
- GitHub connector exposes repository file operations but not repository creation, so creating a brand-new public GitHub repository requires `gh`, GitHub web UI, or an additional repository-creation tool.

## Deferred Ideas

- Add queue-backed abandoned cart email dispatch.
- Add async media processing.
- Add real payment webhooks and order reconciliation.
- Add tax invoice status machine.

## Preferences

- Keep implementation docs and commands in Portuguese-friendly wording for the user.

