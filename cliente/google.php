<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../config/config.php';

iniciaSessao();
exigeLoginCliente();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'conectar') {
    if (MODO_DEV) {
        header('Location: whatsapp.php');
        exit;
    }
    // fora do MODO_DEV falta fazer o fluxo real do OAuth do Google
}
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — <?= traduz('google_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
</head>
<body>

<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> CalendarioIA</div>
    <a class="botao botao-contorno botao-pequeno" href="pago.php"><?= traduz('botao_atras') ?></a>
  </div>
  <div class="conteudo-pagina espacado centralizado">
    <div class="etapa-cabecalho" style="width:100%;">
      <div class="progresso"><span class="completo"></span><span class="completo"></span><span class="completo"></span><span></span></div>
      <span class="etapa-rotulo"><?= traduz('google_step_label') ?></span>
    </div>
    <div class="icone-grande">📅</div>
    <h1 class="tela-titulo"><?= traduz('google_titulo') ?></h1>
    <p class="tela-subtitulo" style="max-width:30ch;"><?= traduz('google_subtitulo') ?></p>

    <div class="cartao" style="width:100%;text-align:left;">
      <div class="permissoes-lista">
        <div class="permissao granted"><span class="permissao-icone">✓</span> <?= traduz('google_perm_1') ?></div>
        <div class="permissao granted"><span class="permissao-icone">✓</span> <?= traduz('google_perm_2') ?></div>
        <div class="permissao denied"><span class="permissao-icone">✕</span> <?= traduz('google_perm_3') ?></div>
      </div>
    </div>

    <form method="post" action="google.php" style="width:100%;">
      <input type="hidden" name="acao" value="conectar" />
      <button type="submit" class="botao botao-branco botao-google" style="width:100%;">
        <span class="logo-google"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84C6.71 7.3 9.14 5.38 12 5.38z"/></svg></span>
        <?= traduz('botao_conectar_google') ?>
      </button>
    </form>
    <?php if (MODO_DEV): ?>
      <div class="dica" style="text-align:center;">🧪 Modo desarrollo: la conexión con Google será simulada.</div>
    <?php endif; ?>
    <p class="nota-legal"><?= traduz('google_microlegal') ?></p>
  </div>
</div>

<div class="vista-desktop">
  <div class="cadastro-estrutura">
    <div class="cadastro-marca">
      <div class="marca"><span class="logo"><span data-bot="white" data-size="22"></span></span> CalendarioIA</div>
      <div class="login-icone" style="font-size:46px;">📅</div>
      <h2><?= traduz('google_titulo') ?></h2>
      <p><?= traduz('google_subtitulo') ?></p>
      <div class="login-recursos">
        <div><span class="login-recurso-check">✓</span> <?= traduz('google_perm_1') ?></div>
        <div><span class="login-recurso-check">✓</span> <?= traduz('google_perm_2') ?></div>
        <div><span class="login-recurso-check">✕</span> <?= traduz('google_perm_3') ?></div>
      </div>
    </div>
    <div class="cadastro-form">
      <div class="form-area">
        <div class="cadastro-progresso">
          <div class="cadastro-progresso-barras"><span class="completo"></span><span class="completo"></span><span class="completo"></span><span></span></div>
          <span class="cadastro-progresso-rotulo"><?= traduz('google_step_label') ?></span>
        </div>
        <h2 class="form-titulo"><?= traduz('google_titulo_desktop') ?></h2>
        <p class="form-subtitulo"><?= traduz('google_subtitulo_desktop') ?></p>
        <div class="cartao-simples" style="margin-bottom:18px;">
          <div class="permissoes-lista">
            <div class="permissao granted"><span class="permissao-icone">✓</span> <?= traduz('google_perm_1') ?></div>
            <div class="permissao granted"><span class="permissao-icone">✓</span> <?= traduz('google_perm_2') ?></div>
            <div class="permissao denied"><span class="permissao-icone">✕</span> <?= traduz('google_perm_3') ?></div>
          </div>
        </div>
        <form method="post" action="google.php">
          <input type="hidden" name="acao" value="conectar" />
          <button type="submit" class="botao botao-branco botao-google" style="width:100%;">
            <span class="logo-google"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84C6.71 7.3 9.14 5.38 12 5.38z"/></svg></span>
            <?= traduz('botao_conectar_google') ?>
          </button>
        </form>
        <p class="nota-legal"><?= traduz('google_microlegal') ?></p>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/mascote.js"></script>
</body>
</html>
