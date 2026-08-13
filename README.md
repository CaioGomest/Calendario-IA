# CalendarioIA

Sistema de agendamento pessoal via **WhatsApp + Google Calendar**. O usuário fala em linguagem natural no WhatsApp, um agente de IA (Gemini, orquestrado no n8n) interpreta e cria, lista, edita ou cancela eventos e envia lembretes.

## O que tem neste repositório

Apenas a camada **PHP**:

- Endpoints internos consumidos pelo n8n (`api/internal/`)
- Painel admin (`admin/`)
- Área do cliente (`cliente/`)
- Painel do afiliado (`afiliado/`)
- Landing page (`landpage.php`)

WhatsApp, Evolution API, n8n e Gemini são infraestrutura externa.

## Stack

- PHP puro (sem framework)
- MySQL via PDO (prepared statements)
- jQuery
- HTML + Tailwind CSS (via CDN)
- Fontes Fredoka + Nunito

## Estrutura

```
api/internal/       Endpoints consumidos pelo n8n (protegidos por X-Internal-Secret)
  usuarios.php      GET  — dados do usuário por telefone (tokens Google descriptografados)
  evento.php        POST/PUT/DELETE — gerencia eventos (aceita id_evento ou id_google_event)
  eventos.php       GET  — lista eventos do usuário
  eventosGoogle.php GET  — busca eventos direto no Google Calendar (com filtro de data e ordem)
  log.php           POST — salva log de mensagem
  lembreteEnviado.php    PUT — marca lembrete como enviado
  lembretesPendentes.php GET — eventos com lembrete pendente (inclui idioma do usuário)
  token.php         POST — renova token Google OAuth
  sessao.php        GET/POST — contexto de conversa
  transacao.php     POST/GET/DELETE — registra transações financeiras

api/stripe-webhook.php   Webhook de confirmação de pagamento (também gera comissão de afiliado)

admin/              Painel do operador
  login.php
  dashboard.php     Métricas, gráficos de atividade, eventos recentes
  usuarios.php      Listagem, busca, criar/editar/apagar usuários
  planos.php        CRUD de planos (integrado ao Stripe)
  afiliados.php     Gestão de afiliados e comissões
  saques.php        Aprovação de saques solicitados por afiliados
  configuracao.php  Variáveis de sistema, preferências, modo dev

cliente/            Área do usuário final
  login.php / cadastro.php / recuperar.php / redefinir.php
  pago.php          Checkout via Stripe (seleção de plano)
  google.php / google-callback.php   OAuth Google Calendar (escopo calendar.events)
  whatsapp.php      Configuração do número WhatsApp
  home.php          Status das integrações + próximos eventos
  conta.php         Plano, modo silêncio, recordatórios, desconectar
  financas.php      Controle financeiro (transações, gráficos, categorias)

afiliado/           Painel do programa de afiliados
  login.php / cadastro.php / logout.php
  painel.php        Resumo de cliques, indicações e comissões
  link.php          Link de indicação e materiais de divulgação
  comissoes.php     Extrato de comissões
  saque.php         Solicitação de saque

funcoes/            Toda lógica de dados (sem SQL solto em páginas)
idiomas/            i18n — es-MX.php e pt-BR.php
database/banco.sql  Schema completo (inclui tabelas de afiliados)
```

## Implementado

- **Autenticação** — login/cadastro com email+senha e Google OAuth, recuperação de senha por email
- **Onboarding** — 4 passos: conta → pagamento → Google Calendar → WhatsApp
- **Pagamento** — Stripe Checkout com seleção de plano, webhook de confirmação, portal de faturamento
- **Área do cliente** — home com próximos eventos, minha conta, controle financeiro
- **Controle financeiro** — transações por mês, donut chart por categoria, bar chart 6 meses, paginação, categorias via banco
- **Programa de afiliados** — cadastro/login próprio, link de indicação, tracking de cliques, cálculo de comissão no webhook do Stripe, solicitação e aprovação de saques
- **Painel admin** — dashboard com métricas, gestão de usuários, planos, afiliados/saques e configurações do sistema
- **Endpoints n8n** — todos implementados e protegidos por `X-Internal-Secret`
- **Verificação OAuth Google** — escopo restrito a `calendar.events`, tokens cifrados em repouso (AES-256-GCM), seção de Limited Use na política de privacidade
- **i18n** — espanhol mexicano (padrão) e português BR via arrays, sem dependências externas

## Preview

| Login | Cadastro |
|---|---|
| <img width="370" height="579" alt="Login" src="https://github.com/user-attachments/assets/2aa06781-3e25-4aa7-b927-792411f0c33a" /> | <img width="346" height="571" alt="Cadastro" src="https://github.com/user-attachments/assets/5788c189-10bb-40ae-b161-a5bcf8b2ccae" /> |
