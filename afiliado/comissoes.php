<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';
require_once __DIR__ . '/../funcoes/funcoesComponentes.php';

iniciaSessao();
exigeLoginAfiliado();

$pagina_atual = 'comissoes';
$afiliado = buscaAfiliadoPorId(afiliadoLogadoId());
$pagina = (int) ($_GET['pagina'] ?? 1);
$resultado = listaComissoesAfiliado($afiliado['id_afiliado'], $pagina);
$comissoes = $resultado['comissoes'];
$total_paginas = $resultado['total_paginas'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg" />
<title><?= htmlspecialchars(nomeApp()) ?> — Comissões</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css?v=<?= versaoAsset('assets/css/cliente.css') ?>" />
<style>
.afiliado-tabela { width: 100%; border-collapse: collapse; margin-top: 12px; }
.afiliado-tabela th, .afiliado-tabela td { text-align: left; padding: 8px; border-bottom: 1px solid var(--border, #eee); font-size: 14px; }
</style>
</head>
<body>

<?php
ob_start();
?>

<?php if ($comissoes): ?>
<table class="afiliado-tabela">
  <thead><tr><th>Data</th><th>Valor</th></tr></thead>
  <tbody>
    <?php foreach ($comissoes as $c): ?>
    <tr>
      <td><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
      <td>$<?= number_format($c['valor'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php if ($total_paginas > 1): ?>
<div class="fin-paginacao">
  <?php if ($pagina > 1): ?>
  <a href="?pagina=<?= $pagina - 1 ?>">‹</a>
  <?php endif; ?>
  <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
    <?php if ($p === $pagina): ?>
    <span class="ativo"><?= $p ?></span>
    <?php else: ?>
    <a href="?pagina=<?= $p ?>"><?= $p ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  <?php if ($pagina < $total_paginas): ?>
  <a href="?pagina=<?= $pagina + 1 ?>">›</a>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php else: ?>
<p class="dica">Nenhuma comissão registrada ainda.</p>
<?php endif; ?>

<?php $conteudo_comissoes = ob_get_clean(); ?>

<!-- ══════ MOBILE ══════ -->
<div class="vista-mobile">
  <div class="barra-topo">
    <?php renderizaMarca('ink', 20); ?>
  </div>
  <div class="conteudo-pagina espacado">
    <div style="margin-bottom:14px;">
      <h1 class="tela-titulo" style="margin:0;">Comissões</h1>
      <p class="tela-subtitulo" style="margin:4px 0 0;">Histórico completo de comissões recebidas.</p>
    </div>
    <?= $conteudo_comissoes ?>
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
        <div><h1 class="saudacao" style="margin:0;">Comissões</h1><div class="barra-superior-subtitulo">Histórico completo de comissões recebidas.</div></div>
      </header>
      <div class="conteudo-area">
        <?= $conteudo_comissoes ?>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/mascote.js"></script>
</body>
</html>
