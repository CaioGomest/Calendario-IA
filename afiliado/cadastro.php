<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';

iniciaSessao();

$erro = '';

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
        $id_afiliado = insereAfiliado(['nome' => $nome, 'email' => $email, 'senha' => $senha]);
        $afiliado = buscaAfiliadoPorId($id_afiliado);
        fazLoginAfiliado($afiliado);
        header('Location: painel.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(nomeApp()) ?> — Cadastro de Afiliado</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css?v=<?= versaoAsset('assets/css/cliente.css') ?>" />
</head>
<body>
<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
    <a class="botao botao-contorno botao-pequeno" href="login.php">Entrar</a>
  </div>
  <div class="conteudo-pagina">
    <h1 class="tela-titulo">Seja um afiliado</h1>
    <p class="tela-subtitulo">Cadastre-se e ganhe comissão por cada indicação paga.</p>
    <form method="post" action="cadastro.php">
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
      <div class="link-central">Já é afiliado? <a class="link" href="login.php">Entrar</a></div>
    </form>
  </div>
</div>
<script src="../assets/js/mascote.js"></script>
</body>
</html>
