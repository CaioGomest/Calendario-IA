# Custos de IA (Groq) — CalendarioIA

Notas da conversa sobre plano/modelo do Groq e custo estimado por usuário. Registrado em 02/08/2026.

## Motivo

Durante testes do fluxo do WhatsApp, bateu rate limit do Groq: `The service is receiving too many requests from you` (erro 429). Isso é limite do **Free Tier** (30 RPM, 1K req/dia, 12K TPM pro `llama-3.3-70b-versatile`) — qualquer uso real com usuários pagantes estoura isso rápido.

## Modelo em uso hoje

`llama-3.3-70b-versatile` (configurado na variável `GROQ_MODEL` do node "Variaveis" no `fluxo-agente-whatsapp.json`).

**Decisão: manter esse modelo.** Ele lida melhor com os casos ambíguos que a gente ficou corrigindo na sessão (hora sem am/pm, desambiguar eventos com nome repetido, etc.). Modelos menores (ex: `llama-3.1-8b-instant`) são ~10x mais baratos mas arriscam voltar a errar esse tipo de interpretação.

## O que muda pra resolver o rate limit

**Nada de código ou n8n.** É só cadastrar forma de pagamento na conta do Groq ([console.groq.com](https://console.groq.com) → Billing) — isso tira a conta do Free Tier e sobe pro **Developer Tier** automaticamente (pay-as-you-go, sem assinatura fixa). A `GROQ_API_KEY` que já está no n8n continua a mesma, só os limites de RPM/TPM sobem.

(Ngrok não tem nada a ver com isso — é só um túnel de rede, coisa diferente do billing do Groq.)

## Comparação de modelos (Developer Tier)

| Modelo | Input ($/M tokens) | Output ($/M tokens) |
|---|---|---|
| `llama-3.3-70b-versatile` (em uso) | $0,59 | $0,79 |
| `llama-3.1-8b-instant` (alternativa mais barata) | $0,05 | $0,08 |

## Estimativa de custo mensal

Premissa: ~20 mensagens/dia por usuário ativo (uso intenso — se o uso real for mais leve, o custo cai proporcionalmente). Prompt do sistema é longo (~2.000 tokens de entrada por mensagem, ~150 de saída).

Cotação usada: 1 USD ≈ R$ 5,08 (02/08/2026).

| Usuários | `llama-3.3-70b-versatile` | `llama-3.1-8b-instant` |
|---|---|---|
| 100 | ~$78/mês → **~R$ 396/mês** | ~$6,70/mês → ~R$ 34/mês |
| 1.000 | ~$780/mês → **~R$ 3.960/mês** | ~$67/mês → ~R$ 340/mês |

**Custo por usuário (qualquer escala, é linear):**
- `llama-3.3-70b-versatile`: ~$0,78/usuário/mês → **~R$ 3,96/usuário/mês**
- `llama-3.1-8b-instant`: ~$0,067/usuário/mês → ~R$ 0,34/usuário/mês

## Precificação atual do produto (pra comparar)

Único plano cadastrado no banco de produção hoje:

| Nome | Ciclo | Preço | Dias teste |
|---|---|---|---|
| basik | mensal | R$ 1,00 | 0 |

Isso é claramente valor de teste/placeholder — não cobre o custo de IA por usuário (~R$ 3,96), muito menos hospedagem, WhatsApp API, Stripe etc.

### Preço real (em validação)

Cogitado entre **R$ 19,90** ou **R$ 25,90/mês** por usuário (ainda não decidido — 02/08/2026).

Margem sobre o custo de IA (llama-3.3-70b, ~R$ 3,96/usuário/mês), sem contar hospedagem/WhatsApp/Stripe:

| Preço cogitado | Custo IA | Margem bruta (só IA) |
|---|---|---|
| R$ 19,90 | R$ 3,96 | R$ 15,94 (~80%) |
| R$ 25,90 | R$ 3,96 | R$ 21,94 (~85%) |

Os dois cobrem tranquilamente o custo de IA sozinho; a decisão entre os dois provavelmente depende mais de posicionamento de mercado e dos outros custos (hospedagem, WhatsApp API, taxa do Stripe) do que da IA em si.

## Fontes

- [Groq pricing in 2026 (eesel AI)](https://www.eesel.ai/blog/groq-pricing)
- [Groq Pricing In 2026 (CloudZero)](https://www.cloudzero.com/blog/groq-pricing/)
- [Groq llama-3.1-8b-instant Pricing Calculator (Helicone)](https://www.helicone.ai/llm-cost/provider/groq/model/llama-3.1-8b-instant)
- [Cotação Dólar (Investing.com)](https://br.investing.com/currencies/usd-brl)

---

## Atualização 16/08/2026 — Gemini como fallback + teto mensal garantido pelo rate limit

Duas mudanças desde a análise original: (1) o fluxo do WhatsApp (`n8n/fluxo-agente-whatsapp.json`) agora tenta o **Groq primeiro** e, se falhar, cai automaticamente pro **Gemini** como reserva; (2) foi implementado um **limite diário de mensagens por usuário** (ver `limite-diario-mensagens.md`), configurável no admin, padrão 40/dia. Isso muda a pergunta de "quanto pode custar um usuário" (sem teto, risco ilimitado) pra "qual o teto garantido por usuário" — é isso que este relatório calcula.

Cotação usada: 1 USD ≈ R$ 5,22 (16/08/2026).

### Preço por modelo (API, Developer/pay-as-you-go tier)

| Modelo | Papel | Input ($/M tokens) | Output ($/M tokens) |
|---|---|---|---|
| `llama-3.3-70b-versatile` (Groq) | Principal | $0,59 | $0,79 |
| `gemini-flash-latest` → resolve hoje pra `gemini-3.7-flash` (Gemini) | Fallback | $0,75 (preço promocional até 31/12/2026) | $3,75 (idem) |
| `gemini-3.7-flash` (a partir de 01/01/2027) | Fallback | $1,50 | $7,50 |

O node "Gemini" do fluxo já está configurado com `thinkingConfig.thinkingBudget: 0` — testei ao vivo e isso é importante: **sem** essa config, o Gemini gasta ~150 tokens de "raciocínio" invisível mesmo numa resposta trivial (testei: 169 tokens totais numa pergunta de 2 palavras). Com `thinkingBudget: 0`, cai pra 28 tokens, mantendo a resposta correta — reduz o custo do fallback em mais de 5x sem perda perceptível de qualidade pra esse caso de uso (comandos curtos de agenda/finanças).

### Custo por mensagem

Mesma premissa da análise original: prompt de sistema longo (~2.000 tokens de entrada), resposta curta (~150 tokens de saída).

| Cenário | Custo/mensagem (USD) | Custo/mensagem (BRL) |
|---|---|---|
| Groq (uso normal) | $0,001329 | R$ 0,0069 |
| Gemini fallback (preço até 2026) | $0,002063 | R$ 0,0108 |
| Gemini fallback (preço a partir de 2027) | $0,004125 | R$ 0,0215 |

### Teto mensal garantido por usuário (30 dias no limite diário)

Isso é o que o rate limit implementado hoje garante: **não importa quantas mensagens o usuário mande**, o custo mensal dele nunca passa desse teto, porque acima do limite diário a IA simplesmente não é mais chamada (nem Groq, nem Gemini).

| Limite diário configurado | Msgs/mês no teto | Teto 100% Groq (cenário normal) | Teto 100% Gemini (Groq fora do ar o mês todo, preço até 2026) | Teto 100% Gemini (preço 2027) |
|---|---|---|---|---|
| 20/dia | 600 | R$ 4,16 | R$ 6,46 | R$ 12,92 |
| **40/dia (padrão atual)** | **1.200** | **R$ 8,32** | **R$ 12,92** | **R$ 25,84** |
| 60/dia | 1.800 | R$ 12,48 | R$ 19,38 | R$ 38,76 |
| 100/dia | 3.000 | R$ 20,80 | R$ 32,30 | R$ 64,60 |

Cenário realista (fallback é exceção, não regra — Groq só falha em pico/instabilidade pontual, estimando 5% das mensagens indo pro Gemini): com o limite padrão de 40/dia, o teto fica em **~R$ 8,55/usuário/mês**, bem próximo do cenário 100% Groq.

### Isso muda a conta do preço do plano?

Com o teto (cenário realista, limite padrão de 40/dia): **~R$ 8,55/usuário/mês** de custo máximo de IA — bem abaixo dos ~R$ 3,96/usuário/mês estimados antes (que era uma média de uso, não um teto).

| Preço cogitado | Teto de custo IA (realista) | Margem (só IA, pior caso) |
|---|---|---|
| R$ 1,00 (atual, placeholder) | R$ 8,55 | **Negativo** — mesmo com o teto, o plano de R$1 não cobre o pior caso de IA de um usuário só |
| R$ 19,90 | R$ 8,55 | R$ 11,35 (~57%) |
| R$ 25,90 | R$ 8,55 | R$ 17,35 (~67%) |

O ponto principal: **antes do rate limit, não existia teto nenhum** — um usuário podia, em tese, gerar centenas de reais de custo de IA sozinho. Agora, mesmo no pior caso absoluto (Groq fora do ar o mês inteiro, 100% no Gemini, preço de 2027), o teto por usuário é **R$ 25,84/mês** com o limite padrão de 40/dia — ainda comparável ao preço cogitado de R$25,90. Ou seja, mesmo o pior cenário possível não gera prejuízo direto por usuário nesse preço, o que não era garantido antes.

### Fontes (atualização)

- [Gemini 3.7 Flash Pricing Explained (Apidog)](https://apidog.com/blog/gemini-3-7-flash-pricing-explained/)
- [Gemini 3.7 Flash - API Pricing & Benchmarks (OpenRouter)](https://openrouter.ai/google/gemini-3.7-flash)
- [Groq API Pricing 2026 (AI Pricing Guru)](https://www.aipricing.guru/groq-pricing/)
- [Cotação Dólar (Investing.com)](https://br.investing.com/currencies/usd-brl)
