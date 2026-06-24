<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesGoogle.php';

iniciaSessao();

if (usuarioLogadoId()) {
    header('Location: home.php');
    exit;
}

$erro = $_SESSION['erro_google'] ?? '';
unset($_SESSION['erro_google']);

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
<title><?= htmlspecialchars(nomeApp()) ?> — <?= traduz('login_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
</head>
<body>
<div class="login-estrutura">

  <div class="login-marca">
    <div class="marca"><span class="logo"><span data-bot="white" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
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
        <div class="input"><span class="input-icone">✉️</span><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="<?= traduz('email_placeholder') ?>" required /></div>
      </div>
      <div class="campo">
        <label><?= traduz('campo_senha') ?></label>
        <div class="input"><span class="input-icone">🔒</span><input type="password" name="senha" placeholder="<?= traduz('senha_placeholder') ?>" required /><button type="button" class="input-olho" data-toggle-senha aria-label="<?= traduz('senha_mostrar') ?>">👁️</button></div>
      </div>
      <a class="link esqueci-senha" href="recuperar.php"><?= traduz('link_esqueceu_senha') ?></a>
      <button type="submit" class="botao botao-primario botao-espaco"><?= traduz('botao_entrar') ?></button>
      <div class="divisor" style="margin:16px 0;"><?= traduz('divisor_ou') ?></div>
      <a href="<?= htmlspecialchars(geraUrlAutorizacaoGoogle()) ?>" class="botao botao-google">
        <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59A14.5 14.5 0 0 1 9.5 24c0-1.59.28-3.14.76-4.59l-7.98-6.19A23.93 23.93 0 0 0 0 24c0 3.77.9 7.35 2.56 10.56l7.97-5.97z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 5.97C6.51 42.62 14.62 48 24 48z"/></svg>
        <?= traduz('botao_google_login') ?>
      </a>
      <div class="link-central"><?= traduz('login_sem_conta') ?> <a class="link" href="cadastro.php"><?= traduz('link_criar_conta') ?></a></div>
    </form>
  </div>

</div>
<script src="../assets/js/mascote.js"></script>
<script src="../assets/js/senha.js"></script>
</body>
</html>
