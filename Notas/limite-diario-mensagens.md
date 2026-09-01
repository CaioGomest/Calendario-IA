# Limite diário de mensagens (rate limit de custo de IA)

> Implementado em 2026-08-16.

## Por quê

O plano mais barato do produto custa R$1/mês e oferece "eventos ilimitados pelo WhatsApp". Cada mensagem que o usuário manda aciona a IA (Groq/Llama, via n8n) — e cada chamada à IA tem custo real. Sem nenhum limite técnico, um usuário (por engano, abuso ou bug) poderia mandar centenas ou milhares de mensagens num dia, fazendo o custo de IA daquele usuário ultrapassar de longe o que ele paga de assinatura.

Este documento existe pra explicar o que foi feito e por quê. O bloqueio real (item 4) já está implementado no workflow do n8n versionado neste repo — só falta reimportar no seu n8n.

## O que foi implementado

### 1. Limite configurável no admin

Novo campo em **Admin → Configuração → Limites**: "Limite diário de mensagens por usuário", padrão **40 mensagens/dia**. Fica salvo na tabela `configuracoes` (chave `limite_diario_mensagens`), o mesmo mecanismo já usado para outras configs do sistema (nome do app, moeda, idioma).

- `funcoes/funcoesConfiguracao.php` — nova função `limiteDiarioMensagens()`, lê o valor salvo (ou usa 40 como default).
- `admin/configuracao.php` — novo card "Limites" com o formulário.

### 2. Contagem de mensagens do dia

- `funcoes/funcoesEventos.php` — nova função `contaMensagensHojePorUsuario($id_usuario)`, conta quantas mensagens de **entrada** (mandadas pelo usuário) esse usuário já enviou hoje, olhando a tabela `logs_mensagens` que já existia (cada mensagem processada pelo n8n é logada lá via `api/internal/log.php`).
- Só conta `direcao = 'entrada'`. Mensagens de `saida` (respostas do bot) não têm custo de IA, então não entram na conta.
- Foi criado um índice novo no banco (`idx_logs_usuario_dia`, em `id_usuario, direcao, criado_em`) porque essa consulta roda a cada mensagem recebida — sem índice, ficaria lenta conforme o histórico de mensagens crescer.

### 3. Exposição via API — o ponto de controle real

`api/internal/usuarios.php` é o endpoint que o **n8n já chama a cada mensagem recebida, antes de acionar a IA**, pra buscar os dados do usuário pelo telefone. Ele agora retorna três campos novos:

```json
{
  "ok": true,
  "data": {
    ...campos que já existiam...,
    "mensagens_hoje": 3,
    "limite_diario_mensagens": 40,
    "limite_atingido": false
  }
}
```

Esse é o lugar certo pra expor essa informação, porque é a última parada antes da IA ser chamada.

### 4. Bloqueio real no fluxo n8n

O fluxo de atendimento (WhatsApp → n8n → IA) roda fora deste repositório (é infraestrutura externa, ver `AGENTS.md`), mas o workflow em si está versionado aqui em `n8n/fluxo-agente-whatsapp.json` — então o ajuste **já foi feito no JSON**, só falta você reimportar esse arquivo no seu n8n.

O que mudou no workflow (`n8n/fluxo-agente-whatsapp.json`):

- Novo node **"Limite Atingido?"** (IF), logo depois de `Logar Entrada` e antes de `Buscar Sessao`/`Montar Prompt`/`Groq` — checa `Buscar Usuario.data.limite_atingido`.
- Se `true`: vai pro novo node **"Responder Limite Diario"**, que manda uma mensagem padrão pro usuário (em pt-BR ou es-MX, conforme o idioma dele) e **não chama o Groq/IA** — fluxo termina ali, sem custo de IA.
- Se `false`: segue o fluxo normal (`Buscar Sessao` → ... → `Groq` → ...), sem nenhuma mudança de comportamento.

**Importante**: isso só entra em vigor quando você **reimportar** `n8n/fluxo-agente-whatsapp.json` no seu n8n (sobrescrevendo o workflow atual). Até lá, o n8n continua rodando a versão antiga, sem o bloqueio.

## Limitações conhecidas (aceitas conscientemente pra v1)

- **Fuso horário**: a contagem usa `CURDATE()` do servidor MySQL, não o fuso próprio de cada usuário (`fuso_horario` na tabela `usuarios`). Na prática, o "dia" de reset do limite pode variar em algumas horas ao redor da meia-noite dependendo do fuso do usuário. Não compromete o objetivo (conter custo agregado), só desloca a janela de reset em casos raros. Ajustar isso exigiria `CONVERT_TZ` por usuário, mais complexidade — deixado de fora por ora ("menos código" é prioridade do projeto).
- **Limite é global, não por plano**: hoje é um único número pra todos os usuários, independente do plano assinado. Se no futuro fizer sentido ter limites diferentes por plano, dá pra evoluir a função `limiteDiarioMensagens()` pra receber o plano do usuário como parâmetro.

## Arquivos alterados

| Arquivo | O que mudou |
|---|---|
| `funcoes/funcoesConfiguracao.php` | `limiteDiarioMensagens()` |
| `funcoes/funcoesEventos.php` | `contaMensagensHojePorUsuario($id_usuario)` |
| `api/internal/usuarios.php` | 3 campos novos no JSON de resposta |
| `admin/configuracao.php` | card "Limites" + handler `salvar_limites` |
| `idiomas/pt-BR.php`, `idiomas/es-MX.php` | traduções do card novo |
| `database/banco.sql` | índice `idx_logs_usuario_dia` |
| `n8n/fluxo-agente-whatsapp.json` | nodes `Limite Atingido?` e `Responder Limite Diario` |

## Como aplicar

1. **Backend** (já em produção): funções, endpoint, admin, índice — nada a fazer, já está tudo no ar.
2. **n8n**: abrir seu n8n → importar `n8n/fluxo-agente-whatsapp.json` por cima do workflow atual (ou copiar manualmente os dois nodes novos, se preferir não reimportar tudo) → ativar/salvar.
3. Testar mandando várias mensagens seguidas de um número de teste até ultrapassar o limite configurado, conferir que a mensagem de "limite atingido" chega e que o Groq para de ser chamado (dá pra ver isso no histórico de execuções do n8n).
