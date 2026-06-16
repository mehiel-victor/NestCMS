# NestCMS

NestCMS is an open-source MVP for a DTC e-commerce CMS. It gives a founder like Maria one place to manage catalog, inventory, guest checkout, orders, abandoned carts, and revenue analytics.

## Stack

- Backend: PHP 8.3, PDO, PostgreSQL
- Frontend: Nuxt 3, Vue 3, Vite, Chakra UI Vue, SCSS
- Runtime: Docker Compose

## MVP Scope

- Product catalog with variants, media metadata, categories, collections, custom fields, and visibility.
- Inventory by SKU and warehouse with low-stock alerts and movement history.
- Guest checkout with simulated payment methods, coupons, shipping, upsell/cross-sell metadata, and stock decrement.
- Order dashboard with status updates.
- Abandoned cart recovery queue and simulated email events.
- Revenue, funnel, best-seller, margin, LTV, and UTM analytics.

## Run Locally

Docker is required for the full stack because PostgreSQL is provisioned by Compose.

```bash
cp .env.example .env
docker compose up --build
```

Then open:

- Frontend: http://localhost:3000
- Backend health: http://localhost:8080/health

PostgreSQL is seeded automatically on the first run. If you need a clean database:

```bash
docker compose down -v
docker compose up --build
```

## Useful API Calls

```bash
curl http://localhost:8080/api/dashboard
curl http://localhost:8080/api/products
curl http://localhost:8080/api/inventory/low-stock
curl http://localhost:8080/api/orders
curl http://localhost:8080/api/marketing/abandoned-carts
curl http://localhost:8080/api/analytics/revenue
```

Create a product:

```bash
curl -X POST http://localhost:8080/api/products \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Kit Ritual da Manha",
    "description": "Bundle para rotina DTC com produto fisico e guia digital.",
    "product_type": "physical",
    "visibility": "published",
    "price": 149.90,
    "category_id": 1,
    "collection_id": 1,
    "custom_fields": {"material": "algodao organico"},
    "variants": [
      {"sku": "KIT-RITUAL-P", "option_name": "Tamanho", "option_value": "P", "price": 149.90, "stock": 24, "low_stock_threshold": 8}
    ]
  }'
```

Create a checkout order:

```bash
curl -X POST http://localhost:8080/api/checkout \
  -H "Content-Type: application/json" \
  -d '{
    "customer": {"name": "Ana Costa", "email": "ana@example.com"},
    "items": [{"variant_id": 1, "quantity": 1}],
    "payment_method": "pix",
    "shipping_method": "standard",
    "coupon_code": "WELCOME10",
    "utm_source": "instagram"
  }'
```

## GitHub

Public repository: https://github.com/mehiel-victor/NestCMS

## Notes

Payment, shipping, fiscal, and email providers are intentionally stubbed in the MVP. The code is shaped so those integrations can be replaced by real adapters without changing the merchant UI.
