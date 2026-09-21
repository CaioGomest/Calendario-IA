# 00 — Escopo e regras

**Data:** 2026-09-19
**Objetivo:** revisão de qualidade do sistema (bugs, robustez, boas práticas de segurança, capacidade) a pedido do dono do projeto, com relatório detalhado — um arquivo por tópico nesta pasta.

---

## Ambientes

| | Local | Produção |
|---|---|---|
| Caminho / URL | `C:\xampp\htdocs\CalendarioIA\Calendario-IA` | `bipoia.com` (Hostinger, SSH 65002) |
| `APP_ENV` | `dev` (`MODO_DEV=true`) | produção |
| Banco | MySQL local `calendarioia`, 15 tabelas | banco real do Hostinger |
| Pagamento | simulado | Stripe modo **live** (cobrança real) |

Existe ambiente local completo e isolado. Tudo que é pesado ou gera massa de dados roda **só no local**. Produção fica **somente leitura** por padrão.

---

## Meta de escala (obrigatório considerar em todo teste/achado a partir de 20/09)

O sistema precisa aguentar **até 1.000 usuários** sem o dono precisar se preocupar. Todo teste, achado e correção de capacidade/performance deve ser avaliado contra esse cenário, não contra o uso atual (hoje: ~6 usuários ativos):

- **Limite diário de mensagens por usuário:** 40/dia (configurável em `admin/configuracao.php` → `limite_diario_mensagens`). Teto teórico do sistema: 1.000 × 40 = 40.000 mensagens/dia **se todo mundo bater o limite todo dia** (cenário exagerado, mas é o teto a considerar).
- **O que importa não é o total de usuários, é a concorrência real** — quantas ações diferentes podem acontecer no mesmíssimo segundo. Ver `anotacoes/13-capacidade-estresse.md` pra o raciocínio completo (aplicado lá ao teto de processos PHP, mas o princípio vale pra qualquer achado de capacidade daqui pra frente).
- Ao achar algo que hoje "funciona bem" só porque o volume é baixo, **não descartar** — registrar a condição em que isso deixa de valer (query sem índice que hoje roda em 15 registros mas escala mal, etc.), igual já foi feito em `anotacoes/observar-futuramente/`.

---

## O que NÃO tocar (regras firmes)

1. `config/env.php` e `config/config.php` — nunca editar, copiar, sobrescrever ou imprimir credenciais.
2. Nenhuma escrita em produção sem sua autorização item por item. Dados de teste só no banco local.
3. Nenhum fluxo de pagamento em produção (Stripe live = cobrança real). Pagamento testado só localmente.
4. Nenhum envio em massa de WhatsApp (risco de ban do número) nem de e-mail (reputação SMTP).
5. Nada de `DROP`/`TRUNCATE`/`DELETE` sem `WHERE`, mudança de schema ou remoção de arquivo em produção.
6. Nenhum teste de carga contra produção sem combinar antes (hospedagem compartilhada = risco de suspensão). Carga é local.
7. Sem cópia de dados pessoais reais pros relatórios; registra-se só a prova mínima do achado.
8. Nenhum workflow do n8n é alterado (só leitura via API, se você fornecer a chave).

---

## Riscos que limitam o teste

- **Stripe live** → pagamento só local.
- **Número de WhatsApp real** → zero volume; no máximo mensagens pontuais autorizadas.
- **IA (Groq) com custo e rate limit** → sem carga no caminho de IA.
- **Hospedagem compartilhada** → carga só local; produção no máximo GETs leves.
- **Fallback de IA quebrado** (pendência já conhecida) → registrado, não consertado às pressas.

---

## Tópicos (ordem de execução, um arquivo cada)

