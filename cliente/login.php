<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';

iniciaSessao();

if (usuarioLogadoId()) {
    header('Location: home.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $usuario = buscaUsuarioPorEmail($email);

    if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
        fazLoginCliente($usuario);
        header('Location: home.php');
        exit;
    }

    $erro = traduz('erro_login_invalido');
}
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — <?= traduz('login_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
</head>
<body>
<div class="login-estrutura">

  <div class="login-marca">
    <div class="marca"><span class="logo"><span data-bot="white" data-size="20"></span></span> CalendarioIA</div>
    <div class="login-titulo login-titulo-mobile">
      <div class="login-icone"><span data-bot="ink" data-size="56"></span></div>
      <h1><?= traduz('login_saudacao') ?></h1>
      <p><?= traduz('login_subtitulo') ?></p>
    </div>
    <div class="login-titulo login-titulo-desktop">
      <div class="login-icone"><span data-bot="ink" data-size="62"></span></div>
      <h1><?= traduz('login_headline_desktop') ?></h1>
      <p><?= traduz('login_subtitulo_desktop') ?></p>
      <div class="login-recursos">
        <div><span class="login-recurso-check">✓</span> <?= traduz('login_feature_1') ?></div>
        <div><span class="login-recurso-check">✓</span> <?= traduz('login_feature_2') ?></div>
        <div><span class="login-recurso-check">✓</span> <?= traduz('login_feature_3') ?></div>
      </div>
      <div class="login-avatares">
        <span class="login-avatares-grupo"><i style="background:#F4B740"></i><i style="background:#22B573"></i><i style="background:#fff"></i></span>
        <?= traduz('login_social_proof') ?>
      </div>
    </div>
  </div>

  <div class="login-form">
    <form class="form-area" method="post" action="login.php">
      <h2 class="form-titulo"><?= traduz('login_titulo') ?></h2>
      <?php if ($erro): ?>
        <p class="dica" style="color:var(--red);"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <div class="campo">
        <label><?= traduz('campo_email') ?></label>
        <div class="input"><span class="input-icone">✉️</span><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="tu@correo.com" required /></div>
      </div>
      <div class="campo">
        <label><?= traduz('campo_senha') ?></label>
        <div class="input"><span class="input-icone">🔒</span><input type="password" name="senha" placeholder="<?= traduz('senha_placeholder') ?>" required /></div>
      </div>
      <a class="link esqueci-senha" href="recuperar.php"><?= traduz('link_esqueceu_senha') ?></a>
      <button type="submit" class="botao botao-primario botao-espaco"><?= traduz('botao_entrar') ?></button>
      <div class="link-central"><?= traduz('login_sem_conta') ?> <a class="link" href="cadastro.php"><?= traduz('link_criar_conta') ?></a></div>
    </form>
  </div>

</div>
<script src="../assets/js/mascote.js"></script>
</body>
</html>
