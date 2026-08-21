<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesPlanos.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'assinaturas';

$tipos_validos = ['novas', 'renovacoes', 'falharam'];
$tipo_ativo = in_array($_GET['tipo'] ?? '', $tipos_validos, true) ? $_GET['tipo'] : '';

$filtro = [];
if (!empty($_GET['busca'])) {
    $filtro['busca'] = $_GET['busca'];
}
if (!empty($_GET['id_plano'])) {
    $filtro['id_plano'] = (int) $_GET['id_plano'];
}
if ($tipo_ativo === 'novas' || $tipo_ativo === 'renovacoes') {
    $filtro['tipo'] = $tipo_ativo;
    $filtro['data_inicio'] = date('Y-m-01');
} elseif ($tipo_ativo === 'falharam') {
    $filtro['status'] = 'falhou';
    $filtro['data_inicio'] = date('Y-m-01');
}

$rotulos_tipo = [
    'novas' => 'assin_novas',
    'renovacoes' => 'assin_renovacoes',
    'falharam' => 'assin_falharam',
];

$por_pagina = 30;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$pagamentos = listaPagamentos($filtro, $pagina, $por_pagina);
$resumo_filtro = resumoPagamentos($filtro);
$total_paginas = max(1, (int) ceil($resumo_filtro['total'] / $por_pagina));
$planos_disponiveis = listaPlanos();
$receita_filtrada = $resumo_filtro['receita'];

$historico_por_usuario = [];
$historico_total_por_usuario = [];
$status_plano_por_usuario = [];
foreach ($pagamentos as $p) {
    $uid = (int) $p['id_usuario'];
    if (!isset($historico_por_usuario[$uid])) {
        $historico_por_usuario[$uid] = listaPagamentos(['id_usuario' => $uid], 1, 30);
        $historico_total_por_usuario[$uid] = resumoPagamentos(['id_usuario' => $uid])['total'];

        $usuario_atual = buscaUsuarioPorId($uid);
        if ($usuario_atual['plano'] === 'trial' && $usuario_atual['plano_expira_em']) {
            $rotulo_data = traduz('admin_trial_termina');
            $valor_data = date('d/m/Y H:i', strtotime($usuario_atual['plano_expira_em']));
        } elseif ($usuario_atual['plano'] === 'ativo' && $usuario_atual['plano_expira_em']) {
            $rotulo_data = traduz('admin_proxima_cobranca');
            $valor_data = date('d/m/Y H:i', strtotime($usuario_atual['plano_expira_em']));
        } elseif ($usuario_atual['plano'] === 'cancelado' && !empty($usuario_atual['cancelado_em'])) {
            $rotulo_data = traduz('admin_cancelado_em');
            $valor_data = date('d/m/Y H:i', strtotime($usuario_atual['cancelado_em']));
        } else {
            $rotulo_data = traduz('admin_proxima_cobranca');
            $valor_data = '—';
        }
        $status_plano_por_usuario[$uid] = ['rotulo' => $rotulo_data, 'valor' => $valor_data];
    }
}

