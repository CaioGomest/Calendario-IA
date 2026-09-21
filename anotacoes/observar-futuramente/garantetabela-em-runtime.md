# garanteTabelaX() rodando checagem de schema em toda requisição

**RESOLVIDO em 20/09/2026** — removido a pedido do usuário (funções e as 23 chamadas). Ver `atualizacao/logs_atualizacao.txt` (entrada 20/09) e `anotacoes/14-banco-indices-escala.md` §3. Ficando aqui só como histórico.

**Contexto completo:** `anotacoes/14-banco-indices-escala.md` §3.

`garanteTabelaConfiguracoes()`, `garanteTabelaPlanos()`, `garanteTabelaPagamentos()` rodam `CREATE TABLE IF NOT EXISTS` a cada requisição nova que toca essas áreas (20+ pontos de chamada). Útil só na primeira vez que o projeto rodou — o schema já é estável, mora em `database/banco.sql`.

**Por que não corrigir agora:** impacto isolado é pequeno (1 query a mais por requisição relevante). Só remover as chamadas de `garanteTabelaX()` do runtime — trivial quando for mexer nesses arquivos por outro motivo.

**Condição pra revisitar:** quando for fazer qualquer outra mudança em `funcoesConfiguracao.php` ou `funcoesPlanos.php`, aproveitar e tirar essas chamadas.
