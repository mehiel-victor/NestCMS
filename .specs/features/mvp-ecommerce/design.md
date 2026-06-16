# Feature Design: MVP E-Commerce

## Architecture

NestCMS is split into two applications:

- `backend`: PHP 8.3 REST API using PDO and PostgreSQL.
- `frontend`: Nuxt 3 application using Vue 3, Vite, Chakra UI Vue, and SCSS.

The backend owns persistence and commerce rules. The frontend consumes the API through a small service wrapper and renders merchant-facing operations screens plus a storefront checkout preview.

## Backend Components

- `public/index.php`: API front controller and router.
- `src/Database.php`: PDO connection factory.
- `src/Response.php`: JSON response helpers.
- `src/Repositories/*`: SQL-backed data access.
- `src/Services/*`: Catalog, inventory, checkout, orders, marketing, analytics.
- `database/migrations/001_init.sql`: schema.
- `database/seed.sql`: demo data for Maria.

## Frontend Components

- `pages/index.vue`: merchant dashboard.
- `pages/catalog.vue`: catalog management.
- `pages/checkout.vue`: checkout simulator.
- `components/*`: product, inventory, order, marketing, and analytics panels.
- `plugins/chakra.ts`: Chakra UI Vue plugin registration.
- `assets/scss/main.scss`: responsive app shell and commerce UI styling.

## API Surface

- `GET /health`
- `GET /api/dashboard`
- `GET /api/products`
- `POST /api/products`
- `GET /api/inventory/low-stock`
- `POST /api/checkout`
- `GET /api/orders`
- `PATCH /api/orders/{id}/status`
- `GET /api/marketing/abandoned-carts`
- `POST /api/marketing/abandoned-carts/{id}/send`
- `GET /api/analytics/revenue`

## Data Model

Core tables:

- `products`
- `product_media`
- `product_variants`
- `categories`
- `collections`
- `warehouses`
- `inventory_levels`
- `inventory_movements`
- `customers`
- `carts`
- `cart_items`
- `orders`
- `order_items`
- `coupons`
- `email_events`
- `traffic_events`

## MVP Tradeoffs

- Integrations are represented as configuration-ready stubs.
- Checkout payment methods are simulated and do not touch card data.
- Abandoned cart emails are recorded as events instead of sent through SMTP.
- Analytics are computed from PostgreSQL queries over seed and transaction data.

