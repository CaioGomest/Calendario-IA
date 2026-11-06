# Teto de ~20 requisições PHP simultâneas em produção

**Relatório completo:** `anotacoes/13-capacidade-estresse.md`

Resumo: acima de ~20 requisições verdadeiramente simultâneas, `bipoia.com` passa a devolver **HTTP 500 com corpo vazio** na hora, sem fila, pra qualquer página PHP (não é bug de código nem de banco — é limite de processos PHP do plano de hospedagem). Site recupera sozinho assim que a rajada passa.

**Por que está em `urgente/`:** antes de qualquer ação que gere pico de acesso de verdade (campanha de marketing, lançamento, disparo de lembrete pra muitos usuários de uma vez), vale confirmar com a Hostinger o limite real do plano — senão uma fração real de visitantes vê erro puro nesse momento.

**Status (20/09):** landing page já cacheada (maior fonte provável de pico de tráfego público removida). O teto de ~20 processos em si **continua existindo** — ainda falta: confirmar/aumentar o limite com a Hostinger, e espaçar o disparo de lembretes no n8n (outra fonte provável de pico sincronizado).

## Como resolver (discutido em 30/09)

Duas rotas, nessa ordem de prioridade:

**1. Perguntar pra Hostinger primeiro (mais barato/rápido).** O teto é do plano atual de hospedagem compartilhada — limite de processos PHP simultâneos por conta, gerenciado pelo LiteSpeed deles, não configurável via SSH do lado do tenant. Planos superiores da própria Hostinger (Business, Cloud) costumam vir com limite bem maior, às vezes sem precisar trocar de arquitetura. Abrir chat de suporte perguntando o limite exato do plano atual e o de planos acima.

**2. VPS, só se o upgrade de plano não resolver.** Tira o teto de vez (controle total do PHP-FPM, sem vizinho de conta disputando recurso), mas troca hospedagem gerenciada por administração própria de servidor: atualização de segurança do SO, configuração de PHP/MySQL/Nginx-ou-Apache, firewall, backup, certificado — tudo vira responsabilidade do dono (ou custo de um painel tipo CyberPanel/Plesk em cima). Bastante trabalho de infra adiantado pra um projeto com ~6 usuários ativos hoje, considerando que o problema só aparece em pico.

**Nenhuma das duas foi executada ainda** — ação do usuário (contato com a Hostinger), não algo que dá pra resolver só no código.
