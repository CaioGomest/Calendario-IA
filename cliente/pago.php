<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesPlanos.php';
require_once __DIR__ . '/../funcoes/funcoesStripe.php';
require_once __DIR__ . '/../config/config.php';

iniciaSessao();
exigeLoginCliente();

$planos_ativos = listaPlanos(['ativo' => 1]);
$sufixo_pago = [
    'mensal' => traduz('upgrade_ciclo_mensal'),
    'trimestral' => traduz('upgrade_ciclo_trimestral'),
    'anual' => traduz('upgrade_ciclo_anual'),
];

$erro_pago = '';
$client_secret = '';
$subscription_id = '';
$plano_selecionado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    if (MODO_DEV) {
        $id_plano_dev = (int)($_POST['id_plano'] ?? 0);
        $plano_dev = $id_plano_dev ? buscaPlanoPorId($id_plano_dev) : ($planos_ativos[0] ?? null);
        $expira = date('Y-m-d H:i:s', strtotime('+1 month'));
        if ($plano_dev) {
            $map = ['mensal' => '+1 month', 'trimestral' => '+3 months', 'anual' => '+1 year'];
            $expira = date('Y-m-d H:i:s', strtotime($map[$plano_dev['ciclo']] ?? '+1 month'));
        }
        atualizaPlanoUsuario(usuarioLogadoId(), 'ativo', $expira);
        header('Location: google.php');
        exit;
    }

    $sub_id = $_POST['subscription_id'] ?? '';
    if ($sub_id !== '') {
        $sub = buscaAssinaturaStripe($sub_id);
        if (!isset($sub['error']) && in_array($sub['status'] ?? '', ['active', 'trialing'])) {
            $expira_em = !empty($sub['current_period_end'])
                ? date('Y-m-d H:i:s', (int)$sub['current_period_end'])
                : date('Y-m-d H:i:s', strtotime('+1 month'));
            atualizaPlanoUsuario(usuarioLogadoId(), 'ativo', $expira_em);
            atualizaStripeUsuario(usuarioLogadoId(), $sub['customer'] ?? '', $sub_id);
            header('Location: google.php');
            exit;
        }
    }
    $erro_pago = traduz('pago_erro_stripe');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['escolher_plano'])) {
    $id_escolhido = (int)($_POST['id_plano'] ?? 0);
    $plano_selecionado = $id_escolhido ? buscaPlanoPorId($id_escolhido) : null;

    if ($plano_selecionado && !MODO_DEV) {
        $usuario = buscaUsuarioPorId(usuarioLogadoId());
        $stripe_customer_id = $usuario['stripe_customer_id'] ?? '';

        if (empty($stripe_customer_id)) {
            $cliente = criaClienteStripe($usuario['email'], $usuario['nome']);
            if (isset($cliente['error'])) {
                $erro_pago = traduz('pago_erro_stripe');
            } else {
                $stripe_customer_id = $cliente['id'];
                atualizaStripeUsuario(usuarioLogadoId(), $stripe_customer_id, null);
            }
        }

        if (!$erro_pago) {
            $assinatura = criaAssinaturaStripe($stripe_customer_id, [
                'nome_plano' => $plano_selecionado['nome'],
                'preco' => $plano_selecionado['preco'],
                'ciclo' => $plano_selecionado['ciclo'],
                'id_plano' => $plano_selecionado['id_plano'],
                'id_usuario' => usuarioLogadoId(),
                'dias_teste' => $plano_selecionado['dias_teste'] ?? 0,
            ]);

            if (isset($assinatura['error'])) {
                $erro_pago = traduz('pago_erro_stripe');
            } else {
                $subscription_id = $assinatura['id'];
                $client_secret = $assinatura['latest_invoice']['payment_intent']['client_secret'] ?? '';
            }
        }
    }
}

