<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';

iniciaSessao();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome === '' || $email === '' || strlen($senha) < 6) {
        $erro = traduz('erro_campos_invalidos');
    } elseif (buscaUsuarioPorEmail($email)) {
        $erro = traduz('erro_email_existente');
    } else {
        $id_usuario = insereUsuario(['nome' => $nome, 'email' => $email, 'senha' => $senha]);
        fazLoginCliente(['id_usuario' => $id_usuario, 'nome' => $nome, 'email' => $email]);
        header('Location: pago.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — <?= traduz('cadastro_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
</head>
<body>

<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> CalendarioIA</div>
    <a class="botao botao-contorno botao-pequeno" href="login.php"><?= traduz('botao_iniciar_sesion') ?></a>
  </div>
  <div class="conteudo-pagina">
    <div class="etapa-cabecalho">
      <div class="progresso"><span class="completo"></span><span></span><span></span><span></span></div>
      <span class="etapa-rotulo"><?= traduz('cadastro_step_label') ?></span>
    </div>
    <h1 class="tela-titulo"><?= traduz('cadastro_titulo') ?></h1>
    <p class="tela-subtitulo"><?= traduz('cadastro_subtitulo') ?></p>
    <form method="post" action="cadastro.php">
      <?php if ($erro): ?>
        <p class="dica" style="color:var(--red);"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <div class="campo">
        <label><?= traduz('campo_nombre') ?></label>
        <div class="input"><span class="input-icone">🙂</span><input type="text" name="nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" placeholder="Mariana" required /></div>
      </div>
      <div class="campo">
        <label><?= traduz('campo_email') ?></label>
        <div class="input"><span class="input-icone">✉️</span><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="tu@correo.com" required /></div>
      </div>
      <div class="campo">
        <label><?= traduz('campo_senha') ?></label>
        <div class="input"><span class="input-icone">🔒</span><input type="password" name="senha" placeholder="<?= traduz('campo_senha_minima') ?>" required /></div>
        <span class="dica"><?= traduz('hint_senha') ?></span>
      </div>
      <button type="submit" class="botao botao-primario botao-espaco"><?= traduz('botao_continuar') ?></button>
      <p class="nota-legal"><?= traduz('cadastro_microlegal') ?></p>
    </form>
  </div>
</div>

<div class="vista-desktop">
  <div class="cadastro-estrutura">
    <div class="cadastro-marca">
      <div class="marca"><span class="logo"><span data-bot="white" data-size="22"></span></span> CalendarioIA</div>
      <div class="login-icone"><span data-bot="ink" data-size="58"></span></div>
      <h2><?= traduz('cadastro_headline_desktop') ?></h2>
      <p><?= traduz('cadastro_subtitulo_desktop') ?></p>
      <div class="login-recursos">
        <div><span class="login-recurso-check">✓</span> <?= traduz('cadastro_feature_1') ?></div>
        <div><span class="login-recurso-check">✓</span> <?= traduz('cadastro_feature_2') ?></div>
        <div><span class="login-recurso-check">✓</span> <?= traduz('cadastro_feature_3') ?></div>
      </div>
    </div>
    <div class="cadastro-form">
      <form class="form-area" method="post" action="cadastro.php">
        <div class="cadastro-progresso">
          <div class="cadastro-progresso-barras"><span class="completo"></span><span></span><span></span><span></span></div>
          <span class="cadastro-progresso-rotulo"><?= traduz('cadastro_step_label') ?></span>
        </div>
        <h2 class="form-titulo"><?= traduz('cadastro_titulo') ?></h2>
        <p class="form-subtitulo"><?= traduz('cadastro_subtitulo') ?></p>
        <div class="campo">
          <label><?= traduz('campo_nombre') ?></label>
          <div class="input"><span class="input-icone">🙂</span><input type="text" name="nome" placeholder="Mariana" required /></div>
        </div>
        <div class="campo">
          <label><?= traduz('campo_email') ?></label>
          <div class="input"><span class="input-icone">✉️</span><input type="email" name="email" placeholder="tu@correo.com" required /></div>
        </div>
        <div class="campo">
          <label><?= traduz('campo_senha') ?></label>
          <div class="input"><span class="input-icone">🔒</span><input type="password" name="senha" placeholder="<?= traduz('campo_senha_minima') ?>" required /></div>
          <span class="dica"><?= traduz('hint_senha') ?></span>
        </div>
        <button type="submit" class="botao botao-primario botao-espaco"><?= traduz('botao_continuar') ?></button>
        <p class="nota-legal"><?= traduz('cadastro_microlegal') ?></p>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/mascote.js"></script>
</body>
</html>
