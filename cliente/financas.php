<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesFinancas.php';

iniciaSessao();
exigeLoginCliente();

$pagina_atual = 'financas';
$id_usuario = usuarioLogadoId();
$usuario = buscaUsuarioPorId($id_usuario);
$msg_sucesso = '';
$msg_erro = '';

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar' || $acao === 'editar') {
        $tipo = trim($_POST['tipo'] ?? '');
        $valor = (float) ($_POST['valor'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'outros');
        $data_transacao = trim($_POST['data_transacao'] ?? '');

        if (!in_array($tipo, ['entrada', 'saida'], true) || $valor <= 0 || $descricao === '' || $data_transacao === '') {
            $msg_erro = $valor <= 0 ? traduz('fin_erro_valor') : traduz('fin_erro_campos');
        } else {
            $dados = [
                'id_usuario' => $id_usuario,
                'tipo' => $tipo,
                'valor' => $valor,
                'descricao' => $descricao,
                'categoria' => $tipo === 'entrada' ? 'entrada' : $categoria,
                'data_transacao' => $data_transacao,
            ];

            if ($acao === 'criar') {
                insereTransacao($dados);
                $msg_sucesso = traduz('fin_criada');
            } else {
                $id_transacao = (int) ($_POST['id_transacao'] ?? 0);
                $existente = buscaTransacaoPorId($id_transacao);
                if ($existente && (int) $existente['id_usuario'] === $id_usuario) {
                    unset($dados['id_usuario']);
                    atualizaTransacao($id_transacao, $dados);
                    $msg_sucesso = traduz('fin_atualizada');
                }
            }
        }
    }

    if ($acao === 'deletar') {
        $id_transacao = (int) ($_POST['id_transacao'] ?? 0);
        $existente = buscaTransacaoPorId($id_transacao);
        if ($existente && (int) $existente['id_usuario'] === $id_usuario) {
            deletaTransacao($id_transacao);
            $msg_sucesso = traduz('fin_deletada');
        }
    }

    if ($msg_sucesso) {
        $redir = 'financas.php?' . http_build_query(array_filter([
            'mes' => $_GET['mes'] ?? '',
            'ano' => $_GET['ano'] ?? '',
            'sucesso' => $msg_sucesso,
        ]));
        header("Location: $redir");
        exit;
    }
}

if (!empty($_GET['sucesso'])) {
    $msg_sucesso = $_GET['sucesso'];
}

// Mês/ano selecionado
$mes_atual = (int) ($_GET['mes'] ?? date('n'));
$ano_atual = (int) ($_GET['ano'] ?? date('Y'));
if ($mes_atual < 1 || $mes_atual > 12) $mes_atual = (int) date('n');
if ($ano_atual < 2020 || $ano_atual > 2099) $ano_atual = (int) date('Y');

// Mês anterior para navegação e comparação
$dt_anterior = new DateTime("$ano_atual-$mes_atual-01");
$dt_anterior->modify('-1 month');
$mes_ant = (int) $dt_anterior->format('n');
$ano_ant = (int) $dt_anterior->format('Y');

$dt_proximo = new DateTime("$ano_atual-$mes_atual-01");
$dt_proximo->modify('+1 month');
$mes_prox = (int) $dt_proximo->format('n');
$ano_prox = (int) $dt_proximo->format('Y');

// Dados
$resumo = resumoMensal($id_usuario, $mes_atual, $ano_atual);
$resumo_ant = resumoMensal($id_usuario, $mes_ant, $ano_ant);
$saidas_cat = saidasPorCategoria($id_usuario, $mes_atual, $ano_atual);
$historico_6m = resumoUltimos6Meses($id_usuario, $mes_atual, $ano_atual);

$resultado_lista = listaTransacoes($id_usuario, [
    'mes' => $mes_atual,
    'ano' => $ano_atual,
    'pagina' => $_GET['pagina'] ?? 1,
]);
$transacoes = $resultado_lista['transacoes'];
$total_paginas = $resultado_lista['total_paginas'];
$pagina = $resultado_lista['pagina'];

// % variação
function calcVariacao($atual, $anterior) {
    if ($anterior == 0) return $atual > 0 ? 100 : 0;
    return round((($atual - $anterior) / $anterior) * 100);
}

