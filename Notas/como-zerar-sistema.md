# Como zerar o sistema (dados de teste)

> Registrado em 2026-09-01, depois de zerar pra testar cadastro/dashboard do zero.

## O que "zerar" significa

Apagar todo dado de **uso real/teste** do app, mantendo o que é **configuração/catálogo** — pra poder testar o fluxo de cliente (cadastro → conexão Google → WhatsApp → assinatura) começando do zero, sem lixo de testes anteriores atrapalhando números do dashboard.

## Tabelas apagadas (TRUNCATE)

Nessa ordem (por causa de foreign keys, embora `TRUNCATE` com `FOREIGN_KEY_CHECKS=0` dispense a ordem estrita):

```
afiliados_comissoes
afiliados_cliques
afiliados_saques
afiliados_indicacoes
afiliados
pagamentos
logs_ia_uso
logs_mensagens
sessoes_conversa
recorrencias
transacoes
eventos
usuarios
```

## Tabelas mantidas (NUNCA tocar)

```
planos          -- catalogo de planos pagos
administradores -- login do admin
configuracoes   -- ajustes do painel (nome do app, limite de mensagens, etc)
categorias      -- categorias de financas (alimentacao, transporte, etc)
```

## Procedimento seguro

1. **Backup completo antes, sempre.** `mysqldump` do banco inteiro pro servidor (fora do `public_html`, ou em local não servido publicamente) antes de apagar qualquer coisa. Sem exceção.
2. Rodar o `TRUNCATE` das tabelas da lista acima com `SET FOREIGN_KEY_CHECKS=0` (senão a ordem de dependência trava o truncate) e `SET FOREIGN_KEY_CHECKS=1` depois.
3. Conferir contagem de linhas antes/depois de cada tabela (log do que foi apagado).
4. **Nunca deixar um script de "zerar" salvo no repositório ou no servidor** — é sempre uma ferramenta temporária: sobe, roda, confirma resultado, apaga. Isso evita que ele seja disparado sem querer depois (ou versionado com credenciais dentro).
5. Registrar a ação em `atualizacao/logs_atualizacao.txt` com data e o que foi apagado/mantido.

## Onde ficam os backups

Os dumps ficam salvos direto no servidor (fora da pasta pública do site), nomeados tipo `backup_antes_zerar_AAAAMMDD.sql`. Não são commitados no repositório (são grandes e têm dado de usuário real).

## Contexto de quando isso já foi feito

- **2026-08-26**: primeira limpeza de dados de produção (usuários, afiliados, logs, eventos, transações e pagamentos de teste).
- **2026-09-01**: segunda limpeza, mesmo padrão, pra recomeçar os testes de cadastro/assinatura/dashboard do zero depois de corrigir os bugs de inconsistência de plano (ver log de atualização do dia).
