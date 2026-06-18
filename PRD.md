## Problem Statement

O checkout do NestCMS ainda cria pedidos em fluxo simulado, sem cobrança real. A operação precisa vender com PIX, cartão e boleto, mas não pode quebrar a experiência atual do checkout nem do painel da Maria. Hoje a confirmação é síncrona e fictícia, o que impede operação financeira real, conciliação e reembolso confiável. Também faltam trilhas de auditoria e mecanismos de proteção contra reprocessamento indevido de cobrança/webhooks.

## Solution

Substituir o fluxo de pagamento atual por uma camada de provedores reais por gateway (ex.: Stripe, Mercado Pago, Pagar.me), com contrato interno único do NestCMS e sem integração direta do frontend com o gateway. O frontend continua consumindo endpoints internos já existentes; o backend passa a orquestrar: criar intenção/cobrança, acompanhar confirmação assíncrona via webhooks, registrar eventos de pagamento e permitir reembolsos parcial/total sem alterar a experiência atual de checkout/painel.

## User Stories

1. Como visitante, quero finalizar pedido com os mesmos campos atuais de checkout, para pagar por PIX, cartão ou boleto sem mudar a experiência.
2. Como visitante, quero receber instruções de pagamento após gerar o pedido, para completar o pagamento fora do painel.
3. Como visitante, quero ver status de pagamento claro após concluir checkout, para saber se o pedido está pendente, aprovado ou falhou.
4. Como visitante, quero não ser cobrado duas vezes se clicar duas vezes no botão de finalizar, para evitar débitos duplicados.
5. Como visitante, quero poder sair e retornar ao checkout sem perder referência da cobrança, para retomar pagamento.
6. Como administrador, quero ver o pedido criado com estado de negócio inalterado, preservando o fluxo operacional atual.
7. Como administrador, quero acompanhar o status de pagamento no painel, para não depender de login no gateway.
8. Como administrador, quero que mudanças de status financeiro cheguem por webhook, para atualização automática.
9. Como administrador, quero registrar reembolso parcial de pedido pago, para aplicar política de devoluções por item/valor.
10. Como administrador, quero registrar reembolso total com um fluxo simples, para estorno completo no mesmo pedido.
11. Como financeiro, quero persistir `transaction_id`, para rastrear pagamentos no gateway facilmente.
12. Como financeiro, quero persistir `provider_status` em cada etapa, para conciliação operacional e tomada de decisão.
13. Como financeiro, quero persistir `webhook_payload` e trilha de evento, para auditoria e análise de inconsistências.
14. Como operador, quero que webhooks de aprovação, falha, cancelamento e chargeback atualizem o pedido de forma segura e auditável.
15. Como operador, quero idempotência na criação de cobrança, para evitar pagamentos repetidos em retry.
16. Como operador, quero idempotência no processamento de webhook, para ignorar eventos já processados.
17. Como usuário de suporte, quero registrar revisão manual de pagamento em casos de disputa, sem bloquear o painel.
18. Como desenvolvedor, quero uma interface `PaymentProvider` com providers separáveis, para trocar gateway sem reescrever checkout/pedidos.
19. Como desenvolvedor, quero manter o contrato de resposta de `/api/orders` estável para o frontend.
20. Como desenvolvedor, quero mapear erros de webhook com logs estruturados, para diagnóstico rápido.
21. Como time de produto, quero relatório de pedidos com pagamento pendente por tempo prolongado, para alertas operacionais.
22. Como gerente, quero que chargeback não quebre o fluxo de pedido, apenas registre e sinalize risco.
23. Como analista, quero testes automatizados no nível de API para validação de contratos externos.
24. Como PM, quero rollout incremental por gateway, começando por um provider padrão e mantendo fallback.

## Implementation Decisions

- Criar uma camada de abstração `PaymentProvider` no backend com implementações por gateway e adapter de teste/sandbox.
- Manter `frontend/pages/checkout.vue` e `frontend/pages/index.vue` consumindo endpoints internos (`useNestApi`) sem integrar SDKs de pagamento.
- Ajustar criação de cobrança para persistir metadados de transação: provider, `transaction_id`, `provider_status`, `amount`, `currency`, `payment_method`, timestamps e último erro.
- Persistir `webhook_payload` bruto por evento de webhook em trilha de auditoria.
- Separar claramente fluxo de negócio do pedido (`received`, `processing`, `shipped`, `delivered`, `returned`) de estado financeiro do pagamento.
- Adicionar endpoint interno para criação de transação e endpoint de webhook por provider.
- Aplicar idempotência na criação:
  - Chave `idempotency_key` associada ao payload do checkout/pedido.
  - Unicidade por hash de tentativa e `customer`/carrinho.
- Aplicar idempotência no webhook:
  - dedupe por `provider_event_id`.
  - Registrar evento processado antes da mutação de pedido/pagamento.
- Implementar atualização assíncrona orientada por eventos com validação de assinatura do webhook.
- Acrescentar trilha de auditoria com `correlation_id`, `request_id`, actor/event, payload hash e resultado da operação.
- Manter UX atual do painel; apenas ampliar campos/labels de pagamento, sem mudanças de navegação.

## Testing Decisions

- Boas decisões de teste: validar comportamento externo (HTTP/API + respostas observáveis) e não acoplar a implementação interna.
- Testar seams de maior nível:
  - API pública do checkout/pedidos;
  - seam de `PaymentProvider` com adapters fake/stub;
  - persistência de eventos de idempotência e trilha.
- Módulos sob teste:
  - `CheckoutService` via endpoint de criação de pedido/cobrança;
  - `OrderService` e repositórios de pedido/eventos;
  - handlers de webhook por provider.
- Prior art interno: estrutura atual baseada em services/repositories favorece injeção de dependência e testes de contrato por camadas.
- Cenários obrigatórios:
  - checkout em status pendente para PIX/cartão/boleto;
  - aprovação/recusa por webhook;
  - reprocessamento de webhook duplicado;
  - reembolso parcial e total;
  - reembolso inválido (pedido não pago);
  - assinatura de webhook inválida;
  - fallback/safeguard com provider indisponível.

## Out of Scope

- Redesign do checkout para nova jornada multi-step com novos formulários.
- Mudança de stack de infraestrutura (fila distribuída complexa, microserviços) nesta etapa.
- Emissão fiscal completa e conciliação contábil fim-a-fim.
- Refatoração do estado operacional do pedido para unificar financeiro+logística em único fluxo.
- Ajustes de branding/UX além de complementação de status de pagamento.

## Further Notes

- O repositório já indica pagamentos, shipping, fiscal e email como stubs, facilitando evolução incremental para provedor real.
- A prioridade é preservar operação e evitar interrupção da interface.
- Implementar por etapas: (1) criação de cobrança + atualização assíncrona, (2) reembolsos, (3) reconciliação periódica.
- Reforçar no painel a diferenciação entre status operacional e financeiro para reduzir erro operacional.
