# Verificação OAuth Google — checklist

> Análise feita em 2026-08-15 sobre o fluxo OAuth do CalendarioIA (`funcoes/funcoesGoogle.php`, `cliente/google-callback.php`, `privacidade.php`, `termos.php`, `landpage.php`).

## ✅ Já está correto

- **Escopo mínimo e justificado**: só `openid email profile calendar.events` — não pede `calendar` completo. Evita cair em revisão pesada (CASA security assessment), exigida só pra escopos "restritos".
- **Uso do escopo é coerente com o código**: `calendar.events` é realmente usado (`listaEventosGoogleCalendar`, `buscaStatusEventoGoogle`, criação de evento). O analista do Google confere isso batendo o vídeo de demo com o comportamento real do app.
- **Cláusula de Limited Use já na política de privacidade** (`idiomas/pt-BR.php:589` / `idiomas/es-MX.php:589`): linka a Google API Services User Data Policy explicitamente. Exigência formal do formulário de verificação.
- **Revogação de acesso implementada de verdade**: `desconectaGoogle()` chama `revogaTokenGoogle()`, que revoga o token na própria Google (não só apaga localmente).
- **Tokens cifrados em repouso** (AES-256-GCM em `cifraTokenGoogle`/`decifraTokenGoogle`) — ajuda na seção de segurança do formulário.
- **Homepage pública** com links de Privacidade e Termos no rodapé (`landpage.php`), acessível sem login.
- **`access_type=offline` + `prompt=consent`** — garante refresh_token sempre, necessário pro caso de uso (lembretes assíncronos via WhatsApp).

## ⚠️ Resolver antes de submeter

1. ~~**Verificação de domínio no Google Search Console**~~ ✅ Concluído em 2026-08-15 — `bipoia.com` verificado via registro TXT DNS.

2. **Tela de consentimento OAuth no Cloud Console** (fora do código, trava a análise se faltar)
   - Nome do app, logo, e-mail de suporte, e-mail do desenvolvedor preenchidos.
   - **Domínios autorizados** incluindo `bipoia.com`.
   - Link de Homepage diferente do link de Privacy Policy (já ok — páginas separadas).

3. **Vídeo de demonstração** (obrigatório pro escopo sensível `calendar.events`)
   Gravar em produção (não localhost), mostrando:
   - Login → tela de consentimento do Google (a mesma da screenshot, só que aprovada).
   - Uso efetivo do escopo: criar/ver um evento no calendário através do fluxo do app (mesmo que o gatilho real seja via WhatsApp, mostrar o evento aparecendo no Google Calendar).
   - Onde fica o botão de desconectar/revogar acesso.

4. **Falta o parâmetro `state` no fluxo OAuth** (`funcoes/funcoesGoogle.php:6-16`)
   Não é exigência formal de verificação, mas é a proteção padrão contra CSRF no `google-callback.php`. Não bloqueia a análise — vale corrigir depois.

## Não bloqueia, mas vale nota

- `buscaPerfilGoogle` usa o endpoint antigo `oauth2/v2/userinfo` em vez de `openidconnect.googleapis.com/v1/userinfo`. Ainda funciona, Google não descontinuou.

## Próximos passos sugeridos

- [x] Confirmar domínio verificado no Search Console
- [ ] Revisar preenchimento completo da tela de consentimento no Cloud Console
- [ ] Gravar vídeo de demonstração em produção
- [ ] Submeter para verificação
- [ ] (Opcional, depois) Adicionar `state` com validação CSRF no callback
