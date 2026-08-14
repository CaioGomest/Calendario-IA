<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';

iniciaSessao();
exigeLoginAfiliado();

$pagina_atual = 'link';
$afiliado = buscaAfiliadoPorId(afiliadoLogadoId());
$total_cliques = contaCliquesPorAfiliado($afiliado['id_afiliado']);
$total_indicacoes = contaIndicacoesPorAfiliado($afiliado['id_afiliado']);
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$link_indicacao = $protocolo . '://' . $_SERVER['HTTP_HOST'] . '/landpage.php?ref=' . urlencode($afiliado['codigo']);
$taxa_conversao = $total_cliques > 0 ? round(($total_indicacoes / $total_cliques) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg" />
<title><?= htmlspecialchars(nomeApp()) ?> — Meu link</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css?v=<?= versaoAsset('assets/css/cliente.css') ?>" />
<style>
.afiliado-cards { display: flex; gap: 16px; flex-wrap: wrap; margin: 20px 0; }
.afiliado-card { flex: 1; min-width: 160px; background: var(--surface, #fff); border: 1px solid var(--border, #eee); border-radius: 12px; padding: 16px; }
.afiliado-card b { display: block; font-size: 22px; margin-top: 4px; }
.afiliado-link-box { display: flex; gap: 8px; align-items: center; background: var(--surface, #fff); border: 1px solid var(--border, #eee); border-radius: 12px; padding: 12px; margin: 16px 0; }
.afiliado-link-box input { flex: 1; border: none; background: transparent; font-size: 14px; }
</style>
</head>
<body>

<?php
ob_start();
?>

<p class="dica">Compartilhe o link abaixo. Toda visita é contabilizada, e todo cadastro feito a partir dele vira uma indicação sua.</p>

<div class="afiliado-link-box">
  <input type="text" readonly value="<?= htmlspecialchars($link_indicacao) ?>" onclick="this.select()" />
  <button type="button" class="botao botao-contorno botao-pequeno" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($link_indicacao, ENT_QUOTES) ?>')">Copiar</button>
</div>

<div class="afiliado-cards">
  <div class="afiliado-card">Cliques no link<b><?= (int) $total_cliques ?></b></div>
  <div class="afiliado-card">Indicações confirmadas<b><?= (int) $total_indicacoes ?></b></div>
  <div class="afiliado-card">Taxa de conversão<b><?= $taxa_conversao ?>%</b></div>
</div>

<?php $conteudo_link = ob_get_clean(); ?>

<!-- ══════ MOBILE ══════ -->
<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
  </div>
  <div class="conteudo-pagina espacado">
    <div style="margin-bottom:14px;">
      <h1 class="tela-titulo" style="margin:0;">Meu link</h1>
      <p class="tela-subtitulo" style="margin:4px 0 0;">Seu link exclusivo de indicação.</p>
    </div>
    <?= $conteudo_link ?>
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
        <div><h1 class="saudacao" style="margin:0;">Meu link</h1><div class="barra-superior-subtitulo">Seu link exclusivo de indicação.</div></div>
      </header>
      <div class="conteudo-area">
        <?= $conteudo_link ?>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/mascote.js"></script>
</body>
</html>
