CREATE TABLE auth_invitees (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(220) NOT NULL UNIQUE,
    role VARCHAR(40) NOT NULL CHECK (role IN ('admin', 'operator', 'finance')),
    status VARCHAR(40) NOT NULL DEFAULT 'invited' CHECK (status IN ('invited', 'active', 'revoked')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE auth_magic_tokens (
    id BIGSERIAL PRIMARY KEY,
    invitee_id BIGINT NOT NULL REFERENCES auth_invitees(id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMPTZ NOT NULL,
    used_at TIMESTAMPTZ,
    revoked_at TIMESTAMPTZ,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE auth_sessions (
    id BIGSERIAL PRIMARY KEY,
    invitee_id BIGINT NOT NULL REFERENCES auth_invitees(id) ON DELETE CASCADE,
    session_id VARCHAR(64) NOT NULL UNIQUE,
    access_token_hash CHAR(64) NOT NULL UNIQUE,
    refresh_token_hash CHAR(64) NOT NULL UNIQUE,
    role VARCHAR(40) NOT NULL,
    access_expires_at TIMESTAMPTZ NOT NULL,
    refresh_expires_at TIMESTAMPTZ NOT NULL,
    revoked_at TIMESTAMPTZ,
    revoked_reason VARCHAR(120),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    last_seen_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    created_ip INET,
    created_user_agent TEXT,
    last_ip INET,
    last_user_agent TEXT
);

CREATE TABLE auth_audit_events (
    id BIGSERIAL PRIMARY KEY,
    invitee_id BIGINT REFERENCES auth_invitees(id) ON DELETE SET NULL,
    session_id BIGINT REFERENCES auth_sessions(id) ON DELETE SET NULL,
    event_type VARCHAR(80) NOT NULL,
    outcome VARCHAR(40) NOT NULL,
    ip_address INET,
    user_agent TEXT,
    request_path TEXT,
    request_method VARCHAR(16),
    details JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE auth_rate_limits (
    rate_key VARCHAR(200) NOT NULL,
    bucket_start TIMESTAMPTZ NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 1,
    first_seen_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    last_seen_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (rate_key, bucket_start)
);

ALTER TABLE auth_audit_events
    ADD CONSTRAINT auth_audit_events_outcome_check
    CHECK (outcome IN ('allowed', 'denied', 'error', 'revoked'));

CREATE INDEX idx_auth_magic_tokens_invitee_id ON auth_magic_tokens(invitee_id);
CREATE INDEX idx_auth_magic_tokens_expires ON auth_magic_tokens(expires_at);
CREATE INDEX idx_auth_magic_tokens_used ON auth_magic_tokens(used_at) WHERE used_at IS NOT NULL;

CREATE INDEX idx_auth_sessions_invitee_id ON auth_sessions(invitee_id);
CREATE INDEX idx_auth_sessions_refresh_hash ON auth_sessions(refresh_token_hash);
CREATE INDEX idx_auth_sessions_access_hash ON auth_sessions(access_token_hash);
CREATE INDEX idx_auth_sessions_access_expiration ON auth_sessions(access_expires_at);
CREATE INDEX idx_auth_sessions_refresh_expiration ON auth_sessions(refresh_expires_at);

CREATE INDEX idx_auth_audit_events_invitee_id ON auth_audit_events(invitee_id);
CREATE INDEX idx_auth_audit_events_session_id ON auth_audit_events(session_id);
CREATE INDEX idx_auth_rate_limits_key ON auth_rate_limits(rate_key, bucket_start);
