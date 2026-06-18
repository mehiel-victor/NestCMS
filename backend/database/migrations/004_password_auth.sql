ALTER TABLE auth_invitees
    ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255);
