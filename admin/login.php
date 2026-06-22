<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';

iniciaSessao();

if (adminLogadoId() !== null) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (MODO_DEV && $email === 'admin@calendarioia.com' && $senha === 'admin123') {
        $_SESSION['id_admin'] = 0;
        $_SESSION['nome_admin'] = 'Admin Dev';
        $_SESSION['email_admin'] = $email;
        header('Location: dashboard.php');
        exit;
    }

    $admin = buscaAdminPorEmail($email);

    if ($admin && password_verify($senha, $admin['senha_hash'])) {
        fazLoginAdmin($admin);
        header('Location: dashboard.php');
        exit;
    }

    $erro = traduz('admin_login_erro');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/admin.css" />
</head>
<body>
<div class="admin-login">
  <div class="admin-login-card">
    <div class="admin-login-marca">
      <span class="logo-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </span>
      CalendarioIA
    </div>
    <span class="selo-admin"><?= traduz('admin_painel') ?></span>
    <h1><?= traduz('admin_entrar') ?></h1>
    <p class="subtitulo"><?= traduz('admin_acesse_painel') ?></p>
    <?php if ($erro): ?>
      <div class="erro-msg"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <div class="campo">
        <label><?= traduz('admin_email') ?></label>
        <div class="campo-entrada">
          <span class="icone"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
          <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@calendarioia.com" required />
        </div>
      </div>
      <div class="campo">
        <label><?= traduz('admin_senha') ?></label>
        <div class="campo-entrada">
          <span class="icone"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" name="senha" placeholder="<?= traduz('admin_sua_senha') ?>" required />
        </div>
      </div>
      <button type="submit" class="botao botao-primario botao-espaco-topo"><?= traduz('admin_entrar') ?></button>
    </form>
  </div>
</div>
</body>
</html>
