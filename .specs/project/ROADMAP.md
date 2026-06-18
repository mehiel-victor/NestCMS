# NestCMS Roadmap

## MVP 0.1

- Product catalog API and admin UI.
- Inventory ledger and low-stock visibility.
- Guest checkout API and storefront checkout UI.
- Order dashboard with status updates.
- Abandoned cart recovery queue.
- Revenue, order, product, and conversion analytics.
- Dockerized PHP + PostgreSQL backend and Nuxt frontend.
- Real checkout payment orchestration foundation:
  - Introduce internal `PaymentProvider` abstraction and payment contract.
  - Add transaction/intent creation for PIX, card, and boleto.
  - Implement asynchronous webhook updates with event trail and deduplication.
  - Add partial and full refund persistence with explicit financial status.
- Explicitly separate operational status (`received` ... `returned`) from financial payment status.

## Post-MVP

- Provider rollout by priority (Pix/Card/Boleto):
  - Phase 1: default provider selected by environment configuration (e.g., Stripe or Mercado Pago).
  - Phase 2: secure fallback/switchover to secondary provider.
- Include chargeback handling and manual review without blocking operational order flow.
- Add panel reporting for orders pending payment beyond configured thresholds.
- Correios, Melhor Envio, and Kangu shipping quotes.
- Fiscal integrations for NF-e, Bling, and Tiny ERP.
- Marketing provider sync for Klaviyo and Mailchimp.
- Loyalty, birthday discounts, and richer campaign flows.
- Media storage and CDN-backed product assets.
- Role-based access control and multi-tenant stores.
