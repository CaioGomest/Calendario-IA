# 13 — Capacidade e estresse

**Data:** 2026-09-20
**Onde rodou:** produção (`bipoia.com`), autorizado explicitamente pelo usuário ("empurra até achar o teto de verdade"), já que o ambiente local estava indisponível (`config/config.php` sumiu do disco local — ver `anotacoes/00-escopo-e-regras.md`, seção "Status dos ambientes").
**Status do site após os testes:** saudável — recupera em segundos após cada rajada, sem efeito residual.

---

## Resumo executivo

O site aguenta bem uma carga moderada (latência ótima, ~100-150ms, até ~20 requisições verdadeiramente simultâneas), mas **quebra de forma abrupta e consistente acima disso** — não é uma degradação gradual, é um **teto rígido**: as requisições que passam do limite recebem **HTTP 500 com corpo vazio**, instantaneamente, em vez de enfileirar ou responder mais devagar.

**Isso não é bug de código.** É limite de infraestrutura da hospedagem compartilhada (Hostinger) — provavelmente o número de processos PHP simultâneos que o plano permite por conta. Descartei banco de dados como causa (ver §3).

---

## 1. Metodologia (e um erro de ferramenta corrigido no meio do caminho)

**Primeira tentativa (com falha silenciosa):** gerei carga disparando N processos `curl.exe` separados via `xargs -P`. Em concorrência nominal de até 100, deu 0% de erro e ~130ms de latência — parecia ótimo.

**Isso era enganoso.** No Windows, criar dezenas de processos separados tem overhead alto; o throughput real medido (~11 req/s) não subia com a concorrência pedida, sinal de que o gargalo era a minha própria máquina gerando a carga, não o servidor. Ou seja: **o primeiro teste não estava de fato testando o servidor.**

**Correção:** troquei para o motor de paralelismo nativo do `curl` (`--parallel --parallel-max N`, um único processo, requisições genuinamente simultâneas via multiplexação interna). Foi só aí que o teto real apareceu.

*Lição registrada pra qualquer teste de carga futuro neste projeto: gerar concorrência de verdade no Windows precisa do `curl --parallel` (ou ferramenta equivalente de single-process), não `xargs -P` com múltiplos processos.*

## 2. Resultado da rampa (motor correto)

| Concorrência real (`--parallel-max`) | Sucesso (200) | Falha (500, corpo vazio) | Latência das que passaram |
|---|---|---|---|
| 20 | 20/20 | 0 | ~100-150ms |
| 25 | 20/25 | 5 | idem |
| 30 | 20/30 | 10 | idem |
| 35 | 20/35 | 15 | idem |
| 40 | 20/40 | 20 | idem |
| 60 | 20/60 | 40 | idem |

**Achado central:** o número de sucessos fica travado em ~20 (variou 14-20 entre execuções, provavelmente por variação de carga concorrente de outras contas no mesmo servidor compartilhado, ou pequena diferença de timing entre disparos), **independente de quanto eu aumento a concorrência pedida.** As requisições que não cabem no teto não esperam na fila — falham na hora, com **HTTP 500 e corpo de resposta vazio** (sem mensagem de erro do PHP, sem stack trace).

**Recuperação:** instantânea. Testado repetidas vezes logo após cada rajada — o site volta a responder em ~100-150ms assim que a rajada passa. Não há efeito cumulativo nem degradação permanente.

## 3. Causa raiz — o que foi descartado e o que sobrou

Testei diretamente no servidor (leitura, via SSH) pra achar a causa:

| Hipótese | Checado | Resultado |
|---|---|---|
| Limite de conexões do MySQL | `SHOW VARIABLES LIKE 'max_connections'` | **2000** — bem acima do necessário, não é isso |
| Conexões de usuário do MySQL | `SHOW VARIABLES LIKE 'max_user_connections'` | **75** — também não bate com o teto de ~20 |
| Vazamento de conexão do próprio app | `SHOW PROCESSLIST` (mostra só as conexões da própria conta, é servidor compartilhado) | **1 conexão** por vez — o app fecha a conexão PDO corretamente a cada request, sem vazamento |
| Limite de processo do sistema (`ulimit`) | `ulimit -a` via SSH | `max user processes = 1027440` — não é isso |
| Página específica (consulta pesada na landing) | Testei também `/termos` (página estática, sem query nenhuma) sob a mesma carga | **Mesmo padrão de falha** (14 de 40 passaram, resto 500 vazio) |

