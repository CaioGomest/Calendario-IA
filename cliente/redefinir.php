<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';

iniciaSessao();

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$erro = '';
$sucesso = false;

if ($token === '') {
    header('Location: login.php');
    exit;
}

$usuario = buscaUsuarioPorTokenRecuperacao($token);

if (!$usuario) {
    $erro = traduz('redefinir_token_invalido');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $usuario) {
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    if ($senha !== $confirmar) {
        $erro = traduz('erro_senha_confirmacao');
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $senha)) {
        $erro = traduz('erro_senha_fraca');
    } else {
        atualizaSenhaUsuario((int)$usuario['id_usuario'], $senha);
        deletaTokenRecuperacao((int)$usuario['id_usuario']);
        $sucesso = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(nomeApp()) ?> — <?= traduz('redefinir_titulo') ?></title>
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
      <h1><?= traduz('redefinir_titulo_desktop') ?></h1>
      <p><?= traduz('redefinir_subtitulo_desktop') ?></p>
    </div>
  </div>

  <div class="login-form">
    <div class="form-area">
      <h2 class="form-titulo"><?= traduz('redefinir_titulo') ?></h2>

      <?php if ($sucesso): ?>
        <div class="sucesso-msg" style="margin-bottom:14px;"><?= traduz('redefinir_senha_atualizada') ?></div>
        <a href="login.php" class="botao botao-primario botao-espaco" style="text-align:center;"><?= traduz('botao_entrar') ?></a>
      <?php elseif ($erro && !$usuario): ?>
        <p class="dica" style="color:var(--red);"><?= htmlspecialchars($erro) ?></p>
        <a href="recuperar.php" class="botao botao-primario botao-espaco" style="text-align:center;"><?= traduz('redefinir_pedir_novo') ?></a>
      <?php else: ?>
        <?php if ($erro): ?>
          <p class="dica" style="color:var(--red);"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form method="post" action="redefinir.php">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>" />
          <div class="campo">
            <label><?= traduz('campo_senha') ?></label>
            <div class="input"><span class="input-icone">🔒</span><input type="password" name="senha" placeholder="<?= traduz('campo_senha_minima') ?>" required minlength="8" /><button type="button" class="input-olho" data-toggle-senha aria-label="<?= traduz('senha_mostrar') ?>">👁️</button></div>
            <span class="dica-campo"><?= traduz('hint_senha') ?></span>
          </div>
          <div class="campo">
            <label><?= traduz('campo_confirmar_senha') ?></label>
            <div class="input"><span class="input-icone">🔒</span><input type="password" name="confirmar_senha" placeholder="<?= traduz('senha_confirmar_placeholder') ?>" required /><button type="button" class="input-olho" data-toggle-senha aria-label="<?= traduz('senha_mostrar') ?>">👁️</button></div>
          </div>
          <button type="submit" class="botao botao-primario botao-espaco"><?= traduz('redefinir_botao') ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>

</div>
<script src="../assets/js/mascote.js"></script>
<script src="../assets/js/senha.js"></script>
</body>
</html>
