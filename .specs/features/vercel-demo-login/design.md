# Design: Vercel Demo Login

## Approach

Add a frontend-only demo API adapter used only when all of these are true:

- The code is running in the browser.
- The current hostname is not `localhost` or `127.0.0.1`.
- `runtimeConfig.public.apiBase` resolves to `localhost` or `127.0.0.1`.

This keeps production API deployments possible: setting `NUXT_PUBLIC_API_BASE` to a public backend disables demo mode automatically.

## Components

- `frontend/composables/useDemoNestApi.ts`: in-browser demo data, seeded credential validation, local session payloads, and no-op mutations.
- `frontend/composables/useNestApi.ts`: delegates to the demo adapter only under the guard above.

## Error States

- Wrong seeded credentials throw a clear credential error.
- Demo refresh without a demo refresh token clears the session through existing middleware behavior.

## Empty and Loading States

- Existing page loading and empty states remain unchanged.
- Demo data is seeded to avoid empty first-run dashboards.

## API Contracts

The demo adapter mirrors the existing high-level frontend API methods:

- `login`
- `refresh`
- `me`
- `logout`
- `dashboard`
- `products`
- `publicProducts`
- `createProduct`
- `orders`
- `updateOrderStatus`
- `createPaymentRefund`
- `submitPaymentReview`
- `pendingPaymentReport`
- `abandonedCarts`
- `sendRecovery`
- `checkout`
- `revenue`

## Business Rules

- Demo credentials are public seed credentials, not production authentication.
- Demo mutations update only in-memory Nuxt state for the current browser session.
- Real API behavior remains preferred whenever a public `NUXT_PUBLIC_API_BASE` exists.
