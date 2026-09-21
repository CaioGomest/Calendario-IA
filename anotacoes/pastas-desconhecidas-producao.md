# Pastas fora do inventário padrão em produção: escopo/ e testes/

**Data:** 2026-09-20 (atualizado no mesmo dia, correção de um engano meu)
**Status:** `testes/` totalmente esclarecida. `escopo/` ainda pendente de investigar.

Durante o backup de produção (20/09), a listagem de `public_html/` mostrou duas pastas que não apareciam no inventário inicial do projeto (`AGENTS.md`):

```
./escopo
./testes
```

## `testes/` — RESOLVIDO, engano meu na primeira anotação

Eu tinha registrado aqui, por engano, que `testes/` "não existe no repositório local" — **isso estava errado**. A pasta existe local, é rastreada pelo Git normalmente (`git ls-files testes/` confirma) e é um mini-framework de testes próprio do projeto:

- `testes/rodar.php` — executor. **Se bloqueia sozinho pra qualquer IP que não seja `127.0.0.1`/`::1`** (checagem logo no topo do arquivo) — ou seja, mesmo estando no webroot de produção, não é acessível de fora, só localmente no próprio servidor.
- `testes/casos/*.php` — casos de teste (`funcoesUsuarios.php`, `funcoesFinancas.php`), com funções auxiliares `testar()`, `espera()`, `esperaVerdadeiro()`, `esperaNulo()`.

Nada a corrigir aqui — é infraestrutura de teste legítima e já protegida. O erro foi meu, na investigação inicial (não olhei direito antes de concluir "não existe local").

## `escopo/` — ainda pendente

Confirmado antes: está no `.gitignore` (proposital, nunca versionado) — mas ainda não abri o conteúdo pra saber o que é. Fica pro tópico 15 (configuração/infra): listar o conteúdo (só leitura via SSH) e checar se é acessível publicamente por HTTP (`https://bipoia.com/escopo/...`).
