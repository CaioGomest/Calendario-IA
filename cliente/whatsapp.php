<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../config/config.php';

iniciaSessao();
exigeLoginCliente();

$erro_whatsapp = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    if ($whatsapp !== '') {
        $telefone_formatado = '+52' . preg_replace('/\D+/', '', $whatsapp);
        $existente = buscaUsuarioPorTelefone($telefone_formatado);
        if ($existente && (int)$existente['id_usuario'] !== usuarioLogadoId()) {
            $erro_whatsapp = traduz('erro_telefone_existe');
        } else {
            atualizaTelefoneUsuario(usuarioLogadoId(), $telefone_formatado);
            header('Location: home.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(nomeApp()) ?> — <?= traduz('whatsapp_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
</head>
<body>

<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
    <a class="botao botao-contorno botao-pequeno" href="google.php"><?= traduz('botao_atras') ?></a>
  </div>
  <div class="conteudo-pagina espacado centralizado">
    <div class="etapa-cabecalho" style="width:100%;">
      <div class="progresso"><span class="completo"></span><span class="completo"></span><span class="completo"></span><span class="completo"></span></div>
      <span class="etapa-rotulo"><?= traduz('whatsapp_step_label') ?></span>
    </div>
    <div class="icone-grande">💬</div>
    <h1 class="tela-titulo"><?= traduz('whatsapp_titulo') ?></h1>
    <p class="tela-subtitulo" style="max-width:30ch;"><?= traduz('whatsapp_subtitulo') ?></p>

    <?php if ($erro_whatsapp): ?>
    <div class="erro-msg" style="margin-bottom:10px;"><?= htmlspecialchars($erro_whatsapp) ?></div>
    <?php endif; ?>

    <form method="post" action="whatsapp.php" style="width:100%;">
      <div class="campo">
        <label><?= traduz('campo_whatsapp') ?></label>
        <div class="input"><span class="input-prefixo">🇲🇽 +52</span><input inputmode="tel" name="whatsapp" placeholder="55 1234 5678" required /></div>
      </div>
      <p class="tela-subtitulo" style="text-align:center;font-size:13px;"><?= traduz('whatsapp_aviso') ?></p>
      <button type="submit" class="botao botao-whatsapp" style="width:100%;"><?= traduz('botao_conectar_whatsapp') ?></button>
    </form>

    <?php if (MODO_DEV): ?>
      <a class="dica" style="text-align:center;display:block;margin-top:4px;" href="home.php">🧪 Modo desarrollo: pular sem conectar</a>
    <?php endif; ?>
    <div class="divisor" style="width:100%;"><?= traduz('whatsapp_sep') ?></div>

    <div class="cartao" style="width:100%;">
      <div class="qr-cartao">
        <div class="qr-caixa">📱</div>
        <div class="qr-texto">
          <b><?= traduz('whatsapp_qr_titulo') ?></b>
          <span><?= traduz('whatsapp_qr_texto') ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="vista-desktop">
  <div class="cadastro-estrutura">
    <div class="cadastro-marca">
      <div class="marca"><span class="logo"><span data-bot="white" data-size="22"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
      <div class="login-icone" style="font-size:46px;">💬</div>
      <h2><?= traduz('whatsapp_titulo_desktop') ?></h2>
      <p><?= traduz('whatsapp_subtitulo_desktop') ?></p>
      <div class="login-recursos">
        <div><span class="login-recurso-check">✓</span> <?= traduz('whatsapp_feature_1') ?></div>
        <div><span class="login-recurso-check">✓</span> <?= traduz('whatsapp_feature_2') ?></div>
      </div>
    </div>
    <div class="cadastro-form">
      <div class="form-area">
        <div class="cadastro-progresso">
          <div class="cadastro-progresso-barras"><span class="completo"></span><span class="completo"></span><span class="completo"></span><span class="completo"></span></div>
          <span class="cadastro-progresso-rotulo"><?= traduz('whatsapp_step_label') ?></span>
        </div>
        <h2 class="form-titulo"><?= traduz('whatsapp_titulo') ?></h2>
        <p class="form-subtitulo"><?= traduz('whatsapp_aviso') ?></p>
        <?php if ($erro_whatsapp): ?>
        <div class="erro-msg" style="margin-bottom:10px;"><?= htmlspecialchars($erro_whatsapp) ?></div>
        <?php endif; ?>
        <form method="post" action="whatsapp.php">
          <div class="campo">
            <label><?= traduz('campo_whatsapp') ?></label>
            <div class="input"><span class="input-prefixo">🇲🇽 +52</span><input inputmode="tel" name="whatsapp" placeholder="55 1234 5678" required /></div>
          </div>
          <button type="submit" class="botao botao-whatsapp botao-espaco"><?= traduz('botao_conectar_whatsapp') ?></button>
          <?php if (MODO_DEV): ?>
            <a class="dica" style="text-align:center;display:block;margin-top:8px;" href="home.php">🧪 Modo desarrollo: pular sem conectar</a>
          <?php endif; ?>
        </form>
        <div class="divisor"><?= traduz('whatsapp_sep') ?></div>
        <div class="cartao">
          <div class="qr-cartao">
            <div class="qr-caixa">📱</div>
            <div class="qr-texto">
              <b><?= traduz('whatsapp_qr_titulo') ?></b>
              <span><?= traduz('whatsapp_qr_texto') ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/mascote.js"></script>
</body>
</html>