$var_saldo = calcVariacao($resumo['saldo'], $resumo_ant['saldo']);
$var_entradas = calcVariacao($resumo['total_entradas'], $resumo_ant['total_entradas']);
$var_saidas = calcVariacao($resumo['total_saidas'], $resumo_ant['total_saidas']);

$nome_mes_ant = traduz('fin_mes_curto_' . $mes_ant);
$nome_mes_atual = traduz('fin_mes_' . $mes_atual);

$categorias = categoriasValidas();

// Badge do plano
$agora = new DateTime();
$expirado = $usuario['plano_expira_em'] && new DateTime($usuario['plano_expira_em']) < $agora;
$dias_restantes_trial = (!$expirado && $usuario['plano_expira_em'])
    ? (int) $agora->diff(new DateTime($usuario['plano_expira_em']))->days : 0;
if ($expirado) {
    $badge_plano = traduz('badge_expirado');
    $badge_cor = 'vermelho';
} elseif ($usuario['plano'] === 'trial') {
    $badge_plano = str_replace('5', (string) $dias_restantes_trial, traduz('badge_prueba_dias'));
    $badge_cor = 'ambar';
} elseif ($usuario['plano'] === 'ativo') {
    $badge_plano = traduz('badge_ativo');
    $badge_cor = 'verde';
} else {
    $badge_plano = '—';
    $badge_cor = 'ambar';
}

