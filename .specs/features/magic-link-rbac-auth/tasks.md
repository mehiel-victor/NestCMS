# Tasks: Auth por Magic Link com RBAC

## T1 — Banco e Seed de Segurança

- Status: Planned
- Requirements: REQ-AUTH-030, REQ-AUTH-031, REQ-AUTH-008, REQ-AUTH-009, REQ-AUTH-010, REQ-AUTH-034
- Done when:
  - Nova migração cria tabelas `auth_invitees`, `auth_magic_tokens`, `auth_sessions`, `auth_audit_events`, `auth_rate_limits`.
  - Seed inicial inclui os 3 perfis sem auto-registro.
  - Índices e constraints obrigatórios estão presentes.

## T2 — Camada de Repositório de Autenticação

- Status: Planned
- Requirements: REQ-AUTH-009, REQ-AUTH-010, REQ-AUTH-016, REQ-AUTH-017, REQ-AUTH-025
- Done when: Repositórios para convites, tokens, sessões, rate limit e audit estão com operações de busca, gravação e rotação.

## T3 — Serviço de Auth/Policies

- Status: Planned
- Requirements: REQ-AUTH-001, REQ-AUTH-002, REQ-AUTH-004, REQ-AUTH-005, REQ-AUTH-006, REQ-AUTH-007, REQ-AUTH-012, REQ-AUTH-013, REQ-AUTH-014, REQ-AUTH-015, REQ-AUTH-022, REQ-AUTH-024, REQ-AUTH-032, REQ-AUTH-034
- Done when: Fluxos de request, callback, refresh, logout e validação retornam contratos esperados com auditoria consistente.

## T4 — Rotas REST de Auth no Backend

- Status: Planned
- Requirements: REQ-AUTH-011, REQ-AUTH-013, REQ-AUTH-020, REQ-AUTH-023, REQ-AUTH-029
- Done when:
  - Endpoints `POST /api/auth/magic/request`, `GET /api/auth/magic/callback`, `POST /api/auth/refresh`, `POST /api/auth/logout`, `GET /api/auth/me` respondem com status e payload corretos.
  - Erros de validação não expõem estado interno do convite.

## T5 — Proteção de API e RBAC por Papel

- Status: Planned
- Requirements: REQ-AUTH-017, REQ-AUTH-019, REQ-AUTH-035
- Done when: Endpoints existentes do painel passam por validação de sessão e função de acordo com escopo inicial.

## T6 — Composable de Sessão e Persistência

- Status: Planned
- Requirements: REQ-AUTH-021, REQ-AUTH-033
- Done when: Access/refresh tokens persistem em `localStorage` e são limpos no logout.

## T7 — Cliente API com Refresh Automático

- Status: Planned
- Requirements: REQ-AUTH-022, REQ-AUTH-024, REQ-AUTH-025, REQ-AUTH-032
- Done when: Chamadas de API protegidas fazem retry com `refresh` após 401 de expiração de access token.

## T8 — Middleware Frontend de Proteção

- Status: Planned
- Requirements: REQ-AUTH-017, REQ-AUTH-018, REQ-AUTH-019, REQ-AUTH-020, REQ-AUTH-035
- Done when: Rotas de painel exigem sessão válida e respeitam função definida na página.

## T9 — Páginas de Login/Callback

- Status: Planned
- Requirements: REQ-AUTH-001, REQ-AUTH-002, REQ-AUTH-003, REQ-AUTH-011, REQ-AUTH-013, REQ-AUTH-020
- Done when: Usuário consegue solicitar link e autenticar via callback sem quebrar fluxo da aplicação.

## T10 — Painel com Logout + Contexto de Função

- Status: Planned
- Requirements: REQ-AUTH-018, REQ-AUTH-026, REQ-AUTH-027, REQ-AUTH-035
- Done when: Usuário autenticado faz logout revogando sessão corrente e limpando estado local.

## T11 — Seed e Configuração de Ambiente

- Status: Planned
- Requirements: REQ-AUTH-030, REQ-AUTH-031, REQ-AUTH-032, REQ-AUTH-033
- Done when: `docker-compose`, `.env.example` e documentação mínima apresentam novas variáveis de ambiente.

## T12 — Validação Manual de Contrato

- Status: Planned
- Requirements: Todas
- Done when:
  - Manualmente validados cenários: request neutro, callback válido/inválido, refresh/rotacao, logout e rejeição por função.
  - Registro de risco e decisão final inserido em `STATE.md`.
