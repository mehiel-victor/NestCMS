# Feature Spec: Vercel Demo Login

## Discovery

- Evidence: the production Vercel deploy renders the seeded login screen, but submitting `admin@nestcms.test` calls `http://localhost:8080/api/auth/login` and fails with `<no response> Failed to fetch`.
- Cause: the Vercel project has no `NUXT_PUBLIC_API_BASE`, so the Nuxt runtime falls back to `http://localhost:8080`, which is not reachable from a public visitor's browser.

## Scope

Allow the frontend-only Vercel deployment to demonstrate seeded access without a hosted backend API.

## Requirements

- REQ-DEMO-001: When the app is opened from a non-localhost host and `apiBase` is still localhost, authentication must use an in-browser demo mode.
- REQ-DEMO-002: Demo mode must accept only the documented seeded credentials.
- REQ-DEMO-003: Demo mode must populate a local session compatible with the existing auth middleware.
- REQ-DEMO-004: Demo mode must return seeded dashboard, catalog, checkout, order, marketing, and analytics data so the panel remains usable after login.
- REQ-DEMO-005: Local development must continue using the real API when opened on localhost.
- REQ-DEMO-006: If `NUXT_PUBLIC_API_BASE` is configured to a public API, the frontend must use the real API instead of demo mode.

## Acceptance Criteria

- AC-001: On `nestcms.vercel.app`, `admin@nestcms.test` / `Admin@123` logs in without contacting `localhost:8080`.
- AC-002: Wrong credentials still show a login error.
- AC-003: After login, the dashboard renders seeded KPIs, orders, stock, recovery queue, and analytics.
- AC-004: Logout clears the local session and returns to `/login`.
- AC-005: Running locally at `localhost` keeps calling `http://localhost:8080`.
