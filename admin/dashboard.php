<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesEventos.php';
require_once __DIR__ . '/../funcoes/funcoesPlanos.php';
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'dashboard';

$total_usuarios = contaTotalUsuarios();
$usuarios_ativos = contaUsuariosAtivos();
$por_plano = contaUsuariosPorPlano();
$total_eventos = contaTotalEventos();
$eventos_hoje = contaEventosHoje();
$total_mensagens = contaTotalMensagens();
$novos_semana = contaUsuariosNovosEstaSemana();
$novos_mes = contaUsuariosNovosEsteMes();
$receita_estimada = calculaReceitaTotal();
$cancelados_mes = contaCanceladosEsteMes();
$plano_detalhado = contaUsuariosPorPlanoDetalhado();
$eventos_recentes = listaEventosRecentes(6);

$usuarios_por_dia = contaUsuariosPorDia(30);
$cancelamentos_por_dia = contaCancelamentosPorDia(30);
$receita_por_mes = receitaPorMes(6);

$taxa_conversao = $total_usuarios > 0
    ? round(($por_plano['ativo'] ?? 0) / $total_usuarios * 100, 1)
    : 0;

function preencheDias($dados, $dias = 30) {
    $mapa = [];
    foreach ($dados as $d) $mapa[$d['dia']] = (int)$d['total'];
    $labels = [];
    $values = [];
    for ($i = $dias - 1; $i >= 0; $i--) {
        $dia = date('Y-m-d', strtotime("-{$i} days"));
        $labels[] = date('d/m', strtotime($dia));
        $values[] = $mapa[$dia] ?? 0;
    }
    return ['labels' => $labels, 'values' => $values];
}

