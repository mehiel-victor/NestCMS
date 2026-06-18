CREATE TABLE payment_transactions (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    provider VARCHAR(80) NOT NULL,
    provider_transaction_id VARCHAR(180) NOT NULL UNIQUE,
    idempotency_key VARCHAR(190) NOT NULL UNIQUE,
    payment_method VARCHAR(40) NOT NULL,
    amount NUMERIC(12, 2) NOT NULL CHECK (amount >= 0),
    currency CHAR(3) NOT NULL DEFAULT 'BRL',
    provider_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    payment_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    last_error TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT payment_transactions_status_check CHECK (payment_status IN ('pending', 'processing', 'approved', 'failed', 'partially_refunded', 'refunded', 'chargeback'))
);

CREATE TABLE payment_events (
    id BIGSERIAL PRIMARY KEY,
    transaction_id BIGINT NOT NULL REFERENCES payment_transactions(id) ON DELETE CASCADE,
    provider_event_id VARCHAR(190) NOT NULL,
    provider VARCHAR(80) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    provider_status VARCHAR(40),
    payload_hash CHAR(64) NOT NULL,
    webhook_payload JSONB NOT NULL,
    processed_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(provider, provider_event_id)
);

CREATE TABLE payment_refunds (
    id BIGSERIAL PRIMARY KEY,
    transaction_id BIGINT NOT NULL REFERENCES payment_transactions(id) ON DELETE CASCADE,
    provider_refund_id VARCHAR(190) NOT NULL UNIQUE,
    amount NUMERIC(12, 2) NOT NULL CHECK (amount >= 0),
    reason TEXT NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'processing',
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    created_by VARCHAR(120)
);

CREATE TABLE payment_idempotency_keys (
    id BIGSERIAL PRIMARY KEY,
    idempotency_key VARCHAR(190) NOT NULL UNIQUE,
    transaction_id BIGINT NOT NULL REFERENCES payment_transactions(id) ON DELETE CASCADE,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    result_payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE manual_payment_reviews (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    actor VARCHAR(180) NOT NULL,
    decision VARCHAR(60) NOT NULL,
    notes TEXT,
    risk_level VARCHAR(30) NOT NULL DEFAULT 'medium',
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

ALTER TABLE orders
    ADD COLUMN payment_status VARCHAR(40),
    ADD COLUMN payment_provider VARCHAR(80),
    ADD COLUMN payment_provider_status VARCHAR(40),
    ADD COLUMN payment_transaction_id BIGINT REFERENCES payment_transactions(id),
    ADD CONSTRAINT orders_payment_status_check CHECK (
        payment_status IS NULL OR payment_status IN ('pending', 'processing', 'approved', 'failed', 'partially_refunded', 'refunded', 'chargeback')
    );

CREATE INDEX idx_orders_payment_status ON orders(payment_status);
CREATE INDEX idx_orders_payment_provider ON orders(payment_provider);
CREATE INDEX idx_payment_transactions_order ON payment_transactions(order_id);
CREATE INDEX idx_payment_events_transaction ON payment_events(transaction_id);
CREATE INDEX idx_payment_events_provider ON payment_events(provider, provider_event_id);
CREATE INDEX idx_payment_refunds_transaction ON payment_refunds(transaction_id);
CREATE INDEX idx_payment_idempotency_order ON payment_idempotency_keys(order_id);
CREATE INDEX idx_manual_payment_reviews_order ON manual_payment_reviews(order_id);
