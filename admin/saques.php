<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'saques';
$msg_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $id_saque = (int) ($_POST['id_saque'] ?? 0);
    $saque = buscaSaquePorId($id_saque);

    if ($saque && $saque['status'] === 'pendente') {
        if ($_POST['acao'] === 'marcar_pago') {
            atualizaStatusSaque($id_saque, 'pago');
            $msg_sucesso = 'Saque marcado como pago.';
        } elseif ($_POST['acao'] === 'recusar') {
            atualizaStatusSaque($id_saque, 'recusado');
            $msg_sucesso = 'Saque recusado.';
        }
    }

    if ($msg_sucesso) {
        header('Location: saques.php?sucesso=' . urlencode($msg_sucesso));
        exit;
    }
}

if (!empty($_GET['sucesso'])) {
    $msg_sucesso = $_GET['sucesso'];
}

$filtro_status = $_GET['status'] ?? '';
$saques = listaTodosSaques($filtro_status ? ['status' => $filtro_status] : []);

$rotulo_status = ['pendente' => 'Pendente', 'pago' => 'Pago', 'recusado' => 'Recusado'];
$cor_status = ['pendente' => 'ambar', 'pago' => 'verde', 'recusado' => 'vermelho'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(nomeApp()) ?> — Saques</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= versaoAsset('assets/css/admin.css') ?>" />
</head>
<body>
<div class="admin-estrutura">
  <?php require __DIR__ . '/_includes/sidebar.php'; ?>
  <div class="admin-conteudo">
    <header class="admin-barra">
      <div>
        <h1>Saques de afiliados</h1>
        <div class="subtitulo"><?= count($saques) ?> solicitação(ões)</div>
      </div>
      <div class="espaco"></div>
      <div class="campo-entrada">
        <select onchange="location.href='saques.php' + (this.value ? '?status=' + this.value : '')">
          <option value="" <?= $filtro_status === '' ? 'selected' : '' ?>>Todos os status</option>
          <option value="pendente" <?= $filtro_status === 'pendente' ? 'selected' : '' ?>>Pendente</option>
          <option value="pago" <?= $filtro_status === 'pago' ? 'selected' : '' ?>>Pago</option>
          <option value="recusado" <?= $filtro_status === 'recusado' ? 'selected' : '' ?>>Recusado</option>
        </select>
      </div>
    </header>
    <div class="admin-area">

      <?php if ($msg_sucesso): ?>
      <div class="sucesso-msg"><?= htmlspecialchars($msg_sucesso) ?></div>
      <?php endif; ?>

      <div class="painel">
        <div class="painel-cabecalho">
          <h2 class="painel-titulo">Solicitações de saque</h2>
        </div>
        <?php if ($saques): ?>
        <table>
          <thead>
            <tr>
              <th>Afiliado</th>
              <th>Valor</th>
              <th>PayPal</th>
              <th>Data</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($saques as $s): ?>
            <tr>
              <td style="color:var(--ink);font-weight:600;"><?= htmlspecialchars($s['nome_afiliado']) ?><br><span style="font-weight:400;color:var(--ink-3);"><?= htmlspecialchars($s['email_afiliado']) ?></span></td>
              <td>$<?= number_format($s['valor'], 2) ?></td>
              <td><?= htmlspecialchars($s['email_paypal']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($s['criado_em'])) ?></td>
              <td><span class="selo <?= $cor_status[$s['status']] ?>"><span class="ponto"></span> <?= $rotulo_status[$s['status']] ?></span></td>
              <td>
                <?php if ($s['status'] === 'pendente'): ?>
                <form method="post" action="saques.php" style="display:inline-block;">
                  <input type="hidden" name="id_saque" value="<?= $s['id_saque'] ?>" />
                  <input type="hidden" name="acao" value="marcar_pago" />
                  <button type="submit" class="botao-pequeno botao-primario-pequeno">Marcar pago</button>
                </form>
                <form method="post" action="saques.php" style="display:inline-block;" onsubmit="return confirm('Recusar esta solicitação?')">
                  <input type="hidden" name="id_saque" value="<?= $s['id_saque'] ?>" />
                  <input type="hidden" name="acao" value="recusar" />
                  <button type="submit" class="botao-pequeno botao-fantasma">Recusar</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="estado-vazio">Nenhuma solicitação de saque encontrada.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
