<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'configuracao';
$msg_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'salvar_preferencias') {
        $idioma = $_POST['idioma'] ?? IDIOMA_PADRAO;
        $fuso = $_POST['fuso_horario'] ?? 'America/Mexico_City';

        $idiomas_validos = listaIdiomasDisponiveis();
        if (in_array($idioma, $idiomas_validos, true)) {
            salvaConfiguracao('idioma_padrao', $idioma);
            $_SESSION['idioma'] = $idioma;
        }

        $fusos_validos = listaFusosHorariosComuns();
        if (in_array($fuso, $fusos_validos, true)) {
            salvaConfiguracao('fuso_horario_padrao', $fuso);
        }

        header('Location: configuracao.php?sucesso=1');
        exit;
    }
}

$msg_sucesso = !empty($_GET['sucesso']) ? traduz('admin_prefs_salvas') : '';

$idioma_atual = buscaConfiguracao('idioma_padrao') ?? IDIOMA_PADRAO;
$fuso_atual = buscaConfiguracao('fuso_horario_padrao') ?? 'America/Mexico_City';
$idiomas_disponiveis = listaIdiomasDisponiveis();
$fusos_disponiveis = listaFusosHorariosComuns();

$nomes_idioma = [
    'pt-BR' => 'Português (Brasil)',
    'es-MX' => 'Español (México)',
];

$variaveis = [
    ['nome' => 'APP_ENV', 'valor' => APP_ENV, 'descricao' => 'Ambiente / Entorno'],
    ['nome' => 'DB_HOST', 'valor' => DB_HOST, 'descricao' => 'Host DB'],
    ['nome' => 'DB_NAME', 'valor' => DB_NAME, 'descricao' => 'Database'],
    ['nome' => 'INTERNAL_SECRET', 'valor' => INTERNAL_SECRET ? str_repeat('•', 6) . substr(INTERNAL_SECRET, -4) : '(vazio)', 'descricao' => 'Internal Secret'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — <?= traduz('admin_configuracao') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/admin.css" />
</head>
<body>
<div class="admin-estrutura">
  <?php require __DIR__ . '/_includes/sidebar.php'; ?>
  <div class="admin-conteudo">
    <header class="admin-barra">
      <div>
        <h1><?= traduz('admin_configuracao') ?></h1>
        <div class="subtitulo"><?= traduz('admin_config_subtitulo') ?></div>
      </div>
    </header>
    <div class="admin-area">

      <?php if ($msg_sucesso): ?>
      <div class="sucesso-msg"><?= htmlspecialchars($msg_sucesso) ?></div>
      <?php endif; ?>

      <!-- Preferências editáveis -->
      <form method="post" action="configuracao.php">
        <input type="hidden" name="acao" value="salvar_preferencias" />
        <div class="config-card" style="margin-bottom:16px;">
          <div class="config-card-header">
            <span class="icone-titulo">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </span>
            <h2><?= traduz('admin_preferencias') ?></h2>
          </div>
          <div class="config-form-item">
            <div class="config-item-info">
              <b><?= traduz('admin_idioma') ?></b>
              <span><?= traduz('admin_idioma_desc') ?></span>
            </div>
            <select name="idioma">
              <?php foreach ($idiomas_disponiveis as $codigo): ?>
              <option value="<?= htmlspecialchars($codigo) ?>" <?= $codigo === $idioma_atual ? 'selected' : '' ?>>
                <?= htmlspecialchars($nomes_idioma[$codigo] ?? $codigo) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="config-form-item">
            <div class="config-item-info">
              <b><?= traduz('admin_fuso_horario') ?></b>
              <span><?= traduz('admin_fuso_desc') ?></span>
            </div>
            <select name="fuso_horario">
              <?php foreach ($fusos_disponiveis as $fuso): ?>
              <option value="<?= htmlspecialchars($fuso) ?>" <?= $fuso === $fuso_atual ? 'selected' : '' ?>>
                <?= htmlspecialchars(str_replace(['_', '/'], [' ', ' / '], $fuso)) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="config-form-footer">
            <button type="submit" class="botao-pequeno botao-primario-pequeno"><?= traduz('admin_salvar_prefs') ?></button>
          </div>
        </div>
      </form>

      <div class="config-grid">

        <div class="config-card">
          <div class="config-card-header">
            <span class="icone-titulo">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            </span>
            <h2><?= traduz('admin_variaveis') ?></h2>
          </div>
          <?php foreach ($variaveis as $v): ?>
          <div class="config-item">
            <div class="config-item-info">
              <b><?= htmlspecialchars($v['nome']) ?></b>
              <span><?= htmlspecialchars($v['descricao']) ?></span>
            </div>
            <span class="config-value"><?= htmlspecialchars($v['valor']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="config-card">
          <div class="config-card-header">
            <span class="icone-titulo">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
            </span>
            <h2><?= traduz('admin_sistema') ?></h2>
          </div>
          <div class="config-item">
            <div class="config-item-info">
              <b>PHP</b>
              <span><?= traduz('admin_php_versao') ?></span>
            </div>
            <span class="config-value"><?= phpversion() ?></span>
          </div>
          <div class="config-item">
            <div class="config-item-info">
              <b><?= traduz('admin_servidor') ?></b>
              <span><?= traduz('admin_servidor_desc') ?></span>
            </div>
            <span class="config-value"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? '—') ?></span>
          </div>
          <div class="config-item">
            <div class="config-item-info">
              <b><?= traduz('admin_modo_dev') ?></b>
              <span><?= traduz('admin_modo_dev_desc') ?></span>
            </div>
            <span class="selo <?= MODO_DEV ? 'ambar' : 'verde' ?>"><?= MODO_DEV ? traduz('admin_ativado') : traduz('admin_desativado') ?></span>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</body>
</html>
