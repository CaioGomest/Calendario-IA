# AGENTS.md — [Nome do Projeto]

> Leia este arquivo **antes de qualquer tarefa**. Todo código segue estas regras sem exceção.  
> Quando estiver na dúvida, escolha a opção com **menos código**.

---

## O que é

[Descrição curta do projeto: o que faz, para quem, qual o problema que resolve.]

---

## Stack

- [Linguagem / framework principal]
- [Banco de dados e como acessa: ex. MySQL via PDO]
- [Frontend: ex. jQuery, React, Vue]
- [CSS: ex. Tailwind via CDN, SCSS]
- [Outros: fontes, libs fixas]

Não introduza dependências novas sem necessidade real.

---

## Estrutura de pastas

```
/
├── api/          # endpoints externos
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── config/       # credenciais e conexão
├── database/     # schema e migrations
├── funcoes/      # lógica de dados por domínio
├── admin/        # painel interno
├── cliente/      # área do usuário
└── index.php     # ponto de entrada
```

Pastas e arquivos: **minúsculo, sem acento, sem espaço**.

---

## Convenções OBRIGATÓRIAS

### Funções
- **camelCase**, sempre começando com verbo que descreve a ação: `busca`, `insere`, `atualiza`, `deleta`, `lista`, `valida`.
- **Uma função, uma responsabilidade.** Se faz duas coisas, são duas funções.
- **Reutilizar via parâmetros**, nunca duplicar lógica.
- Lógica de dados fica em `funcoes/`. Páginas só chamam funções e montam HTML/saída.

```php
// ✅ correto
function buscaUsuarioPorEmail($email) { ... }
function insereUsuario($dados) { ... }
function listaEventos($filtro = []) { ... }

// ❌ errado
function getUserAndSendEmail($id) { ... }  // duas responsabilidades
function listaEventosAtivos() { ... }       // duplica listaEventos com filtro
```

### Variáveis
- **snake_case**, nome claro no idioma do projeto.
- O nome deve dizer o que é, sem abreviação.

```php
// ✅ correto
$id_usuario, $token_acesso, $data_evento, $nome_arquivo

// ❌ errado
$u, $tk, $dt, $tmp
```

### Arquivos e páginas
- O nome diz o que a página faz: `dashboard.php`, `cadastro.php`, `recuperar.php`.
- Sem SQL solto dentro de páginas — SQL só dentro das funções de `funcoes/`.

---

## Código limpo

- **Mínimo necessário.** Sem abstração prematura, sem classe onde uma função resolve.
- Sem comentário óbvio. Comente apenas o **porquê**, nunca o quê.
- Sem código morto, sem `var_dump` esquecido, sem `TODO` antigo.
- Três linhas parecidas não viram função — dez viram.

```php
// ❌ comentário óbvio (delete)
// busca o usuário pelo id
$usuario = buscaUsuarioPorId($id);

// ✅ comentário útil (explica o porquê)
// token expira 5 min antes para evitar race condition no n8n
$expira = strtotime('+55 minutes');
```

---

## Segurança

- Nunca concatenar variável em SQL — sempre **prepared statements**.
- Senhas com `password_hash` / `password_verify`, nunca texto puro.
- Credenciais **nunca hardcoded** — ler de variáveis de ambiente.
- Páginas protegidas verificam sessão no topo; sem sessão → redirect para login.
- Endpoints de API validam token/secret no cabeçalho antes de qualquer lógica.

---

## Padrão de resposta de API

```json
{ "ok": true,  "data": { } }
{ "ok": false, "erro": "mensagem legível" }
```

Header sempre: `Content-Type: application/json`.  
Todo endpoint: valida autenticação → valida input → chama **uma** função → responde.

---

## O que NÃO fazer

- ❌ Introduzir lib/dependência sem discutir
- ❌ SQL dentro de páginas ou controllers
- ❌ Função que faz mais de uma coisa
- ❌ Variável com nome genérico (`$data`, `$result`, `$temp`)
- ❌ Código comentado deixado para "depois"
- ❌ Lógica duplicada — parametrize e reutilize