$mostra_pagamento = $plano_selecionado !== null;
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(nomeApp()) ?> — <?= traduz('pago_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
<?php if (!MODO_DEV): ?>
<script src="https://js.stripe.com/v3/"></script>
<?php endif; ?>
<style>
#card-element { padding:12px 14px; border:1.5px solid var(--line,#dde3ec); border-radius:12px; background:#fff; transition:border-color .2s; }
#card-element.StripeElement--focus { border-color:var(--primary,#3b82f6); }
#card-element.StripeElement--invalid { border-color:#ef4444; }
#card-errors { color:#ef4444; font-size:13px; font-weight:600; margin-top:6px; min-height:20px; }
.pago-loading { opacity:.6; pointer-events:none; }
.grade-planos-pago { display:flex; flex-direction:column; gap:12px; width:100%; }
.plano-opcao { border:1.5px solid var(--line,#dde3ec); border-radius:14px; padding:16px 18px; cursor:pointer; transition:border-color .15s, box-shadow .15s; }
.plano-opcao:hover { border-color:var(--primary,#3b82f6); box-shadow:0 2px 8px rgba(59,130,246,.1); }
.plano-opcao-nome { font-family:var(--font-titulo,sans-serif); font-weight:700; font-size:16px; color:var(--ink,#1f2733); }
.plano-opcao-preco { font-size:22px; font-weight:800; color:var(--ink,#1f2733); margin:4px 0; }
.plano-opcao-preco small { font-size:13px; font-weight:600; color:var(--ink-4,#94a3b8); }
.plano-opcao-teste { font-size:12px; font-weight:600; color:#22c55e; margin-top:2px; }
.plano-opcao-desc { font-size:12.5px; color:var(--ink-4,#94a3b8); font-weight:600; margin-top:4px; }
.plano-opcao button { margin-top:10px; width:100%; }
</style>
</head>
<body>

<?php if ($mostra_pagamento): ?>
  <?php
  $form_cartao = function() use ($erro_pago, $subscription_id, $plano_selecionado) { ?>
      <?php if ($erro_pago): ?>
      <div class="erro-msg" style="margin-bottom:12px;"><?= htmlspecialchars($erro_pago) ?></div>
      <?php endif; ?>

      <?php if (MODO_DEV): ?>
        <div class="dica" style="text-align:center;margin-bottom:12px;">🧪 Modo dev: continuar sem pagamento real.</div>
        <form method="post" action="pago.php">
          <input type="hidden" name="confirmar" value="1" />
          <input type="hidden" name="id_plano" value="<?= $plano_selecionado['id_plano'] ?>" />
          <button type="submit" class="botao botao-primario botao-espaco" style="width:100%;"><?= traduz('pago_botao_checkout') ?></button>
        </form>
      <?php else: ?>
        <form id="payment-form" method="post" action="pago.php">
          <input type="hidden" name="confirmar" value="1" />
          <input type="hidden" name="subscription_id" value="<?= htmlspecialchars($subscription_id) ?>" />
          <div class="campo" style="margin-top:0;">
            <label><?= traduz('campo_nombre_tarjeta') ?></label>
            <div class="input"><input type="text" id="card-name" placeholder="Mariana López" required /></div>
          </div>
          <div class="campo">
            <label><?= traduz('campo_numero_tarjeta') ?></label>
            <div id="card-element"></div>
            <div id="card-errors"></div>
          </div>
          <button type="submit" id="btn-pagar" class="botao botao-primario botao-espaco" style="width:100%;"><?= traduz('pago_botao_checkout') ?></button>
        </form>
      <?php endif; ?>
      <div class="confianca" style="margin-top:12px;"><?= traduz('pago_trust') ?></div>
  <?php }; ?>

  <div class="vista-mobile">
    <div class="barra-topo">
      <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
      <a class="botao botao-contorno botao-pequeno" href="pago.php"><?= traduz('botao_atras') ?></a>
    </div>
    <div class="conteudo-pagina espacado">
      <h1 class="tela-titulo"><?= traduz('pago_titulo') ?></h1>
      <div class="plano-cartao">
        <div class="plano-preco"><?= simboloMoeda() . number_format((float)$plano_selecionado['preco'], 0) ?> <small><?= $sufixo_pago[$plano_selecionado['ciclo']] ?? '' ?></small></div>
        <div style="font-weight:700;margin-bottom:8px;"><?= htmlspecialchars($plano_selecionado['nome']) ?></div>
      </div>
      <?php $form_cartao(); ?>
    </div>
  </div>

  <div class="vista-desktop">
    <div class="cadastro-estrutura">
      <div class="cadastro-marca">
        <div class="marca"><span class="logo"><span data-bot="white" data-size="22"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
        <div class="plano-cartao" style="background:rgba(255,255,255,.12);">
          <div class="plano-preco"><?= simboloMoeda() . number_format((float)$plano_selecionado['preco'], 0) ?> <small><?= $sufixo_pago[$plano_selecionado['ciclo']] ?? '' ?></small></div>
          <div style="font-weight:700;"><?= htmlspecialchars($plano_selecionado['nome']) ?></div>
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
        <div class="form-area">
          <h2 class="form-titulo"><?= traduz('pago_titulo') ?></h2>
          <p class="form-subtitulo"><?= traduz('pago_subtitulo_desktop') ?></p>
          <?php $form_cartao(); ?>
        </div>
      </div>
    </div>
  </div>

<?php else: ?>

  <div class="vista-mobile">
    <div class="barra-topo">
      <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
      <a class="botao botao-contorno botao-pequeno" href="cadastro.php"><?= traduz('botao_atras') ?></a>
    </div>
    <div class="conteudo-pagina espacado">
      <div class="etapa-cabecalho">
        <div class="progresso"><span class="completo"></span><span class="completo"></span><span></span><span></span></div>
        <span class="etapa-rotulo"><?= traduz('pago_step_label') ?></span>
      </div>
      <h1 class="tela-titulo"><?= traduz('pago_escolha_plano') ?></h1>
      <p class="tela-subtitulo"><?= traduz('pago_escolha_subtitulo') ?></p>

      <div class="grade-planos-pago">
        <?php foreach ($planos_ativos as $p): ?>
        <div class="plano-opcao">
          <div class="plano-opcao-nome"><?= htmlspecialchars($p['nome']) ?></div>
          <div class="plano-opcao-preco"><?= simboloMoeda() ?><?= number_format((float)$p['preco'], 0) ?> <small><?= $sufixo_pago[$p['ciclo']] ?? '' ?></small></div>
          <?php if ($p['descricao']): ?>
          <div class="plano-opcao-desc"><?= htmlspecialchars($p['descricao']) ?></div>
          <?php endif; ?>
          <?php if ((int)$p['dias_teste'] > 0): ?>
          <div class="plano-opcao-teste"><?= sprintf(traduz('pago_dias_teste'), (int)$p['dias_teste']) ?></div>
          <?php endif; ?>
          <form method="post" action="pago.php">
            <input type="hidden" name="escolher_plano" value="1" />
            <input type="hidden" name="id_plano" value="<?= $p['id_plano'] ?>" />
            <button type="submit" class="botao botao-primario"><?= traduz('pago_botao_escolher') ?></button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="vista-desktop">
    <div class="cadastro-estrutura">
      <div class="cadastro-marca">
        <div class="marca"><span class="logo"><span data-bot="white" data-size="22"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
        <div class="login-icone" style="font-size:46px;">💳</div>
        <h2><?= traduz('pago_marca_titulo') ?></h2>
        <p><?= traduz('pago_marca_subtitulo') ?></p>
      </div>
      <div class="cadastro-form">
        <div class="form-area">
          <div class="cadastro-progresso">
            <div class="cadastro-progresso-barras"><span class="completo"></span><span class="completo"></span><span></span><span></span></div>
            <span class="cadastro-progresso-rotulo"><?= traduz('pago_step_label') ?></span>
          </div>
          <h2 class="form-titulo"><?= traduz('pago_escolha_plano') ?></h2>
          <p class="form-subtitulo"><?= traduz('pago_escolha_subtitulo') ?></p>

          <div class="grade-planos-pago">
            <?php foreach ($planos_ativos as $p): ?>
            <div class="plano-opcao">
              <div class="plano-opcao-nome"><?= htmlspecialchars($p['nome']) ?></div>
              <div class="plano-opcao-preco"><?= simboloMoeda() ?><?= number_format((float)$p['preco'], 0) ?> <small><?= $sufixo_pago[$p['ciclo']] ?? '' ?></small></div>
              <?php if ($p['descricao']): ?>
              <div class="plano-opcao-desc"><?= htmlspecialchars($p['descricao']) ?></div>
              <?php endif; ?>
              <?php if ((int)$p['dias_teste'] > 0): ?>
              <div class="plano-opcao-teste"><?= sprintf(traduz('pago_dias_teste'), (int)$p['dias_teste']) ?></div>
              <?php endif; ?>
              <form method="post" action="pago.php">
                <input type="hidden" name="escolher_plano" value="1" />
                <input type="hidden" name="id_plano" value="<?= $p['id_plano'] ?>" />
                <button type="submit" class="botao botao-primario"><?= traduz('pago_botao_escolher') ?></button>
              </form>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>

<script src="../assets/js/mascote.js"></script>
<?php if (!MODO_DEV && $client_secret): ?>
<script>
(function() {
    var stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
    var elements = stripe.elements();
    var card = elements.create('card', {
        style: {
            base: {
                fontFamily: "'Nunito', sans-serif",
                fontSize: '15px',
                fontWeight: '600',
                color: '#1f2733',
                '::placeholder': { color: '#94a3b8' }
            },
            invalid: { color: '#ef4444' }
        },
        hidePostalCode: true
    });
    card.mount('#card-element');

    var form = document.getElementById('payment-form');
    var btn = document.getElementById('btn-pagar');
    var erros = document.getElementById('card-errors');
    var textoOriginal = btn.textContent;

    card.on('change', function(e) {
        erros.textContent = e.error ? e.error.message : '';
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        btn.disabled = true;
        btn.textContent = '<?= traduz('pago_processando') ?>';
        form.classList.add('pago-loading');

        stripe.confirmCardPayment('<?= $client_secret ?>', {
            payment_method: {
                card: card,
                billing_details: { name: document.getElementById('card-name').value }
            }
        }).then(function(result) {
            if (result.error) {
                erros.textContent = result.error.message;
                btn.disabled = false;
                btn.textContent = textoOriginal;
                form.classList.remove('pago-loading');
            } else {
                form.submit();
            }
        });
    });
})();
</script>
<?php endif; ?>
</body>
</html>
