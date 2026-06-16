<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';

iniciaSessao();
exigeLoginCliente();

$pagina_atual = 'conta';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'sair') {
        fazLogoutCliente();
        header('Location: login.php');
        exit;
    }

    if ($acao === 'alternar_modo_silencio') {
        $usuario_atual = buscaUsuarioPorId(usuarioLogadoId());
        atualizaModoSilencio(usuarioLogadoId(), !$usuario_atual['modo_silencio']);
    }

    if ($acao === 'definir_recordatorio') {
        $minutos = (int) ($_POST['minutos'] ?? 30);
        if (in_array($minutos, [15, 30, 60, 1440], true)) {
            atualizaAntecedenciaLembrete(usuarioLogadoId(), $minutos);
        }
    }

    if ($acao === 'desconectar_google') {
        atualizaTokensGoogle(usuarioLogadoId(), null, null, null);
    }

    header('Location: conta.php');
    exit;
}

$usuario = buscaUsuarioPorId(usuarioLogadoId());
$inicial_nome = mb_strtoupper(mb_substr($usuario['nome'], 0, 1));

$agora = new DateTime();
$expirado = $usuario['plano_expira_em'] && new DateTime($usuario['plano_expira_em']) < $agora;
$dias_restantes_trial = (!$expirado && $usuario['plano_expira_em'])
    ? (int) $agora->diff(new DateTime($usuario['plano_expira_em']))->days
    : 0;

if ($expirado) {
    $badge_plano = traduz('badge_expirado');
    $badge_cor   = 'vermelho';
} elseif ($usuario['plano'] === 'trial') {
    $badge_plano = str_replace('5', (string) $dias_restantes_trial, traduz('badge_prueba_dias'));
    $badge_cor   = 'ambar';
} elseif ($usuario['plano'] === 'ativo') {
    $badge_plano = traduz('badge_ativo');
    $badge_cor   = 'verde';
} else {
    $badge_plano = '—';
    $badge_cor   = 'ambar';
}

$google_conectado = !empty($usuario['token_acesso_google']);
$whatsapp_conectado = !empty($usuario['telefone']);

