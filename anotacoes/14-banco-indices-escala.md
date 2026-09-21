# 14 — Banco de dados: índices e pontos que não escalam pra 1.000 usuários

**Data:** 2026-09-20
**Premissa (ver `anotacoes/00-escopo-e-regras.md`):** todo achado aqui é avaliado contra 1.000 usuários / até 40 mensagens por dia cada, não contra o volume atual (hoje: 6 usuários, poucas dezenas de linhas nas tabelas principais). Confirmado via `SHOW INDEX` e `EXPLAIN` direto em produção (só leitura).

---

## 1. `usuarios.telefone` sem índice — o mais importante

**O quê:** `buscaUsuarioPorTelefone()` (`funcoes/funcoesUsuarios.php`) faz `WHERE telefone = ? AND deletado = 0`. A tabela `usuarios` só tem índice em `id_usuario` (chave primária) e `email` (único) — **nada em `telefone`**.

**Por que importa mais que os outros achados desta lista:** essa é a **primeira consulta ao banco em toda mensagem do WhatsApp**, sem exceção (`api/internal/usuarios.php`, chamado pelo n8n a cada mensagem recebida). Hoje, com 6 usuários, é instantâneo de qualquer jeito. Em 1.000 usuários, vira uma varredura completa da tabela **em toda mensagem que qualquer pessoa manda**, o tempo todo.

**Severidade:** média (não quebra nada agora, mas é comportamento que piora sozinho conforme a base cresce, na consulta de maior frequência de todo o sistema).

**Correção:** `CREATE INDEX idx_usuarios_telefone ON usuarios (telefone);` — aditivo, sem risco, instantâneo no tamanho atual da tabela (melhor momento pra fazer é agora, pequena).

---

## 2. `eventos` sem índice pro filtro de lembrete

**O quê:** `listaEventosPendentesLembrete()` (`funcoes/funcoesEventos.php`) faz `WHERE e.lembrete = 1 AND e.lembrete_enviado = 0 ... ORDER BY e.data_inicio`. Rodei `EXPLAIN` nessa query em produção: **`type: ALL`, `key: (vazio)`** — confirma varredura completa da tabela `eventos`, sem usar nenhum índice.

**Por que importa:** esse fluxo roda **a cada 5 minutos, pra todos os usuários de uma vez** (`n8n/fluxo-lembretes.json`). Hoje são 5 linhas na tabela. Com 1.000 usuários acumulando eventos ao longo do tempo (a maioria já com `lembrete_enviado = 1`, ou seja, "lixo histórico" do ponto de vista dessa query), essa varredura cresce e roda **288 vezes por dia**, pra sempre.

**Severidade:** média (mesmo raciocínio do item 1 — degrada sozinho com o tempo, não quebra hoje).

**Correção:** `CREATE INDEX idx_eventos_lembrete ON eventos (lembrete, lembrete_enviado);` — aditivo, sem risco.

---

## 3. `garanteTabelaX()` roda uma checagem de schema em toda requisição que toca a tabela

**O quê:** `garanteTabelaConfiguracoes()`, `garanteTabelaPlanos()`, `garanteTabelaPagamentos()` fazem `CREATE TABLE IF NOT EXISTS` toda vez que são chamadas — e são chamadas no início de praticamente toda função que mexe em configuração/plano/pagamento (achei 20+ pontos de chamada, ver `funcoes/funcoesConfiguracao.php` e `funcoes/funcoesPlanos.php`). Dentro de uma mesma requisição isso só roda uma vez de verdade (tem uma trava `static $criada`), mas **em toda requisição nova** (cada visita, cada mensagem) roda de novo.

**Por que importa:** é uma query a mais, desnecessária, em cada requisição que toca essas áreas — as tabelas já existem faz tempo, essa checagem foi útil só na primeira vez que o projeto rodou. Pequeno, mas soma com os outros itens desta lista (efeito é cumulativo, é exatamente o tipo de coisa que gera o "teto de ~20 processos" chegar mais rápido — ver `anotacoes/13-capacidade-estresse.md`).

