<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesEventos.php';

iniciaSessao();
exigeLoginCliente();

$pagina_atual = 'home';

$usuario = buscaUsuarioPorId(usuarioLogadoId());

$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

$proximos_eventos = array_map(function ($evento) use ($meses) {
    $data = new DateTime($evento['data_inicio']);
    return [
        'dia' => $data->format('d'),
        'mes' => $meses[(int) $data->format('n') - 1],
        'titulo' => $evento['titulo'],
        'detalhe' => $data->format('H:i') . ($evento['descricao'] ? ' · ' . $evento['descricao'] : ''),
    ];
}, listaProximosEventos($usuario['id_usuario']));

$eventos_semana = contaEventosSemana($usuario['id_usuario']);
$dias_restantes_trial = $usuario['plano_expira_em']
    ? max(0, (new DateTime())->diff(new DateTime($usuario['plano_expira_em']))->days)
    : 0;

$saudacao = str_replace('Mariana', $usuario['nome'], traduz('home_saludo'));
$badge_plano = $usuario['plano'] === 'trial'
    ? str_replace('5', (string) $dias_restantes_trial, traduz('badge_prueba_dias'))
    : '✅ ' . ucfirst($usuario['plano']);

$google_conectado = !empty($usuario['token_acesso_google']);
$whatsapp_conectado = !empty($usuario['telefone']);
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — <?= traduz('menu_inicio') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
</head>
<body>

<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> CalendarioIA</div>
    <span class="selo ambar"><?= htmlspecialchars($badge_plano) ?></span>
  </div>
  <div class="conteudo-pagina espacado">
    <div>
      <h1 class="saudacao"><?= htmlspecialchars($saudacao) ?></h1>
      <p class="saudacao-subtitulo"><?= traduz('home_subtitulo') ?></p>
    </div>

    <div class="assistente-cartao">
      <div class="assistente-cabecalho">
        <span class="assistente-avatar"><span data-bot="white" data-size="24"></span></span>
        <b><?= traduz('assist_titulo') ?></b>
      </div>
      <div class="assistente-exemplo"><?= traduz('assist_ejemplo') ?></div>
      <a class="botao botao-whatsapp" href="https://wa.me/" target="_blank">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.06 24l1.7-6.2A11.9 11.9 0 1 1 12 24a11.9 11.9 0 0 1-5.7-1.45L.06 24zM6.6 20l.36.22a9.9 9.9 0 1 0-3.4-3.4l.24.38-1 3.63 3.8-.83z"/></svg>
        <?= traduz('botao_abrir') ?>
      </a>
    </div>

    <div>
      <div class="secao-rotulo"><?= traduz('home_conexiones_titulo') ?></div>
      <div class="conexao">
        <span class="conexao-icone google">📅</span>
        <div class="conexao-info"><b><?= traduz('home_google_label') ?></b><span><?= $google_conectado ? htmlspecialchars($usuario['email']) : '—' ?></span></div>
        <span class="selo <?= $google_conectado ? 'verde' : 'ambar' ?>"><span class="ponto"></span> <?= traduz($google_conectado ? 'home_conectado' : 'home_pendiente') ?></span>
      </div>
      <div class="conexao">
        <span class="conexao-icone whatsapp">💬</span>
        <div class="conexao-info"><b><?= traduz('home_whatsapp_label') ?></b><span><?= $whatsapp_conectado ? htmlspecialchars($usuario['telefone']) : '—' ?></span></div>
        <span class="selo <?= $whatsapp_conectado ? 'verde' : 'ambar' ?>"><span class="ponto"></span> <?= traduz($whatsapp_conectado ? 'home_activo' : 'home_pendiente') ?></span>
      </div>
    </div>

    <div>
      <div class="secao-rotulo"><?= traduz('home_eventos_titulo') ?></div>
      <?php if (!$proximos_eventos): ?>
      <div class="nota-caixa"><?= traduz('home_sin_eventos') ?></div>
      <?php endif; ?>
      <?php foreach ($proximos_eventos as $evento): ?>
      <div class="evento">
        <div class="evento-data"><b><?= htmlspecialchars($evento['dia']) ?></b><span><?= htmlspecialchars($evento['mes']) ?></span></div>
        <div class="evento-info"><b><?= htmlspecialchars($evento['titulo']) ?></b><span><?= htmlspecialchars($evento['detalhe']) ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="nota-caixa"><?= traduz('home_micro_note') ?></div>
    <div class="barra-abas-espaco"></div>
  </div>
  <?php require __DIR__ . '/_includes/menu-inferior.php'; ?>
