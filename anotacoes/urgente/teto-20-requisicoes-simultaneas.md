# Teto de ~20 requisições PHP simultâneas em produção

**Relatório completo:** `anotacoes/13-capacidade-estresse.md`

Resumo: acima de ~20 requisições verdadeiramente simultâneas, `bipoia.com` passa a devolver **HTTP 500 com corpo vazio** na hora, sem fila, pra qualquer página PHP (não é bug de código nem de banco — é limite de processos PHP do plano de hospedagem). Site recupera sozinho assim que a rajada passa.

**Por que está em `urgente/`:** antes de qualquer ação que gere pico de acesso de verdade (campanha de marketing, lançamento, disparo de lembrete pra muitos usuários de uma vez), vale confirmar com a Hostinger o limite real do plano — senão uma fração real de visitantes vê erro puro nesse momento.

**Status (20/09):** landing page já cacheada (maior fonte provável de pico de tráfego público removida). O teto de ~20 processos em si **continua existindo** — ainda falta: confirmar/aumentar o limite com a Hostinger, e espaçar o disparo de lembretes no n8n (outra fonte provável de pico sincronizado).
