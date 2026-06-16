# Tasks: MVP E-Commerce

## T1 Project Shell

- Status: Complete
- Requirements: REQ-OPS-001, AC-006
- Done when: Docker Compose, README, environment files, and project folders exist.
- Verification: File inspection and documented setup commands.

## T2 Backend Schema And Seed

- Status: Complete
- Requirements: REQ-CAT-001..004, REQ-INV-001..003, REQ-CHK-001..003, REQ-ORD-001..002, REQ-MKT-001..002, REQ-ANL-001
- Done when: PostgreSQL schema and demo data cover products, variants, inventory, carts, orders, coupons, email events, and traffic.
- Verification: SQL files are complete and referenced by Docker init.

## T3 Backend API

- Status: Complete
- Requirements: All MVP requirements
- Done when: REST endpoints return JSON and mutate catalog, inventory, checkout, order, and marketing state.
- Verification: Static review of routes and service behavior.

## T4 Frontend App

- Status: Complete
- Requirements: AC-001..005
- Done when: Nuxt pages and components render dashboard, catalog, checkout, and recovery workflows.
- Verification: Static review of component references and API client usage.

## T5 Publish Public GitHub Repository

- Status: Blocked
- Requirements: User request
- Done when: `NestCMS` exists as a public GitHub repository with the MVP pushed.
- Verification: Repository URL resolves publicly.
- Blocker: Current session cannot execute shell commands, and available GitHub tools do not expose repository creation.

