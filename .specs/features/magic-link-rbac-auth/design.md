# Design: Auth por Magic Link com RBAC

## Architecture

- Backend atual (PHP 8.3 + PDO + PostgreSQL) permanece como `index.php` + serviços/repositórios.
- Frontend Nuxt 3 permanece sem framework de autenticação externo; novo middleware global e composables gerenciam sessão local.

## Backend New Components

- `backend/src/Repositories/InviteeRepository.php`: usuários convidados (invites).
- `backend/src/Repositories/MagicTokenRepository.php`: tokens de login pendentes.
- `backend/src/Repositories/AuthSessionRepository.php`: sessões ativas + hashes de access/refresh.
- `backend/src/Repositories/AuthRateLimitRepository.php`: controle de taxa por chave e janela.
- `backend/src/Repositories/AuthAuditRepository.php`: trilha de autenticação.
- `backend/src/Services/AuthService.php`: políticas de segurança, emissão de token/sessão, validação e rotação.

## Frontend New Components

- `frontend/composables/useAuthSession.ts`: estado de sessão, persistência em `localStorage`, limpeza e leitura.
- `frontend/composables/useNestApi.ts` (evolução): suporte a cabeçalho `Authorization`, refresh automático no erro 401.
- `frontend/pages/login.vue`: página pública de solicitação.
- `frontend/pages/auth/callback.vue`: página de troca de `magic token` por sessão.
- `frontend/middleware/auth.global.ts`: proteção global de rotas com RBAC.
- `frontend/components/AppShell.vue` (evolução): integração de estado, nome da função e logout.
- `frontend/types/index.ts` (evolução): tipos de usuário e sessão.

## API Contract

- `POST /api/auth/magic/request`
- `GET /api/auth/magic/callback?token=<token>`
- `POST /api/auth/refresh`
- `POST /api/auth/logout`
- `GET /api/auth/me`

## Domain Model

- `auth_invitees`: e-mails convidados e função.
- `auth_magic_tokens`: token hash, criação, expiração, uso e revogação.
- `auth_sessions`: pares hashed de access/refresh, expiração, revogação, vínculo com sessão.
- `auth_audit_events`: trilha de request/consume/refresh/logout/deny.
- `auth_rate_limits`: limitação por IP e por e-mail.

## Security Model

- Tokens em banco sempre armazenados com `SHA-256(token + salt)`.
- `access_token`/`refresh_token` emitidos como valores opacos.
- `refresh_token` é rotativo e single-use; token anterior é revogado ao trocar.
- `magic_token` tem TTL curto (`AUTH_MAGIC_LINK_TTL_MINUTES`, padrão `10`).
- `auth` endpoints respondem mensagens neutras para não vazar se e-mail existe.

## RBAC Mapping (initial)

- `admin`: todas as rotas do painel e administrativas.
- `operator`: operações administrativas e de rotina.
- `finance`: relatórios financeiros e ações de revisão.

## Data and Env Surface

- `AUTH_TOKEN_HASH_SALT`
- `AUTH_MAGIC_LINK_TTL_MINUTES` (default 10)
- `AUTH_ACCESS_TOKEN_TTL_MINUTES` (default 15)
- `AUTH_REFRESH_TOKEN_TTL_DAYS` (default 7)
- `AUTH_RATE_LIMIT_WINDOW_SECONDS` (default 60)
- `AUTH_RATE_LIMIT_MAX_PER_IP`
- `AUTH_RATE_LIMIT_MAX_PER_EMAIL`
- `AUTH_MAGIC_LINK_BASE_URL`
- `AUTH_REFRESH_ROTATION_ENABLED` (default true)
- `MAGIC_LINK_DEBUG_RETURN_TOKEN` (opcional para ambiente local)
