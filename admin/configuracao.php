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

    if ($_POST['acao'] === 'salvar_geral') {
        $nome_app = trim($_POST['nome_app'] ?? '');
        if ($nome_app !== '') {
            salvaConfiguracao('nome_app', $nome_app);
        }
        $moeda_post = $_POST['moeda'] ?? 'BRL';
        if (in_array($moeda_post, ['BRL', 'USD', 'MXN'], true)) {
            salvaConfiguracao('moeda', $moeda_post);
        }
        header('Location: configuracao.php?sucesso=1');
        exit;
    }

    if ($_POST['acao'] === 'salvar_contato') {
        $email_remetente = trim($_POST['email_remetente'] ?? '');
        $email_suporte = trim($_POST['email_suporte'] ?? '');
        $instagram = trim($_POST['instagram'] ?? '');
        $tiktok = trim($_POST['tiktok'] ?? '');
        $link_suporte = trim($_POST['link_suporte'] ?? '');

        salvaConfiguracao('email_remetente', $email_remetente);
        salvaConfiguracao('email_suporte', $email_suporte);
        salvaConfiguracao('instagram', $instagram);
        salvaConfiguracao('tiktok', $tiktok);
        salvaConfiguracao('link_suporte', $link_suporte);

        header('Location: configuracao.php?sucesso=1');
        exit;
    }
}

$msg_sucesso = !empty($_GET['sucesso']) ? traduz('admin_prefs_salvas') : '';

$idioma_atual = buscaConfiguracao('idioma_padrao') ?? IDIOMA_PADRAO;
$fuso_atual = buscaConfiguracao('fuso_horario_padrao') ?? 'America/Mexico_City';
$idiomas_disponiveis = listaIdiomasDisponiveis();
$fusos_disponiveis = listaFusosHorariosComuns();