</div>

<div class="vista-desktop">
  <div class="app-estrutura">
    <?php require __DIR__ . '/_includes/menu-lateral.php'; ?>
    <div class="conteudo-principal">
      <header class="barra-superior">
        <div><h1 class="saudacao" style="margin:0;"><?= htmlspecialchars($saudacao) ?></h1><div class="barra-superior-subtitulo"><?= traduz('home_subtitulo') ?></div></div>
        <div class="espaco"></div>
        <span class="selo ambar"><?= htmlspecialchars($badge_plano) ?></span>
      </header>
      <div class="conteudo-area">
        <div class="grid-duas-colunas">
          <div class="coluna">
            <div class="assistente-cartao">
              <div class="assistente-cabecalho"><span class="assistente-avatar"><span data-bot="white" data-size="24"></span></span> <b><?= traduz('assist_titulo') ?></b></div>
              <div class="assistente-exemplo"><?= traduz('assist_ejemplo') ?></div>
              <a class="botao botao-whatsapp" href="https://wa.me/" target="_blank">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.06 24l1.7-6.2A11.9 11.9 0 1 1 12 24a11.9 11.9 0 0 1-5.7-1.45L.06 24zM6.6 20l.36.22a9.9 9.9 0 1 0-3.4-3.4l.24.38-1 3.63 3.8-.83z"/></svg>
                <?= traduz('botao_abrir_whatsapp') ?>
              </a>
            </div>
            <div>
              <div class="secao-rotulo-desktop"><?= traduz('home_eventos_titulo') ?></div>
              <?php foreach ($proximos_eventos as $evento): ?>
              <div class="evento">
                <div class="evento-data"><b><?= $evento['dia'] ?></b><span><?= $evento['mes'] ?></span></div>
                <div class="evento-info"><b><?= $evento['titulo'] ?></b><span><?= $evento['detalhe'] ?></span></div>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="nota-caixa"><?= traduz('home_micro_note') ?></div>
          </div>
          <div class="coluna">
            <div class="secao-rotulo-desktop"><?= traduz('home_conexiones_titulo') ?></div>
            <div class="conexao">
              <span class="conexao-icone google">📅</span>
              <div class="conexao-info"><b><?= traduz('home_google_label') ?></b><span><?= $google_conectado ? htmlspecialchars($usuario['email']) : '—' ?></span></div>
              <span class="selo <?= $google_conectado ? 'verde' : 'ambar' ?>"><span class="ponto"></span> <?= traduz($google_conectado ? 'home_conectado' : 'home_pendiente') ?></span>
            </div>
            <div class="conexao">
              <span class="conexao-icone whatsapp">💬</span>
              <div class="conexao-info"><b><?= traduz('home_whatsapp_label') ?></b><span><?= $whatsapp_conectado ? htmlspecialchars($usuario['telefone']) : '—' ?></span></div>
              <span class="selo <?= $whatsapp_conectado ? 'verde' : 'ambar' ?>"><span class="ponto"></span> <?= traduz($whatsapp_conectado ? 'home_activo' : 'home_pendiente') ?></span>
            </div>
            <div class="cartao">
              <b class="disp" style="font-family:var(--font-display);font-size:15.5px;"><?= traduz('home_semana_titulo') ?></b>
              <div style="display:flex;gap:14px;margin-top:10px;">
                <div style="flex:1;"><div style="font-family:var(--font-display);font-weight:700;font-size:22px;"><?= $eventos_semana ?></div><span style="font-size:12px;color:var(--ink-soft);font-weight:700;"><?= traduz('home_semana_eventos') ?></span></div>
                <div style="flex:1;"><div style="font-family:var(--font-display);font-weight:700;font-size:22px;"><?= $eventos_semana ?></div><span style="font-size:12px;color:var(--ink-soft);font-weight:700;"><?= traduz('home_semana_recordatorios') ?></span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/mascote.js"></script>
</body>
</html>
