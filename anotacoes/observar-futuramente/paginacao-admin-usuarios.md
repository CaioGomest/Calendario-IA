# admin/usuarios.php sem paginação

**RESOLVIDO em 20/09/2026** — paginação adicionada (30/página), mesmo padrão de `admin/assinaturas.php`. Ver `atualizacao/logs_atualizacao.txt` (entrada 20/09). Ficando aqui só como histórico.

**Contexto completo:** `anotacoes/14-banco-indices-escala.md` §5.

`listaUsuarios()` não tem `LIMIT`/paginação — sempre retorna todas as linhas. Não é risco de capacidade (é o painel do admin, baixo tráfego), mas com 1.000 usuários a tabela HTML fica gigante e pesada de navegar.

**Por que não corrigir agora:** é UX, não afeta cliente final nem capacidade do sistema.

**Condição pra revisitar:** quando a lista de usuários no admin começar a ficar visivelmente pesada de rolar/carregar (na prática, provavelmente lá pelos 200-300+ usuários).
