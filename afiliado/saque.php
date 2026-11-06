<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';
require_once __DIR__ . '/../funcoes/funcoesComponentes.php';

iniciaSessao();
exigeLoginAfiliado();

$pagina_atual = 'saque';
$afiliado = buscaAfiliadoPorId(afiliadoLogadoId());
$msg_erro = '';
$msg_sucesso = '';
$valor_minimo = 50.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor = (float) str_replace(',', '.', $_POST['valor'] ?? '0');
    $email_paypal = trim($_POST['email_paypal'] ?? '');
    $saldo_disponivel = saldoDisponivelAfiliado($afiliado['id_afiliado']);

    if (!filter_var($email_paypal, FILTER_VALIDATE_EMAIL)) {
        $msg_erro = 'Informe um e-mail do PayPal válido.';
    } elseif ($valor < $valor_minimo) {
        $msg_erro = 'O valor mínimo para saque é $' . number_format($valor_minimo, 2) . '.';
    } elseif ($valor > $saldo_disponivel) {
        $msg_erro = 'Valor maior que o saldo disponível ($' . number_format($saldo_disponivel, 2) . ').';
    } else {
        $id_saque = insereSaqueSeSaldoSuficiente($afiliado['id_afiliado'], $valor, $email_paypal);
        if ($id_saque === null) {
            $msg_erro = 'Valor maior que o saldo disponível ($' . number_format($saldo_disponivel, 2) . ').';
        } else {
            $msg_sucesso = 'Solicitação de saque enviada com sucesso.';
        }
    }

    if ($msg_sucesso) {
        header('Location: saque?sucesso=' . urlencode($msg_sucesso));
        exit;
    }
}

if (!empty($_GET['sucesso'])) {
    $msg_sucesso = $_GET['sucesso'];
}

$saldo_disponivel = saldoDisponivelAfiliado($afiliado['id_afiliado']);
$saques = listaSaquesPorAfiliado($afiliado['id_afiliado']);

$rotulo_status = ['pendente' => 'Pendente', 'pago' => 'Pago', 'recusado' => 'Recusado'];
$cor_status = ['pendente' => 'ambar', 'pago' => 'verde', 'recusado' => 'vermelho'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg" />
<title><?= htmlspecialchars(nomeApp()) ?> - Saques</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css?v=<?= versaoAsset('assets/css/cliente.css') ?>" />
<style>
.afiliado-conteudo { max-width: 480px; }
.afiliado-form-saque { margin: 16px 0; }
.afiliado-form-saque .campo:first-child { margin-top: 0; }
</style>
</head>
<body>

<?php
ob_start();
?>

<div class="afiliado-conteudo">

<?php if ($msg_sucesso): ?>
<div class="sucesso-msg" style="margin-bottom:12px;"><?= htmlspecialchars($msg_sucesso) ?></div>
<?php endif; ?>
<?php if ($msg_erro): ?>
<div class="erro-msg" style="margin-bottom:12px;"><?= htmlspecialchars($msg_erro) ?></div>
<?php endif; ?>

<div class="fin-resumo-grid" style="grid-template-columns: 1fr;">
  <div class="fin-resumo-card">
    <div class="resumo-label">Saldo disponível</div>
    <div class="resumo-valor">$<?= number_format($saldo_disponivel, 2) ?></div>
  </div>
</div>

<form class="cartao afiliado-form-saque" method="post" action="saque">
  <div class="campo">
    <label>Valor do saque</label>
    <div class="input"><span class="input-icone">$</span><input type="text" name="valor" placeholder="0.00" required /></div>
  </div>
  <div class="campo">
    <label>E-mail do PayPal</label>
    <div class="input"><span class="input-icone">✉️</span><input type="email" name="email_paypal" placeholder="seuemail@exemplo.com" required /></div>
  </div>
  <p class="dica" style="margin:8px 0 12px;">Valor mínimo: $<?= number_format($valor_minimo, 2) ?>. O pagamento é feito manualmente pela nossa equipe após aprovação.</p>
  <button type="submit" class="botao botao-primario botao-espaco">Solicitar saque</button>
</form>

<h2 class="tela-subtitulo">Histórico de saques</h2>
<?php if ($saques): ?>
<div class="fin-lista">
  <?php foreach ($saques as $s): ?>
  <div class="fin-item" style="cursor:default;">
    <div class="fin-item-icone" style="background: var(--soft-tint);">💰</div>
    <div class="fin-item-info">
      <div class="fin-item-nome">$<?= number_format($s['valor'], 2) ?></div>
      <div class="fin-item-meta">
        <span><?= date('d/m/Y', strtotime($s['criado_em'])) ?></span>
        <span>·</span>
        <span><?= htmlspecialchars($s['email_paypal']) ?></span>
      </div>
    </div>
    <span class="selo <?= $cor_status[$s['status']] ?>"><span class="ponto"></span> <?= $rotulo_status[$s['status']] ?></span>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<p class="dica">Nenhum saque solicitado ainda.</p>
<?php endif; ?>

</div>

<?php $conteudo_saque = ob_get_clean(); ?>

<!-- ══════ MOBILE ══════ -->
<div class="vista-mobile">
  <div class="barra-topo">
    <?php renderizaMarca('ink', 20); ?>
  </div>
  <div class="conteudo-pagina espacado">
    <div style="margin-bottom:14px;">
      <h1 class="tela-titulo" style="margin:0;">Saques</h1>
      <p class="tela-subtitulo" style="margin:4px 0 0;">Solicite o saque das suas comissões.</p>
    </div>
    <?= $conteudo_saque ?>
    <div class="barra-abas-espaco"></div>
  </div>
  <?php require __DIR__ . '/_includes/menu-inferior.php'; ?>
</div>

<!-- ══════ DESKTOP ══════ -->
<div class="vista-desktop">
  <div class="app-estrutura">
    <?php require __DIR__ . '/_includes/sidebar.php'; ?>
    <div class="conteudo-principal">
      <header class="barra-superior">
        <div><h1 class="saudacao" style="margin:0;">Saques</h1><div class="barra-superior-subtitulo">Solicite o saque das suas comissões.</div></div>
      </header>
      <div class="conteudo-area">
        <?= $conteudo_saque ?>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/mascote.js"></script>
</body>
</html>
