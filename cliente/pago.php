<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../config/config.php';

iniciaSessao();
exigeLoginCliente();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valido = MODO_DEV;

    if (!$valido) {
        $numero_tarjeta = preg_replace('/\s+/', '', $_POST['numero_tarjeta'] ?? '');
        $vencimiento = $_POST['vencimiento'] ?? '';
        $cvc = $_POST['cvc'] ?? '';
        $valido = strlen($numero_tarjeta) >= 13 && $vencimiento !== '' && $cvc !== '';
    }

    if ($valido) {
        atualizaPlanoUsuario(usuarioLogadoId(), 'ativo', date('Y-m-d H:i:s', strtotime('+1 month')));
        header('Location: google.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — <?= traduz('pago_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
</head>
<body>

<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> CalendarioIA</div>
    <a class="botao botao-contorno botao-pequeno" href="cadastro.php"><?= traduz('botao_atras') ?></a>
  </div>
  <div class="conteudo-pagina espacado">
    <div class="etapa-cabecalho">
      <div class="progresso"><span class="completo"></span><span class="completo"></span><span></span><span></span></div>
      <span class="etapa-rotulo"><?= traduz('pago_step_label') ?></span>
    </div>
    <h1 class="tela-titulo"><?= traduz('pago_titulo') ?></h1>

    <div class="plano-cartao">
      <span class="plano-badge"><?= traduz('plan_pill') ?></span>
      <div class="plano-preco"><?= traduz('plan_price') ?> <small><?= traduz('plan_price_small') ?></small></div>
      <div class="plano-recursos">
        <div><span class="plano-recurso-check">✓</span> <?= traduz('plan_feature_1') ?></div>
        <div><span class="plano-recurso-check">✓</span> <?= traduz('plan_feature_2') ?></div>
        <div><span class="plano-recurso-check">✓</span> <?= traduz('plan_feature_3') ?></div>
        <div><span class="plano-recurso-check">✓</span> <?= traduz('plan_feature_4') ?></div>
      </div>
    </div>

    <?php if (MODO_DEV): ?>
      <div class="dica" style="text-align:center;">🧪 Modo desarrollo: puedes continuar sin tarjeta.</div>
    <?php endif; ?>
    <form method="post" action="pago.php">
      <div class="cartao-simples">
        <div class="campo" style="margin-top:0;">
          <label><?= traduz('campo_numero_tarjeta') ?></label>
          <div class="input"><span class="input-icone">💳</span><input inputmode="numeric" name="numero_tarjeta" class="js-cartao-numero" maxlength="19" placeholder="1234 5678 9012 3456" <?= MODO_DEV ? '' : 'required' ?> /></div>
        </div>
        <div class="campos-linha">
          <div class="campo"><label><?= traduz('campo_vencimiento') ?></label><div class="input"><input inputmode="numeric" name="vencimiento" class="js-vencimento" maxlength="5" placeholder="MM/AA" <?= MODO_DEV ? '' : 'required' ?> /></div></div>
          <div class="campo"><label><?= traduz('campo_cvc') ?></label><div class="input"><input inputmode="numeric" name="cvc" class="js-cvc" maxlength="4" placeholder="123" <?= MODO_DEV ? '' : 'required' ?> /></div></div>
        </div>
        <div class="campo">
          <label><?= traduz('campo_nombre_tarjeta') ?></label>
          <div class="input"><input type="text" name="nombre_tarjeta" placeholder="Mariana López" <?= MODO_DEV ? '' : 'required' ?> /></div>
        </div>
      </div>
      <button type="submit" class="botao botao-primario botao-espaco"><?= traduz('botao_empezar_prueba') ?></button>
      <div class="confianca"><?= traduz('pago_trust') ?></div>
    </form>
  </div>
</div>

<div class="vista-desktop">
  <div class="cadastro-estrutura">
    <div class="cadastro-marca">
      <div class="marca"><span class="logo"><span data-bot="white" data-size="22"></span></span> CalendarioIA</div>
      <div class="plano-cartao" style="background:rgba(255,255,255,.12);">
        <span class="plano-badge"><?= traduz('plan_pill') ?></span>
        <div class="plano-preco"><?= traduz('plan_price') ?> <small><?= traduz('plan_price_small') ?></small></div>
        <div class="plano-recursos">
          <div><span class="plano-recurso-check">✓</span> <?= traduz('plan_feature_1') ?></div>
          <div><span class="plano-recurso-check">✓</span> <?= traduz('plan_feature_2') ?></div>
          <div><span class="plano-recurso-check">✓</span> <?= traduz('plan_feature_3') ?></div>
          <div><span class="plano-recurso-check">✓</span> <?= traduz('plan_feature_4') ?></div>
        </div>
      </div>
      <div class="login-avatares"><?= traduz('pago_trust') ?></div>
    </div>
    <div class="cadastro-form">
      <form class="form-area" method="post" action="pago.php">
        <div class="cadastro-progresso">
          <div class="cadastro-progresso-barras"><span class="completo"></span><span class="completo"></span><span></span><span></span></div>
          <span class="cadastro-progresso-rotulo"><?= traduz('pago_step_label') ?></span>
        </div>
        <h2 class="form-titulo"><?= traduz('pago_titulo') ?></h2>
        <p class="form-subtitulo"><?= traduz('pago_subtitulo_desktop') ?></p>
        <?php if (MODO_DEV): ?>
          <div class="dica">🧪 Modo desarrollo: puedes continuar sin tarjeta.</div>
        <?php endif; ?>
        <div class="campo">
          <label><?= traduz('campo_numero_tarjeta') ?></label>
          <div class="input"><span class="input-icone">💳</span><input inputmode="numeric" name="numero_tarjeta" class="js-cartao-numero" maxlength="19" placeholder="1234 5678 9012 3456" <?= MODO_DEV ? '' : 'required' ?> /></div>
        </div>
        <div class="campos-linha">
          <div class="campo"><label><?= traduz('campo_vencimiento') ?></label><div class="input"><input inputmode="numeric" name="vencimiento" class="js-vencimento" maxlength="5" placeholder="MM/AA" <?= MODO_DEV ? '' : 'required' ?> /></div></div>
          <div class="campo"><label><?= traduz('campo_cvc') ?></label><div class="input"><input inputmode="numeric" name="cvc" class="js-cvc" maxlength="4" placeholder="123" <?= MODO_DEV ? '' : 'required' ?> /></div></div>
        </div>
        <div class="campo">
          <label><?= traduz('campo_nombre_tarjeta') ?></label>
          <div class="input"><input type="text" name="nombre_tarjeta" placeholder="Mariana López" <?= MODO_DEV ? '' : 'required' ?> /></div>
        </div>
        <button type="submit" class="botao botao-primario botao-espaco"><?= traduz('botao_empezar_prueba') ?></button>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="../assets/js/mascote.js"></script>
<script src="../assets/js/pagamento.js"></script>
</body>
</html>