$grafico_novos = preencheDias($usuarios_por_dia);
$grafico_cancelamentos = preencheDias($cancelamentos_por_dia);
$simbolo = simboloMoeda();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(nomeApp()) ?> — <?= traduz('admin_dashboard') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/admin.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="admin-estrutura">
  <?php require __DIR__ . '/_includes/sidebar.php'; ?>
  <div class="admin-conteudo">
    <header class="admin-barra">
      <div>
        <h1><?= traduz('admin_dashboard') ?></h1>
        <div class="subtitulo"><?= traduz('admin_visao_geral') ?></div>
      </div>
    </header>
    <div class="admin-area">

      <!-- KPIs principai -->
      <div class="grade-estatisticas">
        <div class="cartao-estatistica destaque">
          <div class="cabecalho-estatistica">
            <span class="rotulo-estatistica"><?= traduz('dash_receita_estimada') ?></span>
            <span class="icone-estatistica verde">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </span>
          </div>
          <div class="valor-estatistica"><?= $simbolo ?><?= number_format($receita_estimada, 0, '.', ',') ?></div>
          <div class="sub-estatistica"><?= $por_plano['ativo'] ?? 0 ?> <?= traduz('dash_assinantes') ?></div>
        </div>
        <div class="cartao-estatistica">
          <div class="cabecalho-estatistica">
            <span class="rotulo-estatistica"><?= traduz('dash_novos_semana') ?></span>
            <span class="icone-estatistica azul">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            </span>
          </div>
          <div class="valor-estatistica"><?= $novos_semana ?></div>
          <div class="sub-estatistica"><?= $novos_mes ?> <?= traduz('dash_este_mes') ?></div>
        </div>
        <div class="cartao-estatistica">
          <div class="cabecalho-estatistica">
            <span class="rotulo-estatistica"><?= traduz('dash_cancelamentos') ?></span>
            <span class="icone-estatistica vermelho">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </span>
          </div>
          <div class="valor-estatistica"><?= $cancelados_mes ?></div>
          <div class="sub-estatistica"><?= traduz('dash_este_mes') ?></div>
        </div>
        <div class="cartao-estatistica">
          <div class="cabecalho-estatistica">
            <span class="rotulo-estatistica"><?= traduz('dash_conversao') ?></span>
            <span class="icone-estatistica roxo">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </span>
          </div>
          <div class="valor-estatistica"><?= $taxa_conversao ?>%</div>
          <div class="sub-estatistica"><?= traduz('dash_trial_para_ativo') ?></div>
        </div>
      </div>

      <!-- Gráfico receita + Distribuição planos -->
      <div class="grade-graficos">
        <div class="painel">
          <div class="painel-cabecalho">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-4)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <h2 class="painel-titulo"><?= traduz('dash_grafico_receita') ?></h2>
          </div>
          <div class="grafico-container"><canvas id="grafico-receita"></canvas></div>
        </div>
        <div class="painel">
          <div class="painel-cabecalho">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-4)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <h2 class="painel-titulo"><?= traduz('dash_grafico_planos') ?></h2>
          </div>
          <div class="grafico-container grafico-doughnut"><canvas id="grafico-planos"></canvas></div>
        </div>
      </div>

      <!-- Gráfico novos usuários vs cancelamentos -->
      <div class="grade-graficos">
        <div class="painel" style="grid-column: 1 / -1;">
          <div class="painel-cabecalho">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-4)"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <h2 class="painel-titulo"><?= traduz('dash_grafico_usuarios') ?></h2>
          </div>
          <div class="grafico-container"><canvas id="grafico-usuarios"></canvas></div>
        </div>
      </div>

      <!-- Resumo operacional + Eventos recentes -->
      <div class="grade-graficos">
        <div class="painel">
          <div class="painel-cabecalho">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-4)"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
            <h2 class="painel-titulo"><?= traduz('dash_resumo') ?></h2>
          </div>
          <div style="padding:4px 20px 16px;">
            <div class="resumo-linha">
              <span><?= traduz('admin_total_usuarios') ?></span>
              <b><?= $total_usuarios ?></b>
            </div>
            <div class="resumo-linha">
              <span><?= traduz('dash_ativos') ?></span>
              <b><?= $usuarios_ativos ?></b>
            </div>
            <div class="resumo-linha">
              <span><?= traduz('admin_em_trial') ?></span>
              <b><?= $por_plano['trial'] ?? 0 ?></b>
            </div>
            <div class="resumo-linha">
              <span><?= traduz('admin_cancelados') ?></span>
              <b style="color:var(--vermelho)"><?= $por_plano['cancelado'] ?? 0 ?></b>
            </div>
            <div class="resumo-linha">
              <span><?= traduz('admin_eventos_criados') ?></span>
              <b><?= $total_eventos ?></b>
            </div>
            <div class="resumo-linha">
              <span><?= traduz('admin_eventos_hoje') ?></span>
              <b><?= $eventos_hoje ?></b>
            </div>
            <div class="resumo-linha">
              <span><?= traduz('admin_mensagens_trocadas') ?></span>
              <b><?= $total_mensagens ?></b>
            </div>
          </div>
        </div>
        <div class="painel">
          <div class="painel-cabecalho">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-4)"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <h2 class="painel-titulo"><?= traduz('admin_eventos_recentes') ?></h2>
          </div>
          <?php if ($eventos_recentes): ?>
          <table>
            <thead>
              <tr>
                <th><?= traduz('admin_titulo') ?></th>
                <th><?= traduz('admin_usuario') ?></th>
                <th><?= traduz('admin_data') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($eventos_recentes as $ev): ?>
              <tr>
                <td style="color:var(--ink);font-weight:600;"><?= htmlspecialchars($ev['titulo']) ?></td>
                <td><?= htmlspecialchars($ev['nome_usuario']) ?></td>
                <td><?= date('d/m H:i', strtotime($ev['data_inicio'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <div class="estado-vazio">
            <div class="icone-vazio">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <?= traduz('admin_nenhum_evento') ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
<script>
(function(){
    var font = "'Nunito', sans-serif";
    var grid = 'rgba(0,0,0,.04)';
    var tip = { titleFont:{family:font,weight:'700',size:12}, bodyFont:{family:font,size:12}, padding:10, cornerRadius:8, displayColors:true, boxPadding:4 };
    var ax = { ticks:{font:{family:font,size:11,weight:'600'},color:'#94a3b8',maxTicksLimit:8}, grid:{color:grid} };
    var leg = { position:'bottom', labels:{font:{family:font,size:12,weight:'600'},padding:14,usePointStyle:true,pointStyle:'circle'} };

    // Receita por mês (barras)
    new Chart(document.getElementById('grafico-receita'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($receita_por_mes, 'mes')) ?>,
            datasets: [{
                label: '<?= traduz('dash_receita_estimada') ?>',
                data: <?= json_encode(array_column($receita_por_mes, 'receita')) ?>,
                backgroundColor: 'rgba(34,197,94,.7)',
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tip,
                    callbacks: { label: function(c){ return '<?= $simbolo ?>' + c.raw.toLocaleString(); } }
                }
            },
            scales: {
                x: ax,
                y: { ...ax, beginAtZero:true, ticks:{...ax.ticks, callback:function(v){return '<?= $simbolo ?>'+v;}} }
            }
        }
    });

    // Distribuição de planos (doughnut)
    new Chart(document.getElementById('grafico-planos'), {
        type: 'doughnut',
        data: {
            labels: ['<?= traduz('admin_em_trial') ?>','<?= traduz('admin_plano_ativo') ?>','<?= traduz('admin_cancelados') ?>','<?= traduz('dash_inativos') ?>'],
            datasets: [{
                data: [<?= $plano_detalhado['trial'] ?>,<?= $plano_detalhado['ativo'] ?>,<?= $plano_detalhado['cancelado'] ?>,<?= $plano_detalhado['inativos'] ?>],
                backgroundColor: ['#f59e0b','#22c55e','#ef4444','#cbd5e1'],
                borderWidth: 0, spacing: 3, borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '68%',
            plugins: { legend: leg, tooltip: tip }
        }
    });

    // Novos usuários vs Cancelamentos (linhas)
    new Chart(document.getElementById('grafico-usuarios'), {
        type: 'line',
        data: {
            labels: <?= json_encode($grafico_novos['labels']) ?>,
            datasets: [
                {
                    label: '<?= traduz('dash_leg_usuarios') ?>',
                    data: <?= json_encode($grafico_novos['values']) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,.08)',
                    fill: true, tension:.35, borderWidth:2.5, pointRadius:0, pointHitRadius:12
                },
                {
                    label: '<?= traduz('dash_cancelamentos') ?>',
                    data: <?= json_encode($grafico_cancelamentos['values']) ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,.06)',
                    fill: true, tension:.35, borderWidth:2.5, pointRadius:0, pointHitRadius:12
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode:'index', intersect:false },
            plugins: { legend:leg, tooltip:tip },
            scales: { x:ax, y:{...ax, beginAtZero:true} }
        }
    });
})();
</script>
</body>
</html>
