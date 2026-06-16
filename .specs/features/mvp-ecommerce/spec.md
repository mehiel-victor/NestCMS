# Feature Spec: MVP E-Commerce

## Scope

Build the first runnable NestCMS MVP for Maria's DTC commerce workflow.

## Requirements

- REQ-CAT-001: Merchants can create products with title, description, type, visibility, price, category, and collection fields.
- REQ-CAT-002: Products support multiple variants with SKU, option labels, price, stock, and low-stock threshold.
- REQ-CAT-003: Products can be grouped by hierarchical categories and collections.
- REQ-CAT-004: Products expose media metadata for images, videos, and documents.
- REQ-INV-001: Inventory is tracked per variant SKU and warehouse.
- REQ-INV-002: Inventory movements are recorded with reason, quantity delta, and timestamp.
- REQ-INV-003: Low-stock items are visible in the dashboard.
- REQ-CHK-001: Customers can complete a guest checkout without creating an account.
- REQ-CHK-002: Checkout accepts payment method selections for credit card, boleto, PIX, Apple Pay, and Google Pay as simulated methods.
- REQ-CHK-003: Checkout supports coupon codes, shipping option selection, upsell, and cross-sell metadata.
- REQ-ORD-001: Orders use the status flow received, processing, shipped, delivered, returned.
- REQ-ORD-002: Merchants can update order status and view customer/order history.
- REQ-MKT-001: Abandoned carts older than one hour are available for recovery.
- REQ-MKT-002: Recovery email sending is represented by a simulated API endpoint.
- REQ-ANL-001: Dashboard analytics include revenue, order count, average order value, conversion funnel, best sellers, high-margin products, customer LTV, and UTM traffic.
- REQ-OPS-001: The project runs locally with Docker Compose using PHP and PostgreSQL for the backend, Nuxt/Vue 3/Vite/Chakra UI Vue/SCSS for the frontend.

## Acceptance Criteria

- AC-001: A seeded store dashboard renders product, order, inventory, marketing, and analytics data.
- AC-002: Creating a product with variants through the API persists product and variant records.
- AC-003: Guest checkout creates an order and decrements variant inventory.
- AC-004: Abandoned carts older than one hour appear in the recovery endpoint and UI.
- AC-005: Revenue analytics are returned by the API without extra configuration.
- AC-006: Setup instructions are documented in `README.md`.

