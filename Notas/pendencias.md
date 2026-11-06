# Pendências

Lista de coisas em aberto que dependem de ação externa (do usuário, ou de terceiros) pra serem fechadas. Atualizar conforme forem resolvidas ou surgirem novas.

---

## Fallback de IA quebrado (Mistral/OpenAI)

O fluxo do WhatsApp (`n8n/fluxo-agente-whatsapp.json`, workflow "Bipo - Agente WhatsApp") tem uma cadeia de fallback de IA: Groq (principal) → Mistral → OpenAI → erro genérico (ver `Notas/limitacoes-ia-terceiros.md`). Confirmado em execução real (08/09/2026) que os dois backups estão quebrados:

- **Mistral**: retorna `401 Invalid API Key` — a chave configurada na variável `MISTRAL_API_KEY` (node "Variaveis") está errada ou expirada.
- **OpenAI**: retorna `401 Incorrect API key provided: COLOQUE_AQUI` — a variável `OPENAI_API_KEY` nunca foi configurada de verdade, ainda tem o texto de placeholder.

Sem fallback funcional, quando a Groq rate-limita (acontece com mensagens rápidas seguidas, e pode acontecer em produção também), o usuário recebe direto a mensagem genérica de erro em vez do bot conseguir responder via Mistral/OpenAI.

**Falta:** o usuário mandar as chaves reais da Mistral e da OpenAI. Quando chegarem, atualizar `MISTRAL_API_KEY`/`OPENAI_API_KEY` no node "Variaveis" do workflow ativo (via API do n8n) e testar.

---

## Assinaturas órfãs no Stripe

6 assinaturas de teste do usuário 1 (Caio, conta de testes) ainda ativas no Stripe, criadas durante testes do fluxo de pagamento.

**Falta:** confirmação do usuário pra cancelar essas assinaturas de teste.

---

## Sitemap no Google Search Console

`sitemap.xml` do site ainda não foi cadastrado no Google Search Console.

**Falta:** ação manual do usuário no painel do Google (fora do escopo deste repositório/Claude).


---

## Stripe e n8n: ações após a revisão de pagamento (27/10/2026)

O código novo já está em produção (ver `anotacoes/revisao-pagamento-renovacao-cancelamento-2026-10-27.md`). Estas partes só o dono consegue fazer:

- [ ] **Stripe, webhook:** no endpoint `https://bipoia.com/api/internal/stripe-webhook.php`, garantir os eventos `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.paid`, `invoice.payment_failed`, `charge.refunded` e **adicionar** `customer.subscription.trial_will_end`, `invoice.upcoming`, `invoice.payment_action_required`. Sem os três novos, os avisos de fim de teste e de renovação **não saem** (o México exige aviso de renovação com pelo menos 5 dias naturais).
- [ ] **Stripe, Billing:** em "Upcoming renewal events" manter pelo menos 5 dias (padrão 7); ligar recibos por e-mail; definir as regras de nova tentativa (Smart Retries, 2 semanas) e a ação final "cancelar assinatura"; no portal do cliente, cancelamento **ao fim do período** (não imediato).
- [x] **n8n (feito em 27/10/2026, via API):** aplicados os 3 ajustes de `Notas/n8n-ajuste-plano-ativo.md` no workflow ao vivo (**não importar o JSON inteiro**: ele tem `COLOQUE_AQUI` no lugar das chaves e apagaria as reais) e testar com um número cancelado e outro sem cadastro. Falta so o teste real: mandar mensagem de um numero com plano cancelado e de um sem cadastro.
- [ ] **Reembolso por arrependimento (Brasil, 7 dias):** quando alguém pedir por e-mail, fazer reembolso **total** no painel da Stripe; o sistema cancela a assinatura e estorna a comissão sozinho.
- [ ] Cancelar as 6 assinaturas de teste órfãs do usuário 1 (ver "Assinaturas órfãs no Stripe", acima).
- [ ] Conferir os Termos (seção 4) com um advogado e emitir nota fiscal de serviço no Brasil (fora do código).
- [ ] **Acompanhar** a primeira renovação real e a primeira troca de ciclo real: o comportamento da Stripe nesses dois pontos foi implementado pela documentação e testado só com simulação.
