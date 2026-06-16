# CalendarioIA

Sistema de agendamento pessoal via **WhatsApp + Google Calendar**. O usuário fala em linguagem natural no WhatsApp, um agente de IA (Gemini, orquestrado no n8n) interpreta e cria, lista, edita ou cancela eventos e envia lembretes.

## O que tem neste repositório

Apenas a camada **PHP**:

- Endpoints internos consumidos pelo n8n (`api/internal/`) -> Em desenvolvimento
- Painel admin (`admin/`) -> Em desenvolvimento
- Área do cliente (`cliente/`)
- Landing page -> Em desenvolvimento

WhatsApp, Evolution API, n8n e Gemini são infraestrutura externa.

## Stack

- PHP puro (sem framework)
- MySQL via PDO (prepared statements)
- jQuery
- HTML + Tailwind CSS (via CDN)
- Fonte Fredoka

## Preview

| Login | Cadastro |
|---|---|
| <img width="370" height="579" alt="Login" src="https://github.com/user-attachments/assets/2aa06781-3e25-4aa7-b927-792411f0c33a" /> | <img width="346" height="571" alt="Cadastro" src="https://github.com/user-attachments/assets/5788c189-10bb-40ae-b161-a5bcf8b2ccae" /> |

## Status

Em desenvolvimento. Já implementados: backend base e telas da área do cliente (login, onboarding).

Próximos passos: dashboard admin e integração com os endpoints consumidos pelo n8n.
