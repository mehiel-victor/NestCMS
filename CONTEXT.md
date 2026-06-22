# NestCMS Context

## Portfolio Demo

A public-facing, frontend-only demonstration of NestCMS for portfolio review. It is not a production commerce backend.

## Demo Profile

A seeded local user role used to enter the portfolio demo. Demo profiles represent admin, operator, and finance perspectives without production authentication.

## Browser Mock State

The local source of truth for demo products, orders, abandoned carts, and analytics. Browser mock state is seeded, mutated by simulated actions, persisted locally, and resettable.

## Simulated Checkout

A browser-only action that creates a mock order, updates local stock when relevant, and shows simulated payment instructions. It never creates a real charge.

## Simulated Recovery

A browser-only action that marks an abandoned cart as having received a recovery attempt. It never sends a real e-mail.

## Operational Status

The order fulfillment state shown to operators, such as received, processing, shipped, delivered, or returned.

## Financial Status

The payment-side state shown separately from fulfillment, such as pending, approved, refunded, partially refunded, or chargeback.
