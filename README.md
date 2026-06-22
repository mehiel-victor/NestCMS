# NestCMS

NestCMS is a frontend-only portfolio demo for DTC commerce operations. It shows how a founder like Maria could review catalog, inventory, checkout, orders, abandoned carts, and revenue analytics without requiring a hosted API, database, payment gateway, email provider, or shipping provider.

All product data is seeded and simulated in the browser. Actions update local mock state by design.

## Demo Scope

- Demo profile access for admin, operator, and finance roles.
- Catalog creation with SKU, visibility, stock, low-stock threshold, and duplicate SKU validation.
- Public checkout simulation for published products only.
- Simulated PIX, credit card, and boleto payment states and instructions.
- Local order creation, operational status advancement, refund simulation, and chargeback simulation.
- Abandoned-cart recovery simulation with visible local timestamp.
- Dashboard KPIs and analytics derived from seeded and locally mutated mock state.
- Browser-local persistence with an explicit reset action on the demo page.

## Not Production Behavior

NestCMS does not create real orders, payments, e-mails, shipments, invoices, webhooks, accounts, or external integrations. Card numbers and sensitive payment fields are never collected.

The demo stores mock products, orders, and recovery actions in `localStorage` under the current browser. Use the reset action in `/demo` to restore the seeded state.

## Stack

- Nuxt 3
- Vue 3
- TypeScript
- Chakra UI Vue
- SCSS
- Lucide icons

## Run Locally

```bash
cd frontend
npm install
npm run dev
```

Then open the local Nuxt URL printed by the preview server, usually `http://localhost:3000`.

`npm run dev` builds the frontend and serves a local preview. The raw `nuxt dev` command remains available as `npm run dev:nuxt` for framework-level debugging.

No `.env`, Docker, PostgreSQL, or backend process is required for the portfolio demo.

## Demo Access

Open `/demo` and choose a seeded profile, or use the manual login form.

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Admin | `admin@nestcms.test` | `Admin@123` |
| Operador | `operator@nestcms.test` | `Operator@123` |
| Financeiro | `finance@nestcms.test` | `Finance@123` |

## Recommended Review Path

1. Open `/demo` and enter with the admin profile.
2. Review dashboard KPIs, low stock, recent orders, recovery queue, and analytics.
3. Create a product in `/catalog`.
4. Simulate checkout in `/checkout`.
5. Confirm the new order and stock change on the dashboard.
6. Simulate recovery, operational advancement, refund, or chargeback.

## Product Docs

- [Frontend-only PRD](PRD.md)
- [Mock commerce flows](docs/product/prd-mock-commerce-flows.md)
- [Demo positioning and copy](docs/product/prd-demo-positioning.md)
- [Frontend-only commerce demo](docs/product/prd-frontend-only-demo.md)
