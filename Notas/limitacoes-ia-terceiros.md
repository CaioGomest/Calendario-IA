# Limitações e decisões sobre provedores de IA (WhatsApp)

> Registrado em 2026-08-20.

## Contexto

O Google, durante a verificação OAuth do escopo `calendar.events`, exigiu conformidade com a **Limited Use Policy** da Google Workspace API User Data and Developer Policy: dados de usuário obtidos via APIs do Workspace (no nosso caso, o Google Calendar) **não podem ser enviados a serviços de IA de terceiros que usam esses dados para treinar seus modelos**.

Isso gerou uma investigação sobre os provedores de IA usados no fluxo do WhatsApp (`n8n/fluxo-agente-whatsapp.json`) e resultou em mudanças reais no fluxo, não só numa resposta de e-mail.

## O que foi encontrado (política de cada provedor)

- **Groq** ✅ — não treina com dados de API em nenhum plano (inclusive free), por contrato (Services Agreement). Fonte: https://console.groq.com/docs/your-data
- **Gemini API** ⚠️ — no plano **free**, o Google usa o conteúdo pra melhorar/treinar os produtos. Só o plano **pago** (com faturamento ativo, mínimo de crédito de US$10 desde mar/2026) tem a garantia contratual de não-treinamento.
- **DeepSeek** ⚠️ — treina com as conversas por padrão. Não existe opção de Zero Data Retention configurável no painel, nem pra clientes de API/enterprise. A própria política deles diz que o tratamento de dados de usuários finais de apps de terceiros (nosso caso) **não é coberto** por essa política — zona cinzenta sem garantia documentada.

Also descobrimos que dados reais do Google Calendar (títulos/horários de eventos) **chegavam nas IAs** via o campo de contexto da conversa (`sessao.contexto` → `ultima_resposta`, reenviado no próximo turno) — não era só a mensagem digitada pelo usuário. Isso reforçou o risco de não-conformidade com Gemini free e DeepSeek.

## Decisão tomada

Em 2026-08-20, removemos **Gemini** e **DeepSeek** do `n8n/fluxo-agente-whatsapp.json`. O fluxo hoje usa **só Groq** (free tier, 100% em conformidade, sem custo). Sem fallback — se o Groq cair, o bot responde com erro técnico em vez de tentar outro provedor (troca consciente: simplicidade e custo zero agora, menos resiliência).

Respondemos o e-mail do Google (thread "API OAuth Dev Verification") explicando essa arquitetura e listando só a Groq como provedor.

## Quando reintroduzir Gemini e/ou DeepSeek

**Gemini**: só depois de ativar faturamento pago na API (não basta linkar a conta, precisa ter crédito ativo — mínimo US$10). Enquanto estiver no free tier, não é seguro reintroduzir.

**DeepSeek**: só depois de confirmar, por fora (contrato/e-mail direto com eles), uma garantia de não-treinamento equivalente à da Groq — a política pública não oferece isso hoje.

**Gatilho combinado com o usuário**: reintroduzir quando o produto já tiver receita suficiente pra sustentar o custo do plano pago (hoje o app roda 100% de graça com Groq, então não há pressa).

## Se for reintroduzir no futuro

1. Confirmar que a condição acima (billing pago / garantia de não-treinamento) está realmente satisfeita — não assumir.
2. Reintroduzir os nodes no `n8n/fluxo-agente-whatsapp.json` (a estrutura de fallback antiga pode ser recriada seguindo o mesmo padrão dos nodes de Groq: HTTP Request → node de sucesso `Marcar X` → `Executar Acao`, com branch de erro pro próximo da cadeia).
3. Avisar o Google se algo relevante mudar na arquitetura de dados (mesma thread de e-mail, ou nova verificação se pedirem).
