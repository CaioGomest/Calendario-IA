# Retry automático em criar evento/transação/recorrência — adiado de propósito

**Data da decisão:** 2026-09-20
**Contexto completo:** `anotacoes/13-capacidade-estresse.md` (teto de ~20 processos PHP simultâneos em produção) e a conversa que levou a essa decisão.

## O que foi decidido

Ao implementar retry com backoff nas chamadas do n8n pro nosso PHP (mitigação pro teto de processos), **só protegi as chamadas seguras de repetir** (leituras, salvar sessão, token, log). **Não** adicionei retry em `evento.php`, `transacao.php` e `recorrencia.php` (POST de criação) — essas fazem `INSERT` simples, sem trava anti-duplicata, então repetir automaticamente arrisca duplicar evento/gasto/assinatura do usuário.

## Por que não resolver isso agora

Meta declarada: aguentar até uns 1.000 usuários sem se preocupar. Na conta feita: mesmo 1.000 usuários batendo o limite diário de mensagens dá menos de 1 msg/s de média no sistema — as falhas por sobrecarga só aconteceriam nos picos raros (rajada de lembrete, pico na landing), que já estão sendo atacados por outras frentes (cache da landing, espaçar lembrete, aumentar limite de processos com a Hostinger). Construir a trava anti-duplicata (mudança de banco + 3 endpoints + n8n) agora seria resolver, com esforço real, um problema que pode nem se manifestar na prática.

Hoje, quando uma criação falha por sobrecarga, o usuário recebe mensagem de erro (não é silencioso) e só manda de novo — chato, mas não duplica nem perde dado.

## Condição pra revisitar

Não é "quando crescer" vagamente — é: **medir quantas vezes uma criação (evento/transação/recorrência) falha por erro do servidor** (logar isso seria o primeiro passo, hoje não é medido separadamente). Se isso aparecer com frequência real (não só teórica) conforme o uso cresce, aí sim vale construir a trava anti-duplicata com dado na mão, não especulação.

## O que a correção de verdade envolveria, quando for a hora

Chave de idempotência por tentativa: o n8n gera um identificador único por ação, manda junto na chamada; o PHP guarda esse identificador (índice único no banco) e ignora se a mesma chave chegar de novo — mesmo padrão que `registraPagamento()` já usa hoje com `INSERT IGNORE` + `stripe_invoice_id`.