| # | Tópico | Onde |
|---|---|---|
| 01 | Mapa de superfície (páginas, endpoints, parâmetros, proteção) | código + local |
| 02 | Autenticação (login, hash, recuperação, token, logout) | local |
| 03 | Autorização e acesso cruzado entre contas (IDOR) | local |
| 04 | Endpoints `api/internal/` (segredo, método, validação, payload) | local |
| 05 | Injeção de SQL (auditoria de prepared statements) | local |
| 06 | XSS / escape de saída | local |
| 07 | CSRF e flags de cookie | local + headers de produção |
| 08 | Assinatura e webhook Stripe (carência, upgrade, reembolso, idempotência) | local |
| 09 | Afiliados (comissão, saque, indicação própria, cookie) | local |
| 10 | Finanças e eventos (validação de valor/data, fuso/UTC, paginação) | local |
| 11 | Limites e brute force de login | local |
| 12 | n8n / agente WhatsApp (opcional, autorizado) | leitura via API |
| 13 | Capacidade e estresse (req/s, latência, ponto de saturação) | **local** |
| 14 | Banco (índices, EXPLAIN, integridade) | local |
| 15 | Configuração/infra (headers, exposição de arquivos, versões) | GET em produção |

---

## Metodologia

- Um tópico por vez; arquivo fechado antes do próximo.
- Cada achado: descrição, reprodução, evidência, impacto real, severidade e correção sugerida.
- **Não corrijo nada na fase de teste.** Se aparecer algo crítico exposto em produção, paro e aviso. Correções ficam pra fase separada, após o relatório.
- Massa de teste local recebe prefixo `TESTE_` e é removida ao fim do tópico.
- Estresse roda em rampa e para sozinho ao degradar; objetivo é achar o teto, não derrubar.

---

## Estrutura desta pasta (atualizado 20/09)

- **`anotacoes/`** — relatórios, achados de bug/segurança e anotações em geral. Um arquivo por assunto.
- **`anotacoes/urgente/`** — subconjunto: achados que precisam ser **analisados antes de publicar uma mudança de verdade**, pra não quebrar o sistema depois de postado. Não é sobre severidade de segurança (isso vai no arquivo normal, com o campo de severidade) — é "olha isso antes do próximo deploy".
- **`anotacoes/observar-futuramente/`** — decisões conscientes de adiar algo (não fazer agora porque não compensa no volume de uso atual, não porque não importa), com a condição concreta que deve disparar a revisão. Ver `anotacoes/observar-futuramente/retry-em-criacao-de-evento-transacao.md` como exemplo.
- **Regra geral (também no `CLAUDE.md`):** nada encontrado durante a revisão é apagado só por parecer estranho. Se achar algo fora do esperado (pasta, arquivo, config), registra em anotação e decide depois — nunca deleta de cara. Ver `anotacoes/pastas-desconhecidas-producao.md` como exemplo (achamos `escopo/` e `testes/` em produção, sem equivalente local — mantidas, só anotadas).

---

## Status dos ambientes (nesta sessão)

- Local: Apache do Wamp roda mas não aponta pro projeto → sirvo com `php -S` (não altera config).
- Banco local: OK, 15 tabelas — **até 20/09**, quando `config/config.php` sumiu do disco sozinho (não fui eu; ver `04-endpoints-internos.md` §0 pra evidência). Testado restaurar via Write e via `cat >`: os dois foram barrados com "Permission denied" só nesse nome de arquivo exato (pasta aceita qualquer outro nome normalmente) — assinatura clássica de antivírus/Defender bloqueando recriação do arquivo. Ambiente local pausado até o usuário resolver isso do lado dele.
- Produção SSH: reconectado.

## Mudança de decisão (20/09) — testes agora em produção, com cautela

Como o ambiente local ficou indisponível, o usuário optou por seguir testando **direto em produção**, mantendo os limites abaixo (mais apertados que o plano original, que previa produção só-leitura):

- Sem carga/estresse em produção (fica pendente até o local voltar, ou autorização explícita ponto a ponto).
- Sem inserir massa de dados de teste em produção sem pedir antes.
- Testes de endpoint que só precisam confirmar rejeição (sem segredo, segredo errado, método errado) → seguem liberados, são somente-leitura por natureza (barrados antes de qualquer lógica de negócio).
- Testes que exigem o segredo real (`INTERNAL_SECRET`) pra ver o caminho autorizado → não tenho esse valor (nunca li `env.php`, por regra). Se o usuário quiser esse tipo de teste mais fundo, ele mesmo fornece o valor pontualmente, como fez com a chave do n8n.
- Pagamento, WhatsApp em volume e e-mail em volume continuam fora, como já valia antes.
