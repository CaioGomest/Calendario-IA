<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesPlanos.php';

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

    if ($acao === 'cancelar_plano') {
        atualizaPlanoUsuario(usuarioLogadoId(), 'cancelado', null);
    }

    if ($acao === 'cambiar_plano' && !empty($_POST['id_plano'])) {
        $plano_escolhido = buscaPlanoPorId((int) $_POST['id_plano']);
        if ($plano_escolhido && $plano_escolhido['ativo']) {
            $expira = null;
            if ($plano_escolhido['ciclo'] === 'mensal') {
                $expira = date('Y-m-d H:i:s', strtotime('+1 month'));
            } elseif ($plano_escolhido['ciclo'] === 'trimestral') {
                $expira = date('Y-m-d H:i:s', strtotime('+3 months'));
            } elseif ($plano_escolhido['ciclo'] === 'anual') {
                $expira = date('Y-m-d H:i:s', strtotime('+1 year'));
            }
            atualizaPlanoUsuario(usuarioLogadoId(), 'ativo', $expira);
        }
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

$planos_disponiveis = listaPlanos(['ativo' => 1]);
$moeda = traduz('lp_moeda');
$sufixo_ciclo = [
    'mensal' => traduz('lp_ciclo_mensal'),
    'trimestral' => traduz('lp_ciclo_trimestral'),
    'anual' => traduz('lp_ciclo_anual'),
];
$data_expira_fmt = $usuario['plano_expira_em']
    ? date('d', strtotime($usuario['plano_expira_em'])) . ' ' . strtolower(date('M', strtotime($usuario['plano_expira_em']))) . ' ' . date('Y', strtotime($usuario['plano_expira_em']))
    : '—';

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
                <?php if ($planos_disponiveis): ?>
                <button type="button" class="botao botao-contorno botao-pequeno" onclick="abrirModal('modal-plano')" style="color:var(--primary);"><?= traduz('modal_cambiar_plan') ?></button>
                <?php endif; ?>
                <button type="button" class="botao botao-contorno botao-pequeno" onclick="abrirModal('modal-tarjeta')"><?= traduz('botao_cambiar_tarjeta') ?></button>
                <button type="button" class="botao botao-perigo botao-pequeno" onclick="abrirModal('modal-cancelar')"><?= traduz('botao_cancelar_plan') ?></button>
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

<!-- Modal: Trocar plano -->
<div id="modal-plano" class="modal-fundo" onclick="if(event.target===this)fecharModal(this.id)">
  <div class="modal-caixa">
    <button type="button" class="modal-fechar" onclick="fecharModal('modal-plano')">✕</button>
    <h2 class="modal-titulo"><?= traduz('modal_cambiar_plan') ?></h2>
    <p class="modal-sub"><?= traduz('modal_cambiar_plan_sub') ?></p>
    <?php if ($planos_disponiveis): ?>
    <div class="modal-planos">
      <?php foreach ($planos_disponiveis as $pi => $pl): ?>
      <div class="plano-opcao <?= $pi === 0 ? 'selecionado' : '' ?>" data-id="<?= $pl['id_plano'] ?>" onclick="selecionarPlano(this)">
        <?php
          $mensal = (float) $pl['preco'];
          if ($pl['ciclo'] === 'anual' && count($planos_disponiveis) > 1) {
              $plano_mensal = null;
              foreach ($planos_disponiveis as $pm) {
                  if ($pm['ciclo'] === 'mensal') { $plano_mensal = $pm; break; }
              }
              if ($plano_mensal) {
                  $preco_mensal_do_anual = $mensal / 12;
                  $economia = round((1 - $preco_mensal_do_anual / (float)$plano_mensal['preco']) * 100);
                  if ($economia > 0) {
                      echo '<span class="plano-badge">' . sprintf(traduz('modal_ahorra'), $economia) . '</span>';
                  }
              }
          }
        ?>
        <div class="plano-nome"><?= htmlspecialchars($pl['nome']) ?></div>
        <div class="plano-preco"><?= $moeda ?><?= $pl['ciclo'] === 'anual' ? (int)round($mensal/12) : (int)$mensal ?><small><?= $sufixo_ciclo['mensal'] ?></small></div>
        <div class="plano-detalhe"><?= traduz('modal_facturacion') ?> <?= htmlspecialchars($sufixo_ciclo[$pl['ciclo']] ?? '') ?></div>
        <?php if ($pl['ciclo'] === 'mensal'): ?>
        <div class="plano-feats">
          <span><?= traduz('lp_feat1') ?></span>
          <span><?= traduz('lp_feat2') ?></span>
          <span><?= traduz('lp_feat3') ?></span>
        </div>
        <?php else: ?>
        <div class="plano-feats">
          <span><?= traduz('modal_todo_del_plan') ?></span>
          <span><?= traduz('modal_meses_gratis') ?></span>
          <span><?= traduz('modal_precio_fijo') ?></span>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <form method="post" action="conta.php">
      <input type="hidden" name="acao" value="cambiar_plano" />
      <input type="hidden" name="id_plano" id="plano-selecionado-id" value="<?= $planos_disponiveis[0]['id_plano'] ?? '' ?>" />
      <button type="submit" class="botao-primario-grande"><?= traduz('modal_cambiar_a_este') ?></button>
    </form>
    <div class="modal-trust"><?= traduz('modal_trust_plan') ?></div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal: Trocar cartão -->
<div id="modal-tarjeta" class="modal-fundo" onclick="if(event.target===this)fecharModal(this.id)">
  <div class="modal-caixa">
    <button type="button" class="modal-fechar" onclick="fecharModal('modal-tarjeta')">✕</button>
    <div class="modal-icone">💳</div>
    <h2 class="modal-titulo"><?= traduz('modal_cambiar_tarjeta') ?></h2>
    <p class="modal-sub"><?= traduz('modal_cambiar_tarjeta_sub') ?></p>
    <div class="modal-campo">
      <label><?= traduz('modal_num_tarjeta') ?></label>
      <div class="modal-input">
        <span class="icone-campo">💳</span>
        <input type="text" placeholder="1234 5678 9012 3456" maxlength="19" data-mask="card" />
      </div>
    </div>
    <div class="modal-campo">
      <div class="campo-row">
        <div>
          <label><?= traduz('modal_vencimiento') ?></label>
          <div class="modal-input">
            <input type="text" placeholder="MM/AA" maxlength="5" data-mask="expiry" />
          </div>
        </div>
        <div>
          <label>CVC</label>
          <div class="modal-input">
            <input type="text" placeholder="123" maxlength="4" data-mask="cvc" />
          </div>
        </div>
      </div>
    </div>
    <div class="modal-campo">
      <label><?= traduz('modal_nombre_tarjeta') ?></label>
      <div class="modal-input">
        <input type="text" placeholder="Mariana López" />
      </div>
    </div>
    <button type="button" class="botao-primario-grande" onclick="fecharModal('modal-tarjeta')"><?= traduz('modal_guardar_tarjeta') ?></button>
    <div class="modal-trust"><?= traduz('modal_trust_tarjeta') ?></div>
  </div>
</div>

<!-- Modal: Cancelar plano -->
<div id="modal-cancelar" class="modal-fundo" onclick="if(event.target===this)fecharModal(this.id)">
  <div class="modal-caixa">
    <button type="button" class="modal-fechar" onclick="fecharModal('modal-cancelar')">✕</button>
    <div class="modal-icone">😥</div>
    <h2 class="modal-titulo"><?= traduz('modal_cancelar_plan') ?></h2>
    <p class="modal-sub"><?= traduz('modal_cancelar_sub') ?></p>
    <div class="modal-alerta">
      <span class="alerta-icone">🌟</span>
      <span class="alerta-texto"><?= traduz('modal_cancelar_alerta') ?></span>
      <span class="alerta-data"><?= $data_expira_fmt ?></span>
    </div>
    <button type="button" class="botao-primario-grande" onclick="fecharModal('modal-cancelar')"><?= traduz('modal_mantener') ?></button>
    <form method="post" action="conta.php">
      <input type="hidden" name="acao" value="cancelar_plano" />
      <button type="submit" class="botao-contorno-grande"><?= traduz('modal_si_cancelar') ?></button>
    </form>
  </div>
</div>

<script src="../assets/js/mascote.js"></script>
<script>
function abrirModal(id){ document.getElementById(id).classList.add('aberto'); }
function fecharModal(id){ document.getElementById(id).classList.remove('aberto'); }

function selecionarPlano(el){
  document.querySelectorAll('.plano-opcao').forEach(function(o){ o.classList.remove('selecionado'); });
  el.classList.add('selecionado');
  document.getElementById('plano-selecionado-id').value = el.dataset.id;
}

document.querySelectorAll('[data-mask="card"]').forEach(function(input){
  input.addEventListener('input', function(){
    var v = this.value.replace(/\D/g,'').substring(0,16);
    this.value = v.replace(/(.{4})/g,'$1 ').trim();
  });
});
document.querySelectorAll('[data-mask="expiry"]').forEach(function(input){
  input.addEventListener('input', function(){
    var v = this.value.replace(/\D/g,'').substring(0,4);
    if(v.length>=3) v = v.substring(0,2)+'/'+v.substring(2);
    this.value = v;
  });
});
document.querySelectorAll('[data-mask="cvc"]').forEach(function(input){
  input.addEventListener('input', function(){
    this.value = this.value.replace(/\D/g,'').substring(0,4);
  });
});
</script>
</body>
</html>
