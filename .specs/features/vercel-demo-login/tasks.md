# Tasks: Vercel Demo Login

## T1 Demo Adapter

- Status: Complete
- Requirements: REQ-DEMO-001..004
- Done when: A composable returns seeded auth, dashboard, catalog, checkout, order, marketing, and analytics data without network calls.

## T2 API Delegation Guard

- Status: Complete
- Requirements: REQ-DEMO-005, REQ-DEMO-006
- Done when: `useNestApi` uses demo mode only for non-localhost browsers with localhost API configuration.

## T3 Verification

- Status: Complete
- Requirements: AC-001..005
- Done when: Typecheck/build pass and the Vercel deployment no longer requires Vercel SSO protection. Final browser verification is done after deployment.
