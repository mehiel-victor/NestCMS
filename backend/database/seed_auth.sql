INSERT INTO auth_invitees (email, password_hash, role, status) VALUES
    ('admin@nestcms.test', '$2y$12$uA7vu63FOXkR14dTW5TjP.9ljI12nJaLI/7nQmjgDvrpBPhL5tSVO', 'admin', 'active'),
    ('operator@nestcms.test', '$2y$12$e0.mNE44aKEWMDFtJx0O/eprbG5RNt5xesCjG9cKwfgb9n/2s4wU2', 'operator', 'active'),
    ('finance@nestcms.test', '$2y$12$GjMz2h3Bt7jN.if2abI8KOkRY2D7PJD4Dr2f39e2h9ipvN9IVqxQa', 'finance', 'active')
ON CONFLICT (email) DO UPDATE
SET password_hash = EXCLUDED.password_hash,
    role = EXCLUDED.role,
    status = EXCLUDED.status,
    updated_at = now();