$novas_mes = contaNovasAssinaturasMes();
$renovacoes_mes = contaRenovacoesMes();
$canceladas_mes = contaCanceladosEsteMes();
$falhas_mes = contaFalhasMes();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg" />
<title><?= htmlspecialchars(nomeApp()) ?> — <?= traduz('admin_assinaturas') ?></title>
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
        <h1><?= traduz('admin_assinaturas') ?></h1>
        <div class="subtitulo"><?= $resumo_filtro['total'] ?> <?= traduz('admin_pagamentos_registrados') ?> · <?= simboloMoeda() ?><?= number_format($receita_filtrada, 2, ',', '.') ?></div>
      </div>
    </header>
    <div class="admin-area">

      <div class="grade-estatisticas">
        <a href="assinaturas?tipo=novas" class="cartao-estatistica" style="display:block;text-decoration:none;color:inherit;<?= $tipo_ativo === 'novas' ? 'box-shadow:inset 0 0 0 2px var(--primary);' : '' ?>">
          <div class="cabecalho-estatistica">
            <span class="rotulo-estatistica"><?= traduz('assin_novas') ?></span>
            <span class="icone-estatistica verde">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </span>
          </div>
          <div class="valor-estatistica"><?= $novas_mes ?></div>
          <div class="sub-estatistica"><?= traduz('dash_este_mes') ?></div>
        </a>
        <a href="assinaturas?tipo=renovacoes" class="cartao-estatistica" style="display:block;text-decoration:none;color:inherit;<?= $tipo_ativo === 'renovacoes' ? 'box-shadow:inset 0 0 0 2px var(--primary);' : '' ?>">
          <div class="cabecalho-estatistica">
            <span class="rotulo-estatistica"><?= traduz('assin_renovacoes') ?></span>
            <span class="icone-estatistica azul">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            </span>
          </div>
          <div class="valor-estatistica"><?= $renovacoes_mes ?></div>
          <div class="sub-estatistica"><?= traduz('dash_este_mes') ?></div>
        </a>
        <a href="usuarios?plano=cancelado" class="cartao-estatistica" style="display:block;text-decoration:none;color:inherit;">
          <div class="cabecalho-estatistica">
            <span class="rotulo-estatistica"><?= traduz('assin_canceladas') ?></span>
            <span class="icone-estatistica vermelho">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </span>
          </div>
          <div class="valor-estatistica"><?= $canceladas_mes ?></div>
          <div class="sub-estatistica"><?= traduz('dash_este_mes') ?></div>
        </a>
        <a href="assinaturas?tipo=falharam" class="cartao-estatistica" style="display:block;text-decoration:none;color:inherit;<?= $tipo_ativo === 'falharam' ? 'box-shadow:inset 0 0 0 2px var(--primary);' : '' ?>">
          <div class="cabecalho-estatistica">
            <span class="rotulo-estatistica"><?= traduz('assin_falharam') ?></span>
            <span class="icone-estatistica ambar">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </span>
          </div>
          <div class="valor-estatistica"><?= $falhas_mes ?></div>
          <div class="sub-estatistica"><?= traduz('dash_este_mes') ?></div>
        </a>
      </div>

      <div class="painel">
        <div class="painel-cabecalho">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-4)"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          <h2 class="painel-titulo">
            <?= traduz('admin_lista_assinaturas') ?>
            <?php if ($tipo_ativo): ?>
            <span class="selo azul" style="margin-left:6px;"><?= traduz($rotulos_tipo[$tipo_ativo]) ?> · <?= traduz('dash_este_mes') ?></span>
            <a href="assinaturas" style="font-size:12.5px;font-weight:600;margin-left:6px;"><?= traduz('admin_limpar_filtro') ?></a>
            <?php endif; ?>
          </h2>
          <div class="espacador"></div>
          <form method="get" action="assinaturas" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo_ativo) ?>" />
            <div class="caixa-busca">
              <span class="icone"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
              <input type="text" name="busca" placeholder="<?= traduz('admin_buscar') ?>" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>" />
            </div>
            <select name="id_plano" class="filtro-select" onchange="this.form.submit()">
              <option value=""><?= traduz('admin_todos_planos') ?></option>
              <?php foreach ($planos_disponiveis as $p): ?>
              <option value="<?= (int) $p['id_plano'] ?>" <?= (int) ($_GET['id_plano'] ?? 0) === (int) $p['id_plano'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
        <?php if ($pagamentos): ?>
        <table>
          <thead>
            <tr>
              <th><?= traduz('admin_nome') ?></th>
              <th><?= traduz('admin_email_col') ?></th>
              <th><?= traduz('admin_plano') ?></th>
              <th><?= traduz('admin_tipo') ?></th>
              <th><?= traduz('admin_ciclo') ?></th>
              <th><?= traduz('admin_valor') ?></th>
              <th><?= traduz('admin_status') ?></th>
              <th><?= traduz('admin_data') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pagamentos as $p): ?>
            <tr style="cursor:pointer;" onclick="abrirHistorico(<?= htmlspecialchars(json_encode([
                'nome' => $p['nome_usuario'],
                'email' => $p['email_usuario'],
                'itens' => $historico_por_usuario[(int) $p['id_usuario']],
                'total' => $historico_total_por_usuario[(int) $p['id_usuario']],
                'status_plano' => $status_plano_por_usuario[(int) $p['id_usuario']],
            ], JSON_HEX_APOS | JSON_HEX_TAG)) ?>)">
              <td style="color:var(--ink);font-weight:600;white-space:nowrap;"><?= htmlspecialchars($p['nome_usuario']) ?></td>
              <td><?= htmlspecialchars($p['email_usuario']) ?></td>
              <td><?= htmlspecialchars($p['nome_plano'] ?? '—') ?></td>
              <td>
                <?php if ($p['tipo_pagamento'] === 'nova'): ?>
                <span class="selo verde"><?= traduz('assin_nova_singular') ?></span>
                <?php elseif ($p['tipo_pagamento'] === 'renovacao'): ?>
                <span class="selo azul"><?= traduz('assin_renovacao_singular') ?></span>
                <?php else: ?>
                —
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars(ucfirst($p['ciclo'])) ?></td>
              <td><?= simboloMoeda() ?><?= number_format((float) $p['valor'], 2, ',', '.') ?></td>
              <?php
                $cores_status = ['pago' => 'verde', 'falhou' => 'vermelho', 'reembolsado' => 'ambar'];
                $rotulos_status = ['pago' => traduz('admin_pago'), 'falhou' => traduz('admin_falhou'), 'reembolsado' => traduz('admin_reembolsado')];
              ?>
              <td><span class="selo <?= $cores_status[$p['status']] ?? 'neutro' ?>"><?= $rotulos_status[$p['status']] ?? $p['status'] ?></span></td>
              <td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if ($total_paginas > 1): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 4px 2px;">
          <span style="font-size:12.5px;color:var(--ink-4);font-weight:600;"><?= traduz('admin_pagina') ?> <?= $pagina ?> / <?= $total_paginas ?></span>
          <div style="display:flex;gap:8px;">
            <?php
              $query_base = array_filter(['busca' => $_GET['busca'] ?? '', 'id_plano' => $_GET['id_plano'] ?? '', 'tipo' => $tipo_ativo]);
            ?>
            <?php if ($pagina > 1): ?>
            <a class="botao-pequeno botao-fantasma" href="assinaturas?<?= http_build_query(array_merge($query_base, ['pagina' => $pagina - 1])) ?>"><?= traduz('admin_anterior') ?></a>
            <?php endif; ?>
            <?php if ($pagina < $total_paginas): ?>
            <a class="botao-pequeno botao-fantasma" href="assinaturas?<?= http_build_query(array_merge($query_base, ['pagina' => $pagina + 1])) ?>"><?= traduz('admin_proxima') ?></a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="estado-vazio">
          <div class="icone-vazio">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          </div>
          <?= traduz('admin_nenhuma_assinatura') ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal Histórico da assinatura -->