// Helper para formatar valor
function formatValor($v) {
    return '$' . number_format(abs($v), 0, '.', ',');
}
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(nomeApp()) ?> — <?= traduz('fin_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css" />
</head>
<body>

<?php
// ── Bloco de conteúdo reutilizado em mobile e desktop ──
ob_start();
?>

<?php if ($msg_sucesso): ?>
<div class="sucesso-msg" style="margin-bottom:12px;"><?= htmlspecialchars($msg_sucesso) ?></div>
<?php endif; ?>
<?php if ($msg_erro): ?>
<div class="erro-msg" style="margin-bottom:12px;"><?= htmlspecialchars($msg_erro) ?></div>
<?php endif; ?>

<!-- Card Saldo -->
<div class="fin-card-saldo">
  <div class="saldo-label"><span class="ponto-saldo"></span> <?= traduz('fin_saldo_mes') ?></div>
  <div class="saldo-valor"><?= $resumo['saldo'] >= 0 ? '+' : '-' ?><?= formatValor($resumo['saldo']) ?></div>
  <div class="saldo-var"><?= $var_saldo >= 0 ? '▲' : '▼' ?> <?= abs($var_saldo) ?>% <?= sprintf(traduz('fin_vs_mes_anterior'), strtolower($nome_mes_ant)) ?></div>
</div>

<!-- Cards Ingresos / Gastos -->
<div class="fin-resumo-grid">
  <div class="fin-resumo-card">
    <div class="resumo-label"><?= traduz('fin_ingresos') ?></div>
    <div class="resumo-valor"><?= formatValor($resumo['total_entradas']) ?></div>
    <div class="resumo-var <?= $var_entradas >= 0 ? 'positivo' : 'negativo' ?>"><?= $var_entradas >= 0 ? '▲' : '▼' ?> <?= abs($var_entradas) ?>%</div>
  </div>
  <div class="fin-resumo-card">
    <div class="resumo-label"><?= traduz('fin_gastos') ?></div>
    <div class="resumo-valor"><?= formatValor($resumo['total_saidas']) ?></div>
    <div class="resumo-var <?= $var_saidas >= 0 ? 'negativo' : 'positivo' ?>"><?= $var_saidas >= 0 ? '▲' : '▼' ?> <?= abs($var_saidas) ?>%</div>
  </div>
</div>

<!-- Gastos por Categoría -->
<?php if ($saidas_cat): ?>
<div class="fin-secao">
  <div class="fin-secao-titulo"><?= traduz('fin_gastos_categoria') ?></div>
  <div class="fin-secao-subtitulo"><?= sprintf(traduz('fin_distribucion'), strtolower($nome_mes_atual), number_format($resumo['total_saidas'], 0, '.', ',')) ?></div>
  <div class="fin-donut-area">
    <canvas class="js-donut-chart" class="fin-donut-canvas" width="140" height="140"></canvas>
    <div class="fin-donut-legenda">
      <?php foreach ($saidas_cat as $sc):
        $cat_key = $sc['categoria'];
        $cat_info = $categorias[$cat_key] ?? $categorias['outros'];
      ?>
      <div class="fin-legenda-item">
        <span class="fin-legenda-cor" style="background:<?= $cat_info['cor'] ?>"></span>
        <span class="fin-legenda-nome"><?= traduz('fin_cat_' . $cat_key) ?></span>
        <span class="fin-legenda-valor"><?= formatValor($sc['total']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Gráfico Ingresos vs Gastos -->
<div class="fin-secao">
  <div class="fin-secao-titulo"><?= traduz('fin_grafico_titulo') ?></div>
  <div class="fin-secao-subtitulo"><?= traduz('fin_grafico_subtitulo') ?></div>
  <div class="fin-grafico-legenda">
    <span><span class="fin-legenda-ponto" style="background:#4D7CFE"></span> <?= traduz('fin_ingresos') ?></span>
    <span><span class="fin-legenda-ponto" style="background:#c7d6ff"></span> <?= traduz('fin_gastos') ?></span>
  </div>
  <canvas class="js-bar-chart fin-grafico-canvas"></canvas>
</div>

<!-- Transações Recentes -->
<div class="fin-secao-transacoes">
  <div class="fin-transacoes-cabecalho">
    <span class="fin-transacoes-rotulo"><?= traduz('fin_transacoes_titulo') ?></span>
    <button type="button" class="fin-botao-novo" onclick="document.getElementById('modal-criar').classList.add('aberto')"><?= traduz('fin_novo') ?></button>
  </div>
  <div class="fin-busca-container">
    <span class="icone-busca"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
    <input type="text" class="js-fin-busca" placeholder="<?= traduz('fin_buscar') ?>" />
  </div>

  <?php if (!$transacoes): ?>
  <div class="nota-caixa"><?= traduz('fin_sem_transacoes') ?></div>
  <?php endif; ?>

  <?php foreach ($transacoes as $t):
    $cat_info = $categorias[$t['categoria']] ?? $categorias['outros'];
    $positivo = $t['tipo'] === 'entrada';
    $dt = new DateTime($t['data_transacao']);
    $dia_fmt = $dt->format('d') . ' ' . strtolower(traduz('fin_mes_curto_' . (int) $dt->format('n')));
    $dados_json = htmlspecialchars(json_encode([
        'id_transacao' => $t['id_transacao'],
        'tipo' => $t['tipo'],
        'valor' => (float) $t['valor'],
        'descricao' => $t['descricao'],
        'categoria' => $t['categoria'],
        'data_transacao' => $t['data_transacao'],
    ], JSON_HEX_APOS | JSON_HEX_TAG), ENT_QUOTES);
  ?>
  <div class="fin-item" data-transacao='<?= $dados_json ?>'>
    <div class="fin-item-icone" style="background:<?= $cat_info['cor'] ?>18;"><?= $cat_info['emoji'] ?></div>
    <div class="fin-item-info">
      <div class="fin-item-nome"><?= htmlspecialchars($t['descricao']) ?></div>
      <div class="fin-item-meta">
        <span class="fin-item-categoria"><?= traduz('fin_cat_' . $t['categoria']) ?></span>
        · <?= $dia_fmt ?>
      </div>
    </div>
    <div class="fin-item-valor <?= $positivo ? 'positivo' : 'negativo' ?>"><?= $positivo ? '+ ' : '— ' ?><?= formatValor($t['valor']) ?></div>
  </div>
  <?php endforeach; ?>

  <!-- Paginação -->
  <?php if ($total_paginas > 1): ?>
  <div class="fin-paginacao">
    <?php if ($pagina > 1): ?>
    <a href="?mes=<?= $mes_atual ?>&ano=<?= $ano_atual ?>&pagina=<?= $pagina - 1 ?>">‹</a>
    <?php endif; ?>
    <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
      <?php if ($p === $pagina): ?>
      <span class="ativo"><?= $p ?></span>
      <?php else: ?>
      <a href="?mes=<?= $mes_atual ?>&ano=<?= $ano_atual ?>&pagina=<?= $p ?>"><?= $p ?></a>
      <?php endif; ?>
    <?php endfor; ?>
    <?php if ($pagina < $total_paginas): ?>
    <a href="?mes=<?= $mes_atual ?>&ano=<?= $ano_atual ?>&pagina=<?= $pagina + 1 ?>">›</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Nota WhatsApp -->
<div class="fin-nota">
  <span class="fin-nota-icone"><span data-bot="ink" data-size="20"></span></span>
  <?= traduz('fin_nota_whatsapp') ?>
</div>

<?php $conteudo_financas = ob_get_clean(); ?>

<!-- ══════ MOBILE ══════ -->
<div class="vista-mobile">
  <div class="barra-topo">
    <div class="marca"><span class="logo"><span data-bot="ink" data-size="20"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
    <div class="fin-seletor-mes">
      <a href="?mes=<?= $mes_ant ?>&ano=<?= $ano_ant ?>">‹</a>
      <span class="mes-label"><?= $nome_mes_atual ?> <?= $ano_atual ?></span>
      <a href="?mes=<?= $mes_prox ?>&ano=<?= $ano_prox ?>">›</a>
    </div>
  </div>
  <div class="conteudo-pagina espacado">
    <div style="margin-bottom:14px;">
      <h1 class="tela-titulo" style="margin:0;"><?= traduz('fin_titulo') ?></h1>
      <p class="tela-subtitulo" style="margin:4px 0 0;"><?= traduz('fin_subtitulo') ?></p>
    </div>
    <?= $conteudo_financas ?>
    <div class="barra-abas-espaco"></div>
  </div>
  <?php require __DIR__ . '/_includes/menu-inferior.php'; ?>
</div>

<!-- ══════ DESKTOP ══════ -->
<div class="vista-desktop">
  <div class="app-estrutura">
    <?php require __DIR__ . '/_includes/menu-lateral.php'; ?>
    <div class="conteudo-principal">
      <header class="barra-superior">
        <div><h1 class="saudacao" style="margin:0;"><?= traduz('fin_titulo') ?></h1><div class="barra-superior-subtitulo"><?= traduz('fin_subtitulo') ?></div></div>
        <div class="espaco"></div>
        <div class="fin-seletor-mes">
          <a href="?mes=<?= $mes_ant ?>&ano=<?= $ano_ant ?>">‹</a>
          <span class="mes-label"><?= $nome_mes_atual ?> <?= $ano_atual ?></span>
          <a href="?mes=<?= $mes_prox ?>&ano=<?= $ano_prox ?>">›</a>
        </div>
      </header>
      <div class="conteudo-area">
        <?= $conteudo_financas ?>
      </div>
    </div>
  </div>
</div>

<!-- ══════ MODAL CRIAR ══════ -->
<div id="modal-criar" class="modal-fundo" onclick="if(event.target===this)this.classList.remove('aberto')">
  <div class="modal-caixa">
    <button type="button" class="modal-fechar" onclick="this.closest('.modal-fundo').classList.remove('aberto')">✕</button>
    <div class="modal-icone">💰</div>
    <h2 class="modal-titulo"><?= traduz('fin_modal_novo_titulo') ?></h2>
    <form method="post" action="financas.php?<?= http_build_query(['mes' => $mes_atual, 'ano' => $ano_atual]) ?>">
      <input type="hidden" name="acao" value="criar" />
      <div class="modal-campo">
        <label><?= traduz('fin_campo_tipo') ?></label>
        <div class="modal-input">
          <span class="icone-campo">📊</span>
          <select name="tipo" id="criar-tipo">
            <option value="saida"><?= traduz('fin_tipo_saida') ?></option>
            <option value="entrada"><?= traduz('fin_tipo_entrada') ?></option>
          </select>
        </div>
      </div>
      <div class="modal-campo">
        <label><?= traduz('fin_campo_valor') ?></label>
        <div class="modal-input">
          <span class="icone-campo">$</span>
          <input type="number" name="valor" step="0.01" min="0.01" required placeholder="0.00" />
        </div>
      </div>
      <div class="modal-campo">
        <label><?= traduz('fin_campo_descricao') ?></label>
        <div class="modal-input">
          <span class="icone-campo">✏️</span>
          <input type="text" name="descricao" required placeholder="<?= traduz('fin_descricao_placeholder') ?>" />
        </div>
      </div>
      <div class="modal-campo">
        <label><?= traduz('fin_campo_categoria') ?></label>
        <div class="modal-input">
          <span class="icone-campo">🏷️</span>
          <select name="categoria">
            <?php foreach ($categorias as $key => $info): ?>
            <option value="<?= $key ?>"><?= $info['emoji'] ?> <?= traduz('fin_cat_' . $key) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-campo">
        <label><?= traduz('fin_campo_data') ?></label>
        <div class="modal-input">
          <span class="icone-campo">📅</span>
          <input type="date" name="data_transacao" required value="<?= date('Y-m-d') ?>" />
        </div>
      </div>
      <button type="submit" class="botao-primario-grande"><?= traduz('fin_botao_salvar') ?></button>
    </form>
  </div>
</div>

<!-- ══════ MODAL EDITAR ══════ -->
<div id="modal-editar" class="modal-fundo" onclick="if(event.target===this)this.classList.remove('aberto')">
  <div class="modal-caixa">
    <button type="button" class="modal-fechar" onclick="this.closest('.modal-fundo').classList.remove('aberto')">✕</button>
    <div class="modal-icone">✏️</div>
    <h2 class="modal-titulo"><?= traduz('fin_modal_editar_titulo') ?></h2>
    <form method="post" action="financas.php?<?= http_build_query(['mes' => $mes_atual, 'ano' => $ano_atual]) ?>">
      <input type="hidden" name="acao" value="editar" />
      <input type="hidden" name="id_transacao" id="editar-id" value="" />
      <div class="modal-campo">
        <label><?= traduz('fin_campo_tipo') ?></label>
        <div class="modal-input">
          <span class="icone-campo">📊</span>
          <select name="tipo" id="editar-tipo">
            <option value="saida"><?= traduz('fin_tipo_saida') ?></option>
            <option value="entrada"><?= traduz('fin_tipo_entrada') ?></option>
          </select>
        </div>
      </div>
      <div class="modal-campo">
        <label><?= traduz('fin_campo_valor') ?></label>
        <div class="modal-input">
          <span class="icone-campo">$</span>
          <input type="number" name="valor" id="editar-valor" step="0.01" min="0.01" required />
        </div>
      </div>
      <div class="modal-campo">
        <label><?= traduz('fin_campo_descricao') ?></label>
        <div class="modal-input">
          <span class="icone-campo">✏️</span>
          <input type="text" name="descricao" id="editar-descricao" required />
        </div>
      </div>
      <div class="modal-campo">
        <label><?= traduz('fin_campo_categoria') ?></label>
        <div class="modal-input">
          <span class="icone-campo">🏷️</span>
          <select name="categoria" id="editar-categoria">
            <?php foreach ($categorias as $key => $info): ?>
            <option value="<?= $key ?>"><?= $info['emoji'] ?> <?= traduz('fin_cat_' . $key) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-campo">
        <label><?= traduz('fin_campo_data') ?></label>
        <div class="modal-input">
          <span class="icone-campo">📅</span>
          <input type="date" name="data_transacao" id="editar-data" required />
        </div>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="submit" class="botao-primario-grande" style="flex:1;"><?= traduz('fin_botao_salvar') ?></button>
      </div>
    </form>
    <form method="post" action="financas.php?<?= http_build_query(['mes' => $mes_atual, 'ano' => $ano_atual]) ?>" style="margin-top:8px;" onsubmit="return confirm('<?= traduz('fin_confirmar_deletar') ?>')">
      <input type="hidden" name="acao" value="deletar" />
      <input type="hidden" name="id_transacao" id="editar-id-deletar" value="" />
      <button type="submit" class="botao-contorno-grande"><?= traduz('fin_botao_deletar') ?></button>
    </form>
  </div>
</div>

<!-- JS data for charts -->
<script>
window.finDonutData = <?= json_encode(array_map(function ($sc) use ($categorias) {
    $info = $categorias[$sc['categoria']] ?? $categorias['outros'];
    return ['valor' => (float) $sc['total'], 'cor' => $info['cor']];
}, $saidas_cat)) ?>;

window.finBarData = <?= json_encode(array_map(function ($m) {
    return [
        'mes' => traduz('fin_mes_curto_' . $m['mes']),
        'entradas' => $m['entradas'],
        'saidas' => $m['saidas'],
    ];
}, $historico_6m)) ?>;

window.finBarLabelEntradas = <?= json_encode(traduz('fin_ingresos')) ?>;
window.finBarLabelSaidas = <?= json_encode(traduz('fin_gastos')) ?>;
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="../assets/js/financas.js"></script>
<script src="../assets/js/mascote.js"></script>
<script>
// Sync editar-id to delete form
$(document).on('click', '.fin-item[data-transacao]', function () {
    var t = $(this).data('transacao');
    $('#editar-id-deletar').val(t.id_transacao);
});
</script>
</body>
</html>
