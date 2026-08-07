<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';

iniciaSessao();
exigeLoginAfiliado();

$pagina_atual = 'painel';
$afiliado = buscaAfiliadoPorId(afiliadoLogadoId());
$total_indicacoes = contaIndicacoesPorAfiliado($afiliado['id_afiliado']);
$total_comissoes = somaComissoesPorAfiliado($afiliado['id_afiliado']);
$total_cliques = contaCliquesPorAfiliado($afiliado['id_afiliado']);
$saldo_disponivel = saldoDisponivelAfiliado($afiliado['id_afiliado']);
$indicacoes_mes = indicacoesPorMes($afiliado['id_afiliado']);
$comissoes_mes = comissoesPorMes($afiliado['id_afiliado']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(nomeApp()) ?> — Painel do Afiliado</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css?v=<?= versaoAsset('assets/css/cliente.css') ?>" />
<style>
.afiliado-cards { display: flex; gap: 16px; flex-wrap: wrap; margin: 20px 0; }
.afiliado-card { flex: 1; min-width: 160px; background: var(--surface, #fff); border: 1px solid var(--border, #eee); border-radius: 12px; padding: 16px; }
.afiliado-card b { display: block; font-size: 22px; margin-top: 4px; }
.afiliado-graficos { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 12px; }
.afiliado-grafico-caixa { flex: 1; min-width: 260px; background: var(--surface, #fff); border: 1px solid var(--border, #eee); border-radius: 12px; padding: 16px; }
.afiliado-grafico-titulo { font-weight: 700; margin-bottom: 8px; }
</style>
</head>
<body>

<?php
ob_start();
?>

<div class="afiliado-cards">
  <div class="afiliado-card">Indicações<b><?= (int) $total_indicacoes ?></b></div>
  <div class="afiliado-card">Cliques no link<b><?= (int) $total_cliques ?></b></div>
  <div class="afiliado-card">Comissões acumuladas<b>$<?= number_format($total_comissoes, 2) ?></b></div>
  <div class="afiliado-card">Saldo disponível para saque<b>$<?= number_format($saldo_disponivel, 2) ?></b></div>
</div>

<div class="afiliado-graficos">
  <div class="afiliado-grafico-caixa">
    <div class="afiliado-grafico-titulo">Indicações por mês</div>
    <canvas class="js-afiliado-bar" data-cor="#4D7CFE" data-dados='<?= htmlspecialchars(json_encode($indicacoes_mes), ENT_QUOTES) ?>'></canvas>
  </div>
  <div class="afiliado-grafico-caixa">
    <div class="afiliado-grafico-titulo">Comissões por mês</div>
    <canvas class="js-afiliado-bar" data-cor="#22B573" data-prefixo="$" data-dados='<?= htmlspecialchars(json_encode($comissoes_mes), ENT_QUOTES) ?>'></canvas>
  </div>
</div>

<?php $conteudo_painel = ob_get_clean(); ?>

<!-- ══════ MOBILE ══════ -->
<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
  </div>
  <div class="conteudo-pagina espacado">
    <div style="margin-bottom:14px;">
      <h1 class="tela-titulo" style="margin:0;">Olá, <?= htmlspecialchars($afiliado['nome']) ?></h1>
      <p class="tela-subtitulo" style="margin:4px 0 0;">Acompanhe suas indicações e comissões.</p>
    </div>
    <?= $conteudo_painel ?>
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
        <div><h1 class="saudacao" style="margin:0;">Olá, <?= htmlspecialchars($afiliado['nome']) ?></h1><div class="barra-superior-subtitulo">Acompanhe suas indicações e comissões.</div></div>
      </header>
      <div class="conteudo-area">
        <?= $conteudo_painel ?>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/mascote.js"></script>
<script src="../assets/js/afiliado.js"></script>
</body>
</html>
