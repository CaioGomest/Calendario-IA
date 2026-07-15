<?php
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])) {
    http_response_code(403);
    exit('Acesso negado.');
}

require_once __DIR__ . '/../config/conexao.php';

$resultados = ['ok' => 0, 'falha' => 0, 'saida' => []];

function testar($grupo, $descricao, $callback) {
    global $resultados;
    try {
        $callback();
        $resultados['ok']++;
        $resultados['saida'][] = ['ok' => true, 'grupo' => $grupo, 'desc' => $descricao];
    } catch (Throwable $e) {
        $resultados['falha']++;
        $resultados['saida'][] = ['ok' => false, 'grupo' => $grupo, 'desc' => $descricao, 'erro' => $e->getMessage()];
    }
}

function espera($obtido, $esperado) {
    if ($obtido !== $esperado) {
        throw new Exception('esperado ' . var_export($esperado, true) . ', obtido ' . var_export($obtido, true));
    }
}

function esperaVerdadeiro($valor) {
    if (!$valor) {
        throw new Exception('esperava verdadeiro, obteve ' . var_export($valor, true));
    }
}

function esperaNulo($valor) {
    if ($valor !== null) {
        throw new Exception('esperava null, obteve ' . var_export($valor, true));
    }
}

foreach (glob(__DIR__ . '/casos/*.php') as $arquivo) {
    require $arquivo;
}

$total = $resultados['ok'] + $resultados['falha'];
$todos_ok = $resultados['falha'] === 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Testes — CalendarioIA</title>
    <style>
        body { font-family: monospace; max-width: 720px; margin: 40px auto; padding: 0 20px; background: #0f172a; color: #e2e8f0; }
        h1 { font-size: 1.1rem; color: #94a3b8; margin-bottom: 24px; }
        .resumo { font-size: 1.5rem; font-weight: bold; margin-bottom: 24px; }
        .resumo.ok { color: #4ade80; }
        .resumo.falha { color: #f87171; }
        .grupo { margin-bottom: 24px; }
        .grupo-titulo { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; }
        .item { padding: 6px 0; border-bottom: 1px solid #1e293b; display: flex; gap: 10px; align-items: flex-start; }
        .item:last-child { border-bottom: none; }
        .icone { flex-shrink: 0; }
        .icone.ok { color: #4ade80; }
        .icone.falha { color: #f87171; }
        .desc { flex: 1; }
        .erro { font-size: 0.8rem; color: #f87171; margin-top: 2px; }
    </style>
</head>
<body>
<h1>CalendarioIA / testes</h1>
<div class="resumo <?= $todos_ok ? 'ok' : 'falha' ?>">
    <?= $resultados['ok'] ?>/<?= $total ?> passou<?= $todos_ok ? ' — tudo ok' : ' — ' . $resultados['falha'] . ' falhou' ?>
</div>

<?php
$grupos = [];
foreach ($resultados['saida'] as $item) {
    $grupos[$item['grupo']][] = $item;
}
foreach ($grupos as $nome_grupo => $itens):
?>
<div class="grupo">
    <div class="grupo-titulo"><?= htmlspecialchars($nome_grupo) ?></div>
    <?php foreach ($itens as $item): ?>
    <div class="item">
        <span class="icone <?= $item['ok'] ? 'ok' : 'falha' ?>"><?= $item['ok'] ? '✓' : '✗' ?></span>
        <div class="desc">
            <?= htmlspecialchars($item['desc']) ?>
            <?php if (!$item['ok']): ?>
            <div class="erro"><?= htmlspecialchars($item['erro']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
</body>
</html>