<div id="modal-historico" class="modal-overlay" onclick="if(event.target===this)fecharHistorico()">
  <div class="modal">
    <div class="modal-header">
      <h2 id="hist-titulo"><?= traduz('admin_historico_pagamentos') ?></h2>
      <button type="button" class="modal-fechar" onclick="fecharHistorico()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div id="hist-email" style="font-size:12.5px;color:var(--ink-4);font-weight:600;margin-bottom:10px;"></div>
      <div class="resumo-linha" style="margin-bottom:14px;"><span id="hist-rotulo-status"></span><b id="hist-valor-status"></b></div>
      <div id="hist-linha-tempo"></div>
      <div id="hist-aviso-limite" style="font-size:12px;color:var(--ink-4);text-align:center;margin-top:10px;display:none;"></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="botao-pequeno botao-fantasma" onclick="fecharHistorico()"><?= traduz('admin_fechar') ?></button>
    </div>
  </div>
</div>

<script>
function escapaHtml(texto) {
    var div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

function abrirHistorico(dados) {
    document.getElementById('hist-titulo').textContent = dados.nome;
    document.getElementById('hist-email').textContent = dados.email;
    document.getElementById('hist-rotulo-status').textContent = dados.status_plano.rotulo;
    document.getElementById('hist-valor-status').textContent = dados.status_plano.valor;

    var moeda = <?= json_encode(simboloMoeda()) ?>;
    var rotulos = {
        nova: <?= json_encode(traduz('assin_nova_singular')) ?>,
        renovacao: <?= json_encode(traduz('assin_renovacao_singular')) ?>,
        pago: <?= json_encode(traduz('admin_pago')) ?>,
        falhou: <?= json_encode(traduz('admin_falhou')) ?>,
        reembolsado: <?= json_encode(traduz('admin_reembolsado')) ?>
    };
    var coresStatus = { pago: 'verde', falhou: 'vermelho', reembolsado: 'ambar' };

    var container = document.getElementById('hist-linha-tempo');
    container.innerHTML = '';
    dados.itens.forEach(function (item) {
        var linha = document.createElement('div');
        linha.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--line-soft);';

        var corStatus = coresStatus[item.status] || 'neutro';
        var tipoHtml = '';
        if (item.tipo_pagamento === 'nova') {
            tipoHtml = '<span class="selo verde" style="margin-right:6px;">' + rotulos.nova + '</span>';
        } else if (item.tipo_pagamento === 'renovacao') {
            tipoHtml = '<span class="selo azul" style="margin-right:6px;">' + rotulos.renovacao + '</span>';
        }

        var data = new Date(item.criado_em.replace(' ', 'T'));
        var dataFmt = data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        var valorFmt = moeda + Number(item.valor).toFixed(2).replace('.', ',');

        var planoNome = escapaHtml(item.nome_plano || '—');

        linha.innerHTML =
            '<div style="flex:1;">' +
            '<div style="font-weight:700;color:var(--ink);font-size:13.5px;margin-bottom:4px;">' + planoNome + '</div>' +
            tipoHtml +
            '<span class="selo ' + corStatus + '">' + rotulos[item.status] + '</span></div>' +
            '<div style="font-weight:700;color:var(--ink);">' + valorFmt + '</div>' +
            '<div style="font-size:12.5px;color:var(--ink-4);white-space:nowrap;">' + dataFmt + '</div>';
        container.appendChild(linha);
    });

    var aviso = document.getElementById('hist-aviso-limite');
    if (dados.total > dados.itens.length) {
        aviso.textContent = <?= json_encode(traduz('assin_mostrando_recentes')) ?>
            .replace('%d', dados.itens.length).replace('%d', dados.total);
        aviso.style.display = '';
    } else {
        aviso.style.display = 'none';
    }

    document.getElementById('modal-historico').classList.add('aberto');
    document.body.style.overflow = 'hidden';
}

function fecharHistorico() {
    document.getElementById('modal-historico').classList.remove('aberto');
    document.body.style.overflow = '';
}
</script>

<script src="../assets/js/mascote.js"></script>
</body>
</html>
