# 01 — Mapa de superfície

**Data:** 2026-09-19
**Tipo:** inventário (leitura de código). Nenhuma alteração feita.

Objetivo: listar cada ponto de entrada, o que aceita e o que exige, pra guiar os testes seguintes. Onde já saltou um ponto de atenção, está marcado com **[ver tópico NN]** — não é conclusão, é pista.

---

## 1. Páginas públicas (sem sessão)

| Página | Método | Entradas | Observação |
|---|---|---|---|
| `index.php` | GET | `?ref` (afiliado), `?idioma` | landing; grava cookie de afiliado e de idioma. Redireciona logado → `cliente/home`. |
| `privacidade.php` | GET | — | estática |
| `termos.php` | GET | — | estática |
| `cliente/cadastro.php` | GET/POST | `nome, email, senha`, reCAPTCHA | cria usuário, faz login, manda e-mail boas-vindas, redireciona `localizacao` |
| `cliente/login.php` | GET/POST | `email, senha`, reCAPTCHA | **[ver 11]** sem limite de tentativas aparente |
| `cliente/recuperar.php` | GET/POST | `email`, reCAPTCHA | gera token de recuperação |
| `cliente/redefinir.php` | GET/POST | `?token`, `senha` | **[ver 02]** validade/uso único do token |
| `cliente/google-callback.php` | GET | `?code, ?state` (OAuth) | recebe retorno do Google |
| `afiliado/cadastro.php` | GET/POST | `nome, email, senha, …` | cria afiliado |
| `afiliado/login.php` | GET/POST | `email, senha` | login afiliado |
| `admin/login.php` | GET/POST | `email, senha` | login admin |

## 2. Área cliente (exige sessão de cliente)

`exigeLoginCliente()` no topo. Algumas exigem também assinatura ativa (`exigeAssinaturaAtiva`).

| Página | Exige assinatura? | Entradas principais | Observação |
|---|---|---|---|
| `cliente/home.php` | não | — | painel |
| `cliente/localizacao.php` | não | `fuso_horario, idioma` | etapa de onboarding |
| `cliente/pago.php` | não | `confirmar, escolher_plano, id_plano` | checkout. **Local = simulado; prod = Stripe live [não testar em prod]** |
| `cliente/google.php` | **sim** | `acao=conectar` | inicia OAuth |
| `cliente/whatsapp.php` | **sim** | `codigo_pais, telefone` | vincula telefone |
| `cliente/financas.php` | não* | CRUD `tipo,valor,descricao,categoria,data_transacao`, `?pagina,?mes,?ano` | *confirmar guarda; entra em bastante input do usuário **[ver 06/10]** |
| `cliente/conta.php` | não | `mudar_idioma, mudar_fuso, cancelar_plano, portal_stripe, desconectar_google, …` | muitas ações num arquivo só |

## 3. Área admin (exige sessão de admin)

`exigeLoginAdmin()`. Páginas: `dashboard, usuarios, assinaturas, planos, afiliados, saques, configuracao`. Ações POST por `acao` (ex.: `alternar_status`, `criar`, editar plano, aprovar saque). São as ações de maior privilégio do sistema — foco de autorização **[ver 03]**.

## 4. Área afiliado (exige sessão de afiliado)

`exigeLoginAfiliado()`. Páginas: `painel, comissoes, link, saque`. `saque.php` aceita POST de solicitação de saque — envolve valor/dinheiro **[ver 09]**.

## 5. Endpoints internos `api/internal/` (exigem `X-Internal-Secret`)

Todos validam o header via `validaSecretInterno()`, **exceto** `stripe-webhook.php`, que usa validação de assinatura da Stripe (correto). Todos respondem JSON.

| Endpoint | Métodos | Entradas |
|---|---|---|
| `usuarios.php` | GET | `?telefone` |
| `evento.php` | POST/PUT/DELETE | JSON evento |
| `eventos.php` | GET | `?id_usuario, ?data_inicio, ?data_fim, ?limite` |
| `eventosGoogle.php` | GET | `?id_usuario, ?fuso_horario, ?limite, ?ordem, ?data_*` |
| `transacao.php` | POST/GET/DELETE | JSON / `?id_usuario, ?mes, ?ano, ?por_pagina` |
| `recorrencia.php` | POST/GET/DELETE | JSON / `?id_usuario` |
| `sessao.php` | GET/POST | `?id_usuario` / JSON contexto |
| `token.php` | POST/DELETE | JSON `id_usuario, token_*` |
| `log.php` | POST | JSON mensagem |
| `iaUso.php` | POST | JSON custo IA |
| `lembretesPendentes.php` | GET | — |
| `lembreteEnviado.php` | PUT | JSON |
| `stripe-webhook.php` | POST | payload Stripe + assinatura |

**Observação de arquitetura:** os endpoints internos confiam no `id_usuario` que vem no corpo/query, sem sessão — é o modelo esperado (quem chama é o n8n com o segredo). Consequência: **a força de todo esse conjunto depende de um único segredo compartilhado**. Se `INTERNAL_SECRET` for fraco/previsível, qualquer um que o descubra manipula dados de qualquer usuário passando o `id_usuario`. Confirmar o valor real **[ver 04]** — sem imprimir o segredo no relatório.

---

## 6. Modelo de autenticação (de `funcoesAuth.php`)

- **3 papéis** (cliente / admin / afiliado), cada um com sua chave de sessão (`id_usuario`, `id_admin`, `id_afiliado`). Guards checam a chave específica. Bom: um cliente logado não vira admin só por ter sessão.
- **Segredo interno:** comparado com `===`. Ponto de atenção: não é comparação de tempo constante (`hash_equals`) — canal lateral de timing teórico **[ver 04]**. Severidade provável baixa (segredo longo torna o ataque impraticável na prática), mas fica registrado.
- **Login não regenera o ID de sessão** (`fazLoginCliente/Admin/Afiliado` só populam `$_SESSION`, sem `session_regenerate_id`). Abre porta pra fixação de sessão **[ver 02/07]**.
- **reCAPTCHA v3** protege cadastro/login/recuperação, mas só fora do MODO_DEV. No local (dev) ele sempre passa — então o teste de brute force local mede o comportamento *sem* o captcha; o captcha em prod é uma camada a mais a confirmar.

---

## 7. Banco (15 tabelas)

`administradores, afiliados, afiliados_cliques, afiliados_comissoes, afiliados_indicacoes, afiliados_saques, categorias, configuracoes, eventos, logs_mensagens, pagamentos, planos, sessoes_conversa, transacoes, usuarios`

Detalhe de schema, índices e integridade → **[tópico 14]**.

---

## 8. Próximos tópicos priorizados a partir daqui

Ordem sugerida pelo risco de negócio:
1. **04** endpoints internos + força do segredo (é a chave de tudo)
2. **03** autorização / acesso cruzado entre contas
3. **02** autenticação (sessão, token de recuperação)
4. **05/06** SQL e XSS nos campos de usuário (finanças, conta)
5. **08/09** dinheiro (Stripe, afiliados)
6. **10/11** validação e limites
7. **13/14** capacidade e banco
8. **15** infra/exposição em produção (só GET)
9. **12** n8n (quando você passar a chave)
