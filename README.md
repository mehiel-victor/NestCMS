# NestCMS

NestCMS is an open-source MVP for a DTC e-commerce CMS. It gives a founder like Maria one place to manage catalog, inventory, guest checkout, orders, abandoned carts, and revenue analytics.

## Stack

- Backend: PHP 8.3, PDO, PostgreSQL
- Frontend: Nuxt 3, Vue 3, Vite, Chakra UI Vue, SCSS
- Runtime: Docker Compose

## MVP Scope

- Product catalog with variants, media metadata, categories, collections, custom fields, and visibility.
- Inventory by SKU and warehouse with low-stock alerts and movement history.
- Guest checkout with provider-backed payment orchestration (PIX/card/boleto), coupons, shipping, upsell/cross-sell metadata, and stock decrement.
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

Seeded access credentials:

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Admin | `admin@nestcms.test` | `Admin@123` |
| Operador | `operator@nestcms.test` | `Operator@123` |
| Financeiro | `finance@nestcms.test` | `Finance@123` |

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

Payment gateway behavior is driven by `.env`:

- `PAYMENT_PROVIDER`: active provider alias (`mock`, `stripe`, `mercado_pago`, `pagar_me`).
- `PAYMENT_PROVIDER_FALLBACK`: ordered fallback providers for non-webhook operations.
- `STRIPE_WEBHOOK_SECRET`, `MERCADO_PAGO_WEBHOOK_SECRET`, `PAGARME_WEBHOOK_SECRET`: optional webhook secrets.

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

## CI/CD

GitHub Actions deploys the Nuxt frontend in `frontend/` to Vercel.

- Pull requests create preview deployments.
- Pushes to `main` create production deployments.
- The workflow uses Vercel CLI `54.9.1` with `vercel build` and `vercel deploy --prebuilt`.

Required GitHub Actions secrets are already expected by `.github/workflows/vercel.yml`:

- `VERCEL_TOKEN`
- `VERCEL_ORG_ID`
- `VERCEL_PROJECT_ID`

Set `NUXT_PUBLIC_API_BASE` in the Vercel project environment when the production API is hosted. Without that, the frontend falls back to `http://localhost:8080`.

## Notes

Payment, shipping, fiscal, and email providers remain pluggable in the MVP. The default payment provider is still mocked for local development, but Stripe/Mercado Pago/Pagar.me adapters are now available for staged rollout without changing checkout/dashboard UX.

Additional planning material:

- [Discovery de Novas Integracoes](docs/discovery-novas-integracoes.md)
- [Posicionamento de Competencias Complementares](docs/posicionamento-competencias-complementares.md)
