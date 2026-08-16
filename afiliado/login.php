<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';
require_once __DIR__ . '/../funcoes/funcoesComponentes.php';

iniciaSessao();

if (afiliadoLogadoId()) {
    header('Location: painel');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $afiliado = buscaAfiliadoPorEmail($email);

    if ($afiliado && password_verify($senha, $afiliado['senha_hash'])) {
        if (!$afiliado['ativo']) {
            $erro = 'Seu cadastro ainda está em análise. Aguarde a aprovação do administrador.';
        } else {
            fazLoginAfiliado($afiliado);
            header('Location: painel');
            exit;
        }
    } else {
        $erro = 'E-mail ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg" />
<title><?= htmlspecialchars(nomeApp()) ?> — Painel do Afiliado</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css?v=<?= versaoAsset('assets/css/cliente.css') ?>" />
</head>
<body>
<div class="login-estrutura">
  <div class="login-marca">
    <?php renderizaMarca('white', 20); ?>
    <div class="login-titulo login-titulo-mobile">
      <div class="login-icone"><span data-bot="ink" data-size="56"></span></div>
      <h1>Painel do Afiliado</h1>
      <p>Acompanhe suas indicações e comissões.</p>
    </div>
    <div class="login-titulo login-titulo-desktop">
      <div class="login-icone"><span data-bot="ink" data-size="62"></span></div>
      <h1>Painel do Afiliado</h1>
      <p>Acompanhe suas indicações e comissões em um só lugar.</p>
      <div class="login-recursos">
        <div><span class="login-recurso-check">✓</span> Acompanhe indicações em tempo real</div>
        <div><span class="login-recurso-check">✓</span> Comissão sobre cada pagamento confirmado</div>
        <div><span class="login-recurso-check">✓</span> Link de indicação exclusivo</div>
      </div>
    </div>
  </div>
  <div class="login-form">
    <form class="form-area" method="post" action="login">
      <h2 class="form-titulo">Entrar</h2>
      <?php if ($erro): ?>
        <p class="dica" style="color:var(--red);"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <div class="campo">
        <label>E-mail</label>
        <div class="input"><span class="input-icone">✉️</span><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required /></div>
      </div>
      <div class="campo">
        <label>Senha</label>
        <div class="input"><span class="input-icone">🔒</span><input type="password" name="senha" required /></div>
      </div>
      <button type="submit" class="botao botao-primario botao-espaco">Entrar</button>
      <div class="link-central">Ainda não é afiliado? <a class="link" href="cadastro">Cadastre-se</a></div>
    </form>
  </div>
</div>
<script src="../assets/js/mascote.js"></script>
</body>
</html>
