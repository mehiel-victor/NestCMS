# NestCMS

## Vision

NestCMS is an open-source commerce CMS for direct-to-consumer founders who need a customizable store, catalog, inventory, checkout, marketing recovery, and revenue analytics in one product.

## Primary Persona

Maria, a 34-year-old DTC founder who sells physical and digital products and wants to reduce long-term platform costs while gaining more control over her store experience.

## MVP Goal

Deliver a runnable commerce operations MVP that demonstrates the core merchant workflow:

- Manage products, variants, categories, and visibility.
- Track inventory by SKU and warehouse.
- Create guest checkout orders with coupons and payment method selection.
- View order status, abandoned carts, and revenue analytics.
- Run locally with PHP, PostgreSQL, Nuxt, Vue 3, Vite, Chakra UI Vue, and SCSS.
- Evolve checkout from simulated to real payment capture through an internal gateway layer that keeps the storefront and admin UX stable.

## Non-Goals For MVP

- Real PCI-DSS payment processing details and direct card tokenization are explicitly handled by the payment provider abstraction.
- Live shipping label or tax invoice emission.
- Production-grade email delivery.
- Multi-site or multi-language support.
- Full ERP, marketplace, or course-platform scope.
- Full split between accounting and payment reconciliation; this will be phased after payment events and audit trail are in place.

## Success Criteria

- The repository can be cloned and started with Docker Compose.
- Maria can add and publish a product with variants.
- A guest checkout flow can create an order without account creation.
- Abandoned carts older than one hour can be listed for recovery.
- Dashboard revenue metrics are available without extra setup.
- A checkout can create and track real payment intents (PIX/card/boleto) through the internal provider layer.
- Payment events from webhooks update orders asynchronously with idempotent, auditable behavior.