**Severidade:** baixa isoladamente, mas soma no total.

**Correção:** remover as chamadas de `garanteTabelaX()` agora que o schema é estável (fica só em `database/banco.sql`, que já é a fonte de verdade do schema).

---

## 4. `sessoes_conversa` — condição de corrida rara (2 mensagens simultâneas do mesmo usuário)

**O quê:** `atualizaSessaoConversa()` faz **SELECT pra ver se existe, depois INSERT ou UPDATE** (duas consultas, não atômico). Não tem `UNIQUE` em `id_usuario` nessa tabela (só o índice normal via FK) — só a chave primária é `id_sessao`.

**Por que importa:** se duas mensagens do **mesmo usuário** chegarem próximas o suficiente pra processar em paralelo (não é o caso comum, mas pode acontecer — ex: usuário manda duas mensagens bem rápido, ou algum retry cruzando), as duas podem achar "não existe sessão" ao mesmo tempo e criar **duas linhas de sessão pro mesmo usuário**. Depois disso, `buscaSessaoConversa()` (que faz `fetch()` sem `ORDER BY`) passa a pegar uma das duas de forma não-determinística — nada quebra visivelmente, mas o contexto da conversa pode ficar inconsistente.

**Severidade:** baixa/rara — registrado em `anotacoes/observar-futuramente/`, não em `urgente/`, porque a condição pra acontecer é estreita (mensagens do mesmíssimo usuário, quase no mesmíssimo instante).

**Correção de verdade (não aplicada):** `UNIQUE` em `id_usuario` + `INSERT ... ON DUPLICATE KEY UPDATE` numa query só, em vez de SELECT+INSERT/UPDATE.

---

## 5. `admin/usuarios.php` sem paginação

**O quê:** `listaUsuarios()` (`funcoes/funcoesUsuarios.php`) não tem `LIMIT`/paginação — sempre retorna todas as linhas que baterem o filtro.

**Por que importa:** não é risco de capacidade pro cliente final (é o painel do admin, baixíssimo tráfego, só você usa) — mas com 1.000 usuários a tela do admin renderiza uma tabela HTML gigante de uma vez, o que fica pesado/ruim de usar, mesmo que o banco aguente numa boa.

**Severidade:** baixa, é UX do admin, não capacidade do sistema.

---

## O que já está bem (conferido, não é achado)

- `logs_mensagens` (contagem do limite diário, roda em toda mensagem) — **tem índice correto** (`idx_logs_usuario_dia`), bate exatamente com a query usada. Escala bem.
- `transacoes`, `recorrencias`, `afiliados_cliques` — índices compostos bem desenhados pros filtros reais usados.
- `sessoes_conversa`, `eventos`, `logs_ia_uso`, `pagamentos` — `id_usuario` já é indexado (via a chave estrangeira, o MySQL cria índice automático nela).

---

## Resumo — o que fazer

| # | Achado | Status |
|---|---|---|
| 1 | Índice em `usuarios.telefone` | ✅ Resolvido 20/09 — índice aplicado em produção, confirmado via EXPLAIN |
| 2 | Índice em `eventos (lembrete, lembrete_enviado)` | ✅ Resolvido 20/09 — índice aplicado em produção, confirmado via EXPLAIN |
| 3 | Remover `garanteTabelaX()` em runtime | ✅ Resolvido 20/09 — 3 funções e 23 chamadas removidas |
| 4 | Corrida em `sessoes_conversa` | ✅ Resolvido 20/09 — `UNIQUE` + `INSERT ... ON DUPLICATE KEY UPDATE` |
| 5 | Paginação no admin | ✅ Resolvido 20/09 — mesmo padrão de `admin/assinaturas.php` |

Todos os 5 achados desta varredura foram corrigidos e testados em produção no mesmo dia. Detalhes de cada correção em `atualizacao/logs_atualizacao.txt` (entradas de 20/09).
