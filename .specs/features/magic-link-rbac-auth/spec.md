# Feature Spec: Auth por Magic Link com RBAC e Sessões Auditáveis

## Scope

Implementar autenticação de acesso ao painel com fluxo de acesso sem senha, trilha de sessão segura e controle de acesso por função (`admin`, `operator`, `finance`).

## Requirements

- REQ-AUTH-001: O visitante pode solicitar link de acesso a partir do e-mail em `/login`.
- REQ-AUTH-002: O fluxo de solicitação deve ser neutro para e-mails inválidos e não convidados.
- REQ-AUTH-003: O tempo de resposta de solicitação deve ser consistente para qualquer e-mail.
- REQ-AUTH-004: Cada solicitação de token deve estar sujeita a limite por e-mail.
- REQ-AUTH-005: Cada solicitação de token deve estar sujeita a limite por IP.
- REQ-AUTH-006: Limite de taxa deve registrar eventos de política.
- REQ-AUTH-007: O magic token deve ter validade de 10 minutos por padrão.
- REQ-AUTH-008: O magic token deve ser single-use.
- REQ-AUTH-009: O magic token persistido no banco deve ser apenas hash SHA-256.
- REQ-AUTH-010: IP e User-Agent devem ser gravados ao criar o token.
- REQ-AUTH-011: O link deve levar para rota de callback no frontend com `token`.
- REQ-AUTH-012: O callback deve validar token, data, status e expiração.
- REQ-AUTH-013: O callback deve retornar `access_token` e `refresh_token` quando válido.
- REQ-AUTH-014: Os tokens emitidos devem conter metadados por função (`admin|operator|finance`).
- REQ-AUTH-015: Os tokens emitidos devem possuir `access` curto e `refresh` mais longo e configuráveis por ambiente.
- REQ-AUTH-016: Os valores emitidos devem ser persistidos com hash no banco.
- REQ-AUTH-017: O painel deve rejeitar sessão sem token.
- REQ-AUTH-018: O fluxo deve redirecionar `/login` quando página protegida é acessada sem autenticação.
- REQ-AUTH-019: Deve haver middleware global de proteção de páginas do painel.
- REQ-AUTH-020: O retorno deve distinguir token inválido e token expirado com mensagem amigável.
- REQ-AUTH-021: O frontend deve persistir tokens em `localStorage` para manter sessão entre recargas.
- REQ-AUTH-022: O frontend deve renovar sessão por `refresh` antes de expirar o `access`.
- REQ-AUTH-023: A rotação de `refresh_token` deve ser compatível com ambiente configurável (`AUTH_REFRESH_ROTATION_ENABLED`).
- REQ-AUTH-024: O `refresh_token` deve ser rotativo e single-use.
- REQ-AUTH-025: O `refresh_token` anterior deve ficar inválido imediatamente após a rotação.
- REQ-AUTH-026: Deve existir logout explícito que revoga apenas a sessão atual.
- REQ-AUTH-027: Deve existir revogação de sessão por evento de segurança.
- REQ-AUTH-028: A arquitetura deve suportar múltiplas sessões por usuário.
- REQ-AUTH-029: A API deve retornar contexto de sessão em `/api/auth/me`.
- REQ-AUTH-030: Todos os usuários do painel devem ser seedados via SQL.
- REQ-AUTH-031: Não deve haver auto-registro via API/página.
- REQ-AUTH-032: O backend deve impedir criação de sessão por token inválido/reutilizado.
- REQ-AUTH-033: O logout deve remover tokens locais do navegador.
- REQ-AUTH-034: O sistema deve expor trilha de auditoria para request, consume, refresh, revoke, logout, e deny.
- REQ-AUTH-035: Rotas e páginas do painel devem respeitar RBAC por função.

## Acceptance Criteria

- AC-001: Solicitação em `/api/auth/magic/request` retorna `200` com mensagem neutra para e-mail válido e inválido.
- AC-002: Link gerado expira em até 10 minutos e falha após expiração com mensagem de erro clara.
- AC-003: Reuso do mesmo magic token falha no segundo uso.
- AC-004: `POST /api/auth/magic/callback` com token válido retorna par `access_token`/`refresh_token` e dados da função.
- AC-005: Acesso a rota protegida sem sessão redireciona para `/login`.
- AC-006: Usuário com token expirado consegue renovar via `POST /api/auth/refresh` antes de bloquear o painel.
- AC-007: Reuso de `refresh_token` antigo falha após rotação.
- AC-008: `POST /api/auth/logout` revoga apenas sessão corrente e remove localStorage no frontend.
- AC-009: Tentativa de acessar rota sensível por função sem permissão retorna 403.
- AC-010: Eventos críticos gravam `auth_audit_events` com motivo, IP e user-agent.
- AC-011: Usuários seedados inicializarem sem necessidade de cadastro online.
