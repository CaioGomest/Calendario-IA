<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';

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
        insereSaqueAfiliado($afiliado['id_afiliado'], $valor, $email_paypal);
        $msg_sucesso = 'Solicitação de saque enviada com sucesso.';
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
<title><?= htmlspecialchars(nomeApp()) ?> — Saques</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css?v=<?= versaoAsset('assets/css/cliente.css') ?>" />
<style>
.afiliado-cards { display: flex; gap: 16px; flex-wrap: wrap; margin: 20px 0; max-width: 420px; }
.afiliado-card { flex: 1; min-width: 160px; background: var(--surface, #fff); border: 1px solid var(--border, #eee); border-radius: 12px; padding: 16px; }
.afiliado-card b { display: block; font-size: 22px; margin-top: 4px; }
.afiliado-form-saque { background: var(--surface, #fff); border: 1px solid var(--border, #eee); border-radius: 12px; padding: 16px; margin: 16px 0; max-width: 420px; }
.afiliado-tabela { width: 100%; border-collapse: collapse; margin-top: 12px; }
.afiliado-tabela th, .afiliado-tabela td { text-align: left; padding: 8px; border-bottom: 1px solid var(--border, #eee); font-size: 14px; }
</style>
</head>
<body>

<?php
ob_start();
?>

<?php if ($msg_sucesso): ?>
<div class="sucesso-msg" style="margin-bottom:12px;"><?= htmlspecialchars($msg_sucesso) ?></div>
<?php endif; ?>
<?php if ($msg_erro): ?>
<div class="erro-msg" style="margin-bottom:12px;"><?= htmlspecialchars($msg_erro) ?></div>
<?php endif; ?>

<div class="afiliado-cards">
  <div class="afiliado-card">Saldo disponível<b>$<?= number_format($saldo_disponivel, 2) ?></b></div>
</div>

<form class="afiliado-form-saque" method="post" action="saque">
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
<table class="afiliado-tabela">
  <thead><tr><th>Data</th><th>Valor</th><th>PayPal</th><th>Status</th></tr></thead>
  <tbody>
    <?php foreach ($saques as $s): ?>
    <tr>
      <td><?= date('d/m/Y', strtotime($s['criado_em'])) ?></td>
      <td>$<?= number_format($s['valor'], 2) ?></td>
      <td><?= htmlspecialchars($s['email_paypal']) ?></td>
      <td><span class="selo <?= $cor_status[$s['status']] ?>"><span class="ponto"></span> <?= $rotulo_status[$s['status']] ?></span></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<p class="dica">Nenhum saque solicitado ainda.</p>
<?php endif; ?>

<?php $conteudo_saque = ob_get_clean(); ?>

<!-- ══════ MOBILE ══════ -->
<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
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