$opcoes_recordatorio = [15 => 'recordatorio_15', 30 => 'recordatorio_30', 60 => 'recordatorio_1h', 1440 => 'recordatorio_1d'];
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — <?= traduz('conta_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
</head>
<body>

<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> CalendarioIA</div>
    <span class="selo <?= $badge_cor ?>"><?= htmlspecialchars($badge_plano) ?></span>
  </div>
  <div class="conteudo-pagina espacado">
    <h1 class="tela-titulo"><?= traduz('conta_titulo') ?></h1>

    <div class="conta-cabecalho">
      <span class="conta-avatar"><?= htmlspecialchars($inicial_nome) ?></span>
      <div class="conta-info"><b><?= htmlspecialchars($usuario['nome']) ?></b><span><?= htmlspecialchars($usuario['email']) ?></span></div>
    </div>

    <div>
      <div class="secao-rotulo"><?= traduz('conta_suscripcion_titulo') ?></div>
      <div class="assinatura-cartao">
        <div class="assinatura-cabecalho">
          <b><?= traduz('plan_mensual') ?></b>
          <span class="selo verde"><?= traduz('plan_mensual_precio') ?></span>
        </div>
        <div class="assinatura-datas"><?= traduz('sub_dates') ?></div>
        <div class="assinatura-acoes">
          <button type="button" class="botao botao-contorno botao-pequeno"><?= traduz('botao_cambiar_tarjeta') ?></button>
          <button type="button" class="botao botao-perigo botao-pequeno"><?= traduz('botao_cancelar_plan') ?></button>
        </div>
      </div>
    </div>

    <div>
      <div class="secao-rotulo"><?= traduz('preferencias_titulo') ?></div>
      <form method="post" action="conta.php" class="config-linha">
        <input type="hidden" name="acao" value="alternar_modo_silencio" />
        <span class="config-icone">🔕</span>
        <div class="config-info"><b><?= traduz('modo_silencio_titulo') ?></b><span><?= traduz('modo_silencio_sub') ?></span></div>
        <button type="submit" class="toggle" style="border:0;background:none;cursor:pointer;"><span class="<?= $usuario['modo_silencio'] ? 'ativo' : '' ?>"></span></button>
      </form>
      <div class="config-linha">
        <span class="config-icone">⏰</span>
        <div class="config-info">
          <b><?= traduz('recordatorios_titulo') ?></b><span><?= traduz('recordatorios_sub') ?></span>
          <div class="controle-segmentado">
            <?php foreach ($opcoes_recordatorio as $minutos => $chave): ?>
            <form method="post" action="conta.php" style="display:inline;">
              <input type="hidden" name="acao" value="definir_recordatorio" />
              <input type="hidden" name="minutos" value="<?= $minutos ?>" />
              <button type="submit" class="opcoes-segmentadas <?= (int) $usuario['antecedencia_lembrete_min'] === $minutos ? 'ativo' : '' ?>" style="border:0;cursor:pointer;"><?= traduz($chave) ?></button>
            </form>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="secao-rotulo"><?= traduz('conexiones_titulo') ?></div>
      <div class="conexao">
        <span class="conexao-icone whatsapp">💬</span>
        <div class="conexao-info"><b><?= traduz('home_whatsapp_label') ?></b><span><?= $whatsapp_conectado ? htmlspecialchars($usuario['telefone']) : '—' ?></span></div>
        <?php if (!$whatsapp_conectado): ?><span class="selo ambar"><span class="ponto"></span> <?= traduz('home_pendiente') ?></span><?php endif; ?>
        <a class="botao botao-contorno botao-pequeno" href="whatsapp.php"><?= traduz('botao_cambiar') ?></a>
      </div>
      <div class="conexao">
        <span class="conexao-icone google">📅</span>
        <div class="conexao-info"><b><?= traduz('home_google_label') ?></b><span><?= $google_conectado ? htmlspecialchars($usuario['email']) : '—' ?></span></div>
        <?php if (!$google_conectado): ?><span class="selo ambar"><span class="ponto"></span> <?= traduz('home_pendiente') ?></span><?php endif; ?>
        <?php if ($google_conectado): ?>
        <form method="post" action="conta.php">
          <input type="hidden" name="acao" value="desconectar_google" />
          <button type="submit" class="botao botao-contorno botao-pequeno"><?= traduz('botao_desconectar') ?></button>
        </form>
        <?php else: ?>
        <a class="botao botao-contorno botao-pequeno" href="google.php"><?= traduz('botao_cambiar') ?></a>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <div class="secao-rotulo"><?= traduz('sesion_titulo') ?></div>
      <form method="post" action="conta.php">
        <input type="hidden" name="acao" value="sair" />
        <button type="submit" class="botao botao-perigo" style="width:100%;"><?= traduz('botao_cerrar_sesion') ?></button>
      </form>
    </div>

    <div class="barra-abas-espaco"></div>
  </div>
  <?php require __DIR__ . '/_includes/menu-inferior.php'; ?>
</div>

<div class="vista-desktop">
  <div class="app-estrutura">
    <?php require __DIR__ . '/_includes/menu-lateral.php'; ?>
    <div class="conteudo-principal">
      <header class="barra-superior">
        <h1><?= traduz('conta_titulo') ?></h1>
        <div class="espaco"></div>
      </header>
      <div class="conteudo-area">
        <div class="conta-grid">
          <div class="coluna">
            <div class="conta-cabecalho">
              <span class="conta-avatar"><?= htmlspecialchars($inicial_nome) ?></span>
              <div class="conta-info"><b><?= htmlspecialchars($usuario['nome']) ?></b><span><?= htmlspecialchars($usuario['email']) ?></span></div>
            </div>

            <div class="secao-rotulo-desktop"><?= traduz('conta_suscripcion_titulo') ?></div>
            <div class="assinatura-cartao">
              <div class="assinatura-cabecalho">
                <b><?= traduz('plan_mensual') ?></b>
                <span class="selo verde"><?= traduz('plan_mensual_precio') ?></span>
              </div>
              <div class="assinatura-datas"><?= traduz('sub_dates') ?></div>
              <div class="assinatura-acoes">
                <button type="button" class="botao botao-contorno botao-pequeno"><?= traduz('botao_cambiar_tarjeta') ?></button>
                <button type="button" class="botao botao-perigo botao-pequeno"><?= traduz('botao_cancelar_plan') ?></button>
              </div>
            </div>

            <div class="secao-rotulo-desktop"><?= traduz('sesion_titulo') ?></div>
            <form method="post" action="conta.php">
        <input type="hidden" name="acao" value="sair" />
        <button type="submit" class="botao botao-perigo" style="width:100%;"><?= traduz('botao_cerrar_sesion') ?></button>
      </form>
          </div>

          <div class="coluna">
            <div class="secao-rotulo-desktop"><?= traduz('preferencias_titulo') ?></div>
            <form method="post" action="conta.php" class="config-linha">
              <input type="hidden" name="acao" value="alternar_modo_silencio" />
              <span class="config-icone">🔕</span>
              <div class="config-info"><b><?= traduz('modo_silencio_titulo') ?></b><span><?= traduz('modo_silencio_sub') ?></span></div>
              <button type="submit" class="toggle" style="border:0;background:none;cursor:pointer;"><span class="<?= $usuario['modo_silencio'] ? 'ativo' : '' ?>"></span></button>
            </form>
            <div class="config-linha">
              <span class="config-icone">⏰</span>
              <div class="config-info">
                <b><?= traduz('recordatorios_titulo') ?></b><span><?= traduz('recordatorios_sub') ?></span>
                <div class="controle-segmentado">
                  <?php foreach ($opcoes_recordatorio as $minutos => $chave): ?>
                  <form method="post" action="conta.php" style="display:inline;">
                    <input type="hidden" name="acao" value="definir_recordatorio" />
                    <input type="hidden" name="minutos" value="<?= $minutos ?>" />
                    <button type="submit" class="opcoes-segmentadas <?= (int) $usuario['antecedencia_lembrete_min'] === $minutos ? 'ativo' : '' ?>" style="border:0;cursor:pointer;"><?= traduz($chave) ?></button>
                  </form>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="secao-rotulo-desktop"><?= traduz('conexiones_titulo') ?></div>
            <div class="conexao">
              <span class="conexao-icone whatsapp">💬</span>
              <div class="conexao-info"><b><?= traduz('home_whatsapp_label') ?></b><span><?= $whatsapp_conectado ? htmlspecialchars($usuario['telefone']) : '—' ?></span></div>
        <?php if (!$whatsapp_conectado): ?><span class="selo ambar"><span class="ponto"></span> <?= traduz('home_pendiente') ?></span><?php endif; ?>
              <a class="botao botao-contorno botao-pequeno" href="whatsapp.php"><?= traduz('botao_cambiar') ?></a>
            </div>
            <div class="conexao">
              <span class="conexao-icone google">📅</span>
              <div class="conexao-info"><b><?= traduz('home_google_label') ?></b><span><?= $google_conectado ? htmlspecialchars($usuario['email']) : '—' ?></span></div>
        <?php if (!$google_conectado): ?><span class="selo ambar"><span class="ponto"></span> <?= traduz('home_pendiente') ?></span><?php endif; ?>
              <?php if ($google_conectado): ?>
              <form method="post" action="conta.php">
                <input type="hidden" name="acao" value="desconectar_google" />
                <button type="submit" class="botao botao-contorno botao-pequeno"><?= traduz('botao_desconectar') ?></button>
              </form>
              <?php else: ?>
              <a class="botao botao-contorno botao-pequeno" href="google.php"><?= traduz('botao_cambiar') ?></a>
              <?php endif; ?>
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