$nome_app = buscaConfiguracao('nome_app') ?? nomeApp();
$moeda_atual = moedaSistema();
$email_remetente = buscaConfiguracao('email_remetente') ?? '';
$email_suporte = buscaConfiguracao('email_suporte') ?? '';
$instagram = buscaConfiguracao('instagram') ?? '';
$tiktok = buscaConfiguracao('tiktok') ?? '';
$link_suporte = buscaConfiguracao('link_suporte') ?? '';

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
<title><?= htmlspecialchars(nomeApp()) ?> — <?= traduz('admin_configuracao') ?></title>
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

      <!-- Nome do app -->
      <form method="post" action="configuracao.php">
        <input type="hidden" name="acao" value="salvar_geral" />
        <div class="config-card" style="margin-bottom:16px;">
          <div class="config-card-header">
            <span class="icone-titulo">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </span>
            <h2><?= traduz('admin_geral') ?></h2>
          </div>
          <div class="config-form-item">
            <div class="config-item-info">
              <b><?= traduz('admin_nome_app') ?></b>
              <span><?= traduz('admin_nome_app_desc') ?></span>
            </div>
            <input type="text" name="nome_app" value="<?= htmlspecialchars($nome_app) ?>" placeholder="<?= htmlspecialchars(nomeApp()) ?>" required />
          </div>
          <div class="config-form-item">
            <div class="config-item-info">
              <b><?= traduz('admin_moeda') ?></b>
              <span><?= traduz('admin_moeda_desc') ?></span>
            </div>
            <select name="moeda">
              <option value="BRL" <?= $moeda_atual === 'BRL' ? 'selected' : '' ?>>R$ — Real (BRL)</option>
              <option value="USD" <?= $moeda_atual === 'USD' ? 'selected' : '' ?>>US$ — Dólar (USD)</option>
              <option value="MXN" <?= $moeda_atual === 'MXN' ? 'selected' : '' ?>>MX$ — Peso (MXN)</option>
            </select>
          </div>
          <div class="config-form-footer">
            <button type="button" class="botao-pequeno botao-primario-pequeno btn-confirmar"><?= traduz('admin_salvar_prefs') ?></button>
          </div>
        </div>
      </form>

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
            <button type="button" class="botao-pequeno botao-primario-pequeno btn-confirmar"><?= traduz('admin_salvar_prefs') ?></button>
          </div>
        </div>
      </form>

      <!-- Contato e Redes Sociais -->
      <form method="post" action="configuracao.php">
        <input type="hidden" name="acao" value="salvar_contato" />
        <div class="config-card" style="margin-bottom:16px;">
          <div class="config-card-header">
            <span class="icone-titulo">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </span>
            <h2><?= traduz('admin_contato_redes') ?></h2>
          </div>
          <div class="config-form-item">
            <div class="config-item-info">
              <b><?= traduz('admin_email_remetente') ?></b>
              <span><?= traduz('admin_email_remetente_desc') ?></span>
            </div>
            <input type="email" name="email_remetente" value="<?= htmlspecialchars($email_remetente) ?>" placeholder="noreply@<?= strtolower(nomeApp()) ?>.com" />
          </div>
          <div class="config-form-item">
            <div class="config-item-info">
              <b><?= traduz('admin_link_suporte') ?></b>
              <span><?= traduz('admin_link_suporte_desc') ?></span>
            </div>
            <input type="url" name="link_suporte" value="<?= htmlspecialchars($link_suporte) ?>" placeholder="https://wa.me/5215512345678" />
          </div>
          <div class="config-form-item">
            <div class="config-item-info">
              <b><?= traduz('admin_email_suporte') ?></b>
              <span><?= traduz('admin_email_suporte_desc') ?></span>
            </div>
            <input type="email" name="email_suporte" value="<?= htmlspecialchars($email_suporte) ?>" placeholder="contato@<?= strtolower(nomeApp()) ?>.com" />
          </div>
          <div class="config-form-item">
            <div class="config-item-info">
              <b>Instagram</b>
              <span><?= traduz('admin_instagram_desc') ?></span>
            </div>
            <input type="text" name="instagram" value="<?= htmlspecialchars($instagram) ?>" placeholder="@<?= strtolower(nomeApp()) ?>" />
          </div>
          <div class="config-form-item">
            <div class="config-item-info">
              <b>TikTok</b>
              <span><?= traduz('admin_tiktok_desc') ?></span>
            </div>
            <input type="text" name="tiktok" value="<?= htmlspecialchars($tiktok) ?>" placeholder="@<?= strtolower(nomeApp()) ?>" />
          </div>
          <div class="config-form-footer">
            <button type="button" class="botao-pequeno botao-primario-pequeno btn-confirmar"><?= traduz('admin_salvar_prefs') ?></button>
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
<!-- Modal de confirmação -->
<div id="modal-confirmar" class="modal-overlay" onclick="if(event.target===this)fecharConfirmar()">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <h2><?= traduz('admin_confirmar_titulo') ?></h2>
      <button type="button" class="modal-fechar" onclick="fecharConfirmar()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="font-size:13.5px;color:var(--ink-2);margin:0 0 14px;"><?= traduz('admin_confirmar_texto') ?></p>
      <div class="campo">
        <label style="font-size:12px;font-weight:700;color:var(--ink-4);text-transform:uppercase;"><?= traduz('admin_confirmar_label') ?></label>
        <div class="campo-entrada">
          <input type="text" id="input-confirmar" placeholder="<?= traduz('admin_confirmar_placeholder') ?>" autocomplete="off" />
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="botao-pequeno botao-fantasma" onclick="fecharConfirmar()"><?= traduz('admin_cancelar') ?></button>
      <button type="button" id="btn-confirmar-final" class="botao-pequeno botao-primario-pequeno" disabled><?= traduz('admin_confirmar_salvar') ?></button>
    </div>
  </div>
</div>

<script>
var formAtual = null;

document.querySelectorAll('.btn-confirmar').forEach(function(btn) {
    btn.addEventListener('click', function() {
        formAtual = btn.closest('form');
        document.getElementById('input-confirmar').value = '';
        document.getElementById('btn-confirmar-final').disabled = true;
        document.getElementById('modal-confirmar').classList.add('aberto');
        setTimeout(function() { document.getElementById('input-confirmar').focus(); }, 100);
    });
});

document.getElementById('input-confirmar').addEventListener('input', function() {
    var val = this.value.trim().toUpperCase();
    document.getElementById('btn-confirmar-final').disabled = (val !== '<?= mb_strtoupper(traduz('admin_confirmar_palavra')) ?>');
});

document.getElementById('btn-confirmar-final').addEventListener('click', function() {
    if (formAtual) formAtual.submit();
});

document.getElementById('input-confirmar').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !document.getElementById('btn-confirmar-final').disabled) {
        if (formAtual) formAtual.submit();
    }
});

function fecharConfirmar() {
    document.getElementById('modal-confirmar').classList.remove('aberto');
    formAtual = null;
}
</script>
</body>
</html>
