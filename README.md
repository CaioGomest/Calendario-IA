# CalendarioIA

Sistema de agendamento pessoal via **WhatsApp + Google Calendar**. O usuário fala em linguagem natural no WhatsApp, um agente de IA (Gemini, orquestrado no n8n) interpreta e cria, lista, edita ou cancela eventos e envia lembretes.

## O que tem neste repositório

Apenas a camada **PHP**:

- Endpoints internos consumidos pelo n8n (`api/internal/`)
- Painel admin (`admin/`)
- Área do cliente (`cliente/`)
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
  usuarios.php      GET  — dados do usuário por telefone
  evento.php        POST/PUT/DELETE — gerencia eventos
  eventos.php       GET  — lista eventos do usuário
  log.php           POST — salva log de mensagem
  lembreteEnviado.php    PUT — marca lembrete como enviado
  lembretesPendentes.php GET — eventos com lembrete pendente
  token.php         POST — renova token Google OAuth
  sessao.php        GET/POST — contexto de conversa
  eventosGoogle.php GET  — busca eventos direto no Google Calendar
  transacao.php     POST/GET/DELETE — registra transações financeiras

admin/              Painel do operador
  login.php
  dashboard.php     Métricas, gráficos de atividade, eventos recentes
  usuarios.php      Listagem, busca, criar/editar/apagar usuários
  planos.php        CRUD de planos (integrado ao Stripe)
  configuracao.php  Variáveis de sistema, preferências, modo dev

cliente/            Área do usuário final
  login.php / cadastro.php / recuperar.php / redefinir.php
  pago.php          Checkout via Stripe (seleção de plano)
  google.php / google-callback.php   OAuth Google Calendar
  whatsapp.php      Configuração do número WhatsApp
  home.php          Status das integrações + próximos eventos
  conta.php         Plano, modo silêncio, recordatórios, desconectar
  financas.php      Controle financeiro (transações, gráficos, categorias)

funcoes/            Toda lógica de dados (sem SQL solto em páginas)
idiomas/            i18n — es-MX.php e pt-BR.php
database/banco.sql  Schema completo
```

## Implementado

- **Autenticação** — login/cadastro com email+senha e Google OAuth, recuperação de senha por email
- **Onboarding** — 4 passos: conta → pagamento → Google Calendar → WhatsApp
- **Pagamento** — Stripe Checkout com seleção de plano, webhook de confirmação, portal de faturamento
- **Área do cliente** — home com próximos eventos, minha conta, controle financeiro
- **Controle financeiro** — transações por mês, donut chart por categoria, bar chart 6 meses, paginação, categorias via banco
- **Painel admin** — dashboard com métricas, gestão de usuários, planos e configurações do sistema
- **Endpoints n8n** — todos implementados e protegidos por `X-Internal-Secret`
- **i18n** — espanhol mexicano (padrão) e português BR via arrays, sem dependências externas

## Preview

| Login | Cadastro |
|---|---|
| <img width="370" height="579" alt="Login" src="https://github.com/user-attachments/assets/2aa06781-3e25-4aa7-b927-792411f0c33a" /> | <img width="346" height="571" alt="Cadastro" src="https://github.com/user-attachments/assets/5788c189-10bb-40ae-b161-a5bcf8b2ccae" /> |
