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
