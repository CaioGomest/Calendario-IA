<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesEmail.php';

iniciaSessao();

$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email !== '') {
        $usuario = buscaUsuarioPorEmail($email);
        if ($usuario) {
            $token = criaTokenRecuperacao((int)$usuario['id_usuario']);
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['SCRIPT_NAME']);
            $link = $base_url . '/redefinir.php?token=' . $token;

            $assunto = sprintf(traduz('email_recuperacao_assunto'), nomeApp());
            $corpo = sprintf(traduz('email_recuperacao_corpo'),
                htmlspecialchars($usuario['nome']),
                $link,
                $link,
                nomeApp()
            );

            enviaEmail($email, $assunto, $corpo);
        }
    }

    $msg = traduz('recuperar_email_enviado');
}
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(nomeApp()) ?> — <?= traduz('recuperar_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
</head>
<body>
<div class="login-estrutura">

  <div class="login-marca">
    <div class="marca"><span class="logo"><span data-bot="white" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
    <div class="login-titulo login-titulo-desktop">
      <div class="login-icone"><span data-bot="ink" data-size="62"></span></div>
      <h1><?= traduz('recuperar_titulo_desktop') ?></h1>
      <p><?= traduz('recuperar_subtitulo_desktop') ?></p>
    </div>
  </div>

  <div class="login-form">
    <div class="form-area">
      <h2 class="form-titulo"><?= traduz('recuperar_titulo') ?></h2>
      <p class="form-subtitulo"><?= traduz('recuperar_subtitulo') ?></p>

      <?php if ($msg): ?>
        <div class="sucesso-msg" style="margin-bottom:14px;"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <?php if ($erro): ?>
        <p class="dica" style="color:var(--red);"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>

      <form method="post" action="recuperar.php">
        <div class="campo">
          <label><?= traduz('campo_email') ?></label>
          <div class="input"><span class="input-icone">✉️</span><input type="email" name="email" placeholder="<?= traduz('email_placeholder') ?>" required /></div>
        </div>
        <button type="submit" class="botao botao-primario botao-espaco"><?= traduz('recuperar_botao') ?></button>
      </form>
      <div class="link-central" style="margin-top:14px;"><a class="link" href="login.php"><?= traduz('recuperar_voltar_login') ?></a></div>
    </div>
  </div>

</div>
<script src="../assets/js/mascote.js"></script>
</body>
</html>