A última linha é a mais importante: como uma página **sem nenhuma consulta ao banco** quebra do mesmo jeito, **descarta qualquer causa relacionada a query lenta ou lock de banco**. O teto é de **execução de PHP por conta**, não de uma rota específica. Isso é consistente com o plano de hospedagem compartilhada limitando quantos processos PHP simultâneos aquela conta pode rodar (gerenciado pelo LiteSpeed/painel da Hostinger, não visível/configurável via SSH do lado do tenant).

## 4. O que isso significa em termos de negócio

Importante não interpretar errado: **isso não quer dizer "só 20 usuários por vez"**. A maioria das visitas de usuários reais não bate no servidor no exato mesmo instante — o teto é sobre **picos de simultaneidade real**, não sobre total de usuários ativos.

Mas ~20 requisições verdadeiramente simultâneas **é um número baixo e fácil de atingir** em cenários realistas:
- uma campanha de marketing ou post que gere um pico de acessos de uma vez;
- um horário de lembrete do WhatsApp disparando pra muitos usuários ao mesmo tempo, cada um gerando chamadas de API internas quase simultâneas;
- qualquer bot/crawler batendo várias páginas rápido.

Nesses cenários, uma fração real de usuários **veria erro 500 puro, sem mensagem nenhuma** — pior ainda: como não há fila/espera, não é "lento", é "quebra na hora" pra quem excede o teto.

## 5. Severidade

**Alta.** Não é uma falha de segurança, mas é uma fragilidade operacional real e barata de acontecer, sem qualquer aviso ao usuário (tela em branco / erro genérico do navegador, sem nossa mensagem de erro amigável — porque nem chega a rodar nosso PHP).

## 6. Correção sugerida

1. ✅ **Feito (20/09):** cache em arquivo pra landing page (`index.php`), TTL 5 min, por idioma. Antes: ~550ms e 7-8 consultas ao banco por visita. Depois (cache hit): ~115ms, zero consultas. Ver `atualizacao/logs_atualizacao.txt` (entrada 20/09) pros detalhes técnicos. Isso reduz bastante a chance da fonte de pico mais óbvia (tráfego público/marketing na landing), mas **não muda o teto em si** — só tira essa rota do caminho.
2. **Pendente — confirmar com a Hostinger** (chat/suporte, ou painel hPanel → "Recursos" / "Limites do plano") qual é o limite real de processos PHP simultâneos do plano atual, e se dá pra aumentar. Isso resolveria a causa raiz de vez; o cache é mitigação, não remove o teto de ~20.
3. Se o limite realmente for baixo por plano, considerar migrar pra um plano com mais processos PHP concorrentes antes de qualquer ação de marketing/lançamento que possa gerar pico de acesso.
4. Página de erro 500 customizada (hoje é corpo vazio) ajudaria a pelo menos não parecer "site fora do ar" pro usuário que cair nesse teto — mas isso é paliativo, o problema de fundo é o teto de processos.
5. Pendente (discutido com o usuário, não implementado ainda): espaçar o disparo de lembretes no n8n e adicionar retry com backoff nas chamadas do n8n pro nosso PHP — mitiga as outras duas fontes prováveis de pico simultâneo (lembretes em lote e falhas transitórias de mensagem).

## 7. Pendente

- Teste de estresse dos **endpoints internos autenticados** (`api/internal/`) não foi feito — não tenho o `INTERNAL_SECRET` de produção. Esses endpoints são chamados pelo n8n a cada mensagem de WhatsApp; se o mesmo teto de processos PHP vale pra eles (bem provável, já que é limite de conta, não de rota), uma rajada de mensagens simultâneas no WhatsApp teria o mesmo problema — o n8n receberia 500 do nosso lado ao tentar buscar/salvar dados do usuário.
- Teste de estresse do fluxo de **pagamento** não é seguro em produção (Stripe live) — não incluído aqu, por regra de escopo.
