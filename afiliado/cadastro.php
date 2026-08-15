<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';

iniciaSessao();

$erro = '';
$enviado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if ($nome === '' || $email === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/', $senha)) {
        $erro = 'A senha deve ter ao menos 8 caracteres, com maiúscula, minúscula, número e símbolo.';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'As senhas não coincidem.';
    } elseif (buscaAfiliadoPorEmail($email)) {
        $erro = 'Já existe um afiliado com este e-mail.';
    } else {
        insereAfiliado(['nome' => $nome, 'email' => $email, 'senha' => $senha, 'ativo' => 0]);
        $enviado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg" />
<title><?= htmlspecialchars(nomeApp()) ?> — Cadastro de Afiliado</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css?v=<?= versaoAsset('assets/css/cliente.css') ?>" />
</head>
<body>
<div class="login-estrutura">
  <div class="login-marca">
    <div class="marca"><span class="logo"><span data-bot="white" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
    <div class="login-titulo login-titulo-mobile">
      <div class="login-icone"><span data-bot="ink" data-size="56"></span></div>
      <h1>Seja um afiliado</h1>
      <p>Ganhe comissão por cada indicação paga.</p>
    </div>
    <div class="login-titulo login-titulo-desktop">
      <div class="login-icone"><span data-bot="ink" data-size="62"></span></div>
      <h1>Seja um afiliado</h1>
      <p>Cadastre-se e ganhe comissão por cada indicação paga.</p>
      <div class="login-recursos">
        <div><span class="login-recurso-check">✓</span> Acompanhe indicações em tempo real</div>
        <div><span class="login-recurso-check">✓</span> Comissão sobre cada pagamento confirmado</div>
        <div><span class="login-recurso-check">✓</span> Link de indicação exclusivo</div>
      </div>
    </div>
  </div>
  <div class="login-form">
    <?php if ($enviado): ?>
    <div class="form-area">
      <h2 class="form-titulo">Cadastro enviado!</h2>
      <p class="dica">Seu cadastro está em análise. Assim que o administrador aprovar, você poderá entrar no seu painel de afiliado.</p>
      <div class="link-central"><a class="link" href="login">Voltar para o login</a></div>
    </div>
    <?php else: ?>
    <form class="form-area" method="post" action="cadastro">
      <h2 class="form-titulo">Cadastrar</h2>
      <?php if ($erro): ?>
        <p class="dica" style="color:var(--red);"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <div class="campo">
        <label>Nome</label>
        <div class="input"><span class="input-icone">🙂</span><input type="text" name="nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required /></div>
      </div>
      <div class="campo">
        <label>E-mail</label>
        <div class="input"><span class="input-icone">✉️</span><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required /></div>
      </div>
      <div class="campo">
        <label>Senha</label>
        <div class="input"><span class="input-icone">🔒</span><input type="password" name="senha" required /></div>
      </div>
      <div class="campo">
        <label>Confirmar senha</label>
        <div class="input"><span class="input-icone">🔒</span><input type="password" name="confirmar_senha" required /></div>
      </div>
      <button type="submit" class="botao botao-primario botao-espaco">Cadastrar</button>
      <div class="link-central">Já é afiliado? <a class="link" href="login">Entrar</a></div>
    </form>
    <?php endif; ?>
  </div>
</div>
<script src="../assets/js/mascote.js"></script>
</body>
</html>
