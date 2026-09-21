# 12 — n8n: capacidade de requisição (isolado, sem IA/WhatsApp)

**Data:** 2026-09-20
**Chave de API:** fornecida pelo usuário nesta sessão (leitura + criação/remoção de 1 workflow de teste).

## O que foi testado e por quê

O pedido era saber quanto tempo o **n8n** leva pra processar uma requisição — sem misturar isso com o tempo da IA (que tem fallback e é variável por natureza) nem arriscar o número real de WhatsApp (spam = risco de ban).

**Método:** criei um workflow novo e isolado, `TESTE_estresse_n8n` (não é o `Bipo - Agente WhatsApp` real, não foi tocado) — `Webhook → Code (simula um trabalho de processamento parecido com o que "Montar Prompt" faz) → Responder`. Sem chamada de IA, sem Evolution/WhatsApp, sem banco. Só a capacidade do próprio motor do n8n de receber e processar requisições concorrentes.

Testado com `curl --parallel` (mesma ferramenta corrigida do teste de site, ver `anotacoes/13-capacidade-estresse.md` §1). **Workflow de teste apagado ao final** — não fica lixo no n8n.

## Resultado

| Concorrência | Erros | Latência média | Mediana (p50) | p95 | p99 |
|---|---|---|---|---|---|
| 1 (isolado) | 0% | 0,26s | — | — | — |
| 10 | 0% | 0,42s | 0,46s | 0,50s | 0,50s |
| 30 | 0% | 0,91s | 0,93s | 1,16s | 1,17s |
| 50 | 0% | 1,21s | 1,25s | 1,42s | 1,44s |
| 100 | 0% | 2,39s | 1,77s | 3,93s | 3,94s |
| 200 | 0% | 6,62s | 5,98s | 15,85s | 15,90s |
| 400 | 20,8%* | 9,20s | 6,26s | 21,36s | 21,37s |

*Os 20,8% de "erro" em 400 não são o n8n recusando — são timeout do meu lado (limite de 60s no cliente). O n8n nunca devolveu um erro de verdade em nenhum nível testado; ele **enfileira**, não rejeita.

**Trabalho de processamento real dentro do n8n** (medido pelo próprio workflow, campo `processado_em_ms`): **2-4 milissegundos**, consistente em qualquer carga. Ou seja, o aumento de latência visto acima **não é o n8n "trabalhando devagar"** — é **fila**: o tempo todo, exceto uns 4ms, é esperando vez.

## Comportamento sob carga — diferente do site (achado importante)

Isso é **bem mais saudável** que o comportamento do site PHP (§13): o n8n não tem teto rígido que quebra na hora — ele vai enfileirando e demorando mais, de forma gradual. Ninguém recebe erro; recebe demora.

Mas pro caso de uso real (bot de chat no WhatsApp), **demora é praticamente igual a quebrado**: ninguém espera 6 segundos (p50 em 200 concorrentes) por uma resposta de chat sem achar que travou.

## Onde fica a linha de "ainda aceitável" pra chat

| Concorrência | Latência típica | Experiência real no WhatsApp |
|---|---|---|
| até ~10 | < 0,5s | ótimo |
| ~30-50 | ~1-1,4s | aceitável, dá pra perceber uma pausa |
| ~100 | ~1,8-3,9s | already ruim — usuário percebe demora |
| ~200+ | 6-16s | inaceitável pra chat — pareceria travado/quebrado |

**Importante contextualizar:** isso é só o n8n **sozinho**, sem a IA. No fluxo real, cada mensagem ainda soma o tempo da chamada à Groq (que já vimos, em sessão anterior, sofrer rate limit sob rajada — ver pendência `pendencia_fallback_ia_quebrado.md`) e as chamadas de volta pro nosso PHP (`api/internal/`, que tem o teto de ~20 concorrentes do §13). **O gargalo real do fluxo completo, sob pico, não vai ser o n8n — vai ser a combinação da Groq rate-limitando e o PHP derrubando acima de ~20 simultâneos.**

## Severidade

**Média-informativa.** O n8n em si aguenta bem mais do que o site PHP e do que a Groq — não é o elo fraco da corrente. Registrado principalmente pra dar a referência de números, e pra deixar claro que otimizar o n8n não resolveria o gargalo real (que está no PHP e na IA).

## Não testado (fora do escopo autorizado)

- Volume real de mensagem pelo WhatsApp/Evolution (risco de ban do número — não autorizado).
- Custo/latência real da Groq sob concorrência (medido indiretamente em sessão anterior via rate limit observado, não repetido aqui pra não gastar de novo).
