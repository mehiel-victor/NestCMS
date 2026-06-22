# PRD: Frontend-Only Commerce Demo

## Purpose

This PRD defines the complete product behavior for NestCMS as a browser-only mock commerce demo.

## Problem

Reviewers need to understand NestCMS quickly without installing Docker, configuring PostgreSQL, or connecting external services. The previous full-stack framing created false expectations around real payments, email, webhooks, and persistence.

## Target Outcome

The demo should communicate: "This is a polished product prototype with realistic commerce workflows, powered by mock state."

## Core Demo Path

1. Open demo.
2. Select or enter a seeded demo profile.
3. Land on dashboard.
4. Review KPIs, orders, stock alerts, and abandoned carts.
5. Create a product.
6. Simulate checkout.
7. Confirm the new order appears in the dashboard/order table.
8. Simulate recovery or financial state change.

## Requirements

- Demo must not require backend availability.
- All main pages must use mock state.
- Mutations must update local UI state immediately.
- Simulated actions must be labeled as simulated.
- Public checkout must use only published mock products.
- Errors should be recoverable and written in product language.
- Empty states should explain what is absent, not how the code works.

## Acceptance Criteria

- The app works from a static deployment.
- A reviewer can complete the core demo path in under five minutes.
- No flow requires a real credential, real payment method, real email, or real API.
- Simulated state changes are visible without refresh.
- A refresh behavior is documented: reset or restore, but never ambiguous.

## Out of Scope

- Real backend calls.
- Real payment gateway adapters.
- Real webhook ingestion.
- Real email sending.
- Persistent multi-user state.
- Production authentication.
