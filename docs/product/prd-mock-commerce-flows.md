# PRD: Mock Commerce Workflows

## Purpose

Define how each commerce workflow should behave when implemented with frontend-only mock state.

## Catalog

### Requirements

- User can create a mock product with title, description, type, visibility, price, SKU, variant, stock, and low-stock threshold.
- Created products appear immediately in the catalog.
- Draft and scheduled products appear in admin catalog views but not public checkout.
- Duplicate SKU should produce a friendly mock validation error.

### Acceptance Criteria

- Creating a valid product updates the product table.
- Creating a product with an empty title fails with a visible error.
- Draft/scheduled products are excluded from checkout options.

## Checkout

### Requirements

- Checkout uses mock products and variants.
- Payment methods are PIX, credit card, and boleto as simulated methods.
- The app must not collect card numbers or sensitive payment fields.
- Checkout creates a mock order.
- Physical product checkout decrements mock stock.
- Digital product checkout does not require shipping cost.

### Acceptance Criteria

- Successful checkout shows order id, operational status, payment status, total, and simulated instructions.
- Invalid quantity or unavailable stock shows a clear error.
- Newly created order appears in dashboard/order state.

## Orders

### Requirements

- Orders have operational status: `received`, `processing`, `shipped`, `delivered`, `returned`.
- Payment status is separate: `pending`, `processing`, `approved`, `failed`, `partially_refunded`, `refunded`, `chargeback`.
- Advancing operational status must not automatically change financial status.

### Acceptance Criteria

- "Advance" updates only operational status.
- Simulated chargeback updates payment state and keeps operational status unchanged.
- Delivered/returned terminal states cannot be advanced further.

## Recovery

### Requirements

- Abandoned carts are seeded mock records.
- Simulated recovery updates `last_recovery_sent_at` or equivalent visible state.
- Copy must say "simulate" or "demo" because no email is sent.

### Acceptance Criteria

- Clicking simulated recovery updates the visible cart row.
- Empty recovery queue has a useful empty state.
- Recovery link behavior is either implemented locally or not shown.

## Analytics

### Requirements

- Analytics can be derived from seeded and local mock state.
- Metrics must not imply production tracking.
- Sample-only metrics should be labeled clearly.

### Acceptance Criteria

- Dashboard renders revenue, order count, average order value, conversion, carts, best sellers, and UTM sample data.
- When local checkout creates an order, order/revenue-facing mock metrics update or the UI states they are sample snapshots.
