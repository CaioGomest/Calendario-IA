# Risco de lembrete duplicado (ou perdido) sob volume maior

**Contexto:** durante a discussão de como espaçar o envio de lembretes (`n8n/fluxo-lembretes.json`) pra evitar pico simultâneo no PHP (ver `anotacoes/13-capacidade-estresse.md` e `anotacoes/urgente/teto-20-requisicoes-simultaneas.md`), apareceu um problema de fundo, independente do espaçamento.

## O problema

O fluxo hoje é: `Buscar Pendentes → Enviar Lembrete → Marcar Enviado` (confirmado em `n8n/fluxo-lembretes.json`, ordem real dos nodes). O lembrete só é marcado como enviado **depois** de mandar a mensagem, um por um.

O cron roda a cada 5 minutos. Se uma rodada demorar mais que isso (lote grande, ou se eu adicionar pausa entre envios pra resolver o pico do PHP), a próxima rodada pode começar **antes da anterior marcar os lembretes que já mandou** — aí ela busca os "pendentes" de novo, acha os mesmos, e **manda duplicado**.

## Por que não é só inverter a ordem

Se eu marcar como enviado **antes** de mandar (pra evitar a duplicidade acima), troco o problema: se o envio falhar depois de marcado (WhatsApp fora do ar, erro de rede), o lembrete fica marcado como "feito" mas a pessoa **nunca recebeu** — perda silenciosa, pior de perceber que duplicidade.

## Correção de verdade (não implementada ainda)

Status intermediário — marcar como "**em processamento**" antes de mandar, com um prazo (ex: 2 min). Se passar do prazo sem confirmar, trata como travado e libera pra tentar de novo. Evita duplicar (segunda rodada não pega o que já está em processamento) e evita perda (se travar, destrava depois).

Isso exige:
- Mudança de schema (status com mais estados, não só sim/não, em `eventos` ou tabela equivalente).
- Mudança em `api/internal/lembretesPendentes.php` (filtro) e `api/internal/lembreteEnviado.php` (marcação).
- Ajuste no `n8n/fluxo-lembretes.json`.

## Por que não é urgente agora

Com ~6 usuários ativos e pouco volume, a chance de uma rodada realmente ultrapassar 5 minutos é baixa — hoje é risco teórico, não real. Fica registrado aqui pra não esquecer e reavaliar **quando o volume de usuários/lembretes crescer**.
