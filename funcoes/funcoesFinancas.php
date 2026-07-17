<?php

require_once __DIR__ . '/../config/conexao.php';

function categoriasValidas() {
    static $cache = null;
    if ($cache !== null) return $cache;

    $pdo = conexao();
    $stmt = $pdo->query('SELECT chave, emoji, cor FROM categorias WHERE ativo = 1 ORDER BY ordem ASC');
    $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cache = [];
    foreach ($linhas as $linha) {
        $cache[$linha['chave']] = ['emoji' => $linha['emoji'], 'cor' => $linha['cor']];
    }

    if (empty($cache)) {
        $cache = [
            'alimentacao'    => ['emoji' => '🍔', 'cor' => '#F59E0B'],
            'transporte'     => ['emoji' => '🚗', 'cor' => '#3B82F6'],
            'entretenimento' => ['emoji' => '🎬', 'cor' => '#8B5CF6'],
            'servicos'       => ['emoji' => '⚡', 'cor' => '#EF4444'],
            'saude'          => ['emoji' => '💊', 'cor' => '#10B981'],
            'educacao'       => ['emoji' => '📚', 'cor' => '#6366F1'],
            'moradia'        => ['emoji' => '🏠', 'cor' => '#F97316'],
            'compras'        => ['emoji' => '🛍️', 'cor' => '#EC4899'],
            'entrada'        => ['emoji' => '💰', 'cor' => '#22C55E'],
            'outros'         => ['emoji' => '📦', 'cor' => '#94A3B8'],
        ];
    }

    return $cache;
}

function validaCategoria($categoria) {
    return array_key_exists($categoria, categoriasValidas());
}

function insereTransacao($dados) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        'INSERT INTO transacoes (id_usuario, tipo, valor, descricao, categoria, data_transacao)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $categoria = $dados['categoria'] ?? 'outros';
    if ($dados['tipo'] === 'entrada') {
        $categoria = 'entrada';
    }
    if (!validaCategoria($categoria)) {
        $categoria = 'outros';
    }
    $stmt->execute([
        $dados['id_usuario'],
        $dados['tipo'],
        $dados['valor'],
        $dados['descricao'],
        $categoria,
        $dados['data_transacao'],
    ]);
    return (int) $pdo->lastInsertId();
}

function atualizaTransacao($id_transacao, $dados) {
    $pdo = conexao();
    $campos = [];
    $params = [];

    $permitidos = ['tipo', 'valor', 'descricao', 'categoria', 'data_transacao'];
    foreach ($permitidos as $campo) {
        if (array_key_exists($campo, $dados)) {
            $campos[] = "$campo = ?";
            $params[] = $dados[$campo];
        }
    }

    if (empty($campos)) {
        return false;
    }

    $params[] = $id_transacao;
    $stmt = $pdo->prepare('UPDATE transacoes SET ' . implode(', ', $campos) . ' WHERE id_transacao = ?');
    $stmt->execute($params);
    return $stmt->rowCount() > 0;
}

function deletaTransacao($id_transacao) {
    $pdo = conexao();
    $stmt = $pdo->prepare('DELETE FROM transacoes WHERE id_transacao = ?');
    $stmt->execute([$id_transacao]);
    return $stmt->rowCount() > 0;
}

function buscaTransacaoPorId($id_transacao) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM transacoes WHERE id_transacao = ?');
    $stmt->execute([$id_transacao]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function listaTransacoes($id_usuario, $filtro = []) {
    $pdo = conexao();
    $where = 'WHERE id_usuario = ?';
    $params = [$id_usuario];

    if (!empty($filtro['mes']) && !empty($filtro['ano'])) {
        $where .= ' AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?';
        $params[] = (int) $filtro['mes'];
        $params[] = (int) $filtro['ano'];
    }
    if (!empty($filtro['tipo'])) {
        $where .= ' AND tipo = ?';
        $params[] = $filtro['tipo'];
    }
    if (!empty($filtro['categoria'])) {
        $where .= ' AND categoria = ?';
        $params[] = $filtro['categoria'];
    }
    if (!empty($filtro['busca'])) {
        $where .= ' AND descricao LIKE ?';
        $params[] = '%' . $filtro['busca'] . '%';
    }

    $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM transacoes $where");
    $stmt_total->execute($params);
    $total = (int) $stmt_total->fetchColumn();

    $por_pagina = (int) ($filtro['por_pagina'] ?? 10);
    $pagina = max(1, (int) ($filtro['pagina'] ?? 1));
    $offset = ($pagina - 1) * $por_pagina;

    $sql = "SELECT * FROM transacoes $where ORDER BY data_transacao DESC, criado_em DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $i => $val) {
        $stmt->bindValue($i + 1, $val);
    }
    $stmt->bindValue(count($params) + 1, $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'transacoes' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'pagina' => $pagina,
        'total_paginas' => (int) ceil($total / $por_pagina),
    ];
}

function resumoMensal($id_usuario, $mes, $ano) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT tipo, SUM(valor) AS total
         FROM transacoes
         WHERE id_usuario = ? AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
         GROUP BY tipo"
    );
    $stmt->execute([$id_usuario, $mes, $ano]);
    $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_entradas = 0;
    $total_saidas = 0;
    foreach ($linhas as $linha) {
        if ($linha['tipo'] === 'entrada') $total_entradas = (float) $linha['total'];
        if ($linha['tipo'] === 'saida') $total_saidas = (float) $linha['total'];
    }

    return [
        'total_entradas' => $total_entradas,
        'total_saidas' => $total_saidas,
        'saldo' => $total_entradas - $total_saidas,
    ];
}

function saidasPorCategoria($id_usuario, $mes, $ano) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT categoria, SUM(valor) AS total
         FROM transacoes
         WHERE id_usuario = ? AND tipo = 'saida' AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
         GROUP BY categoria
         ORDER BY total DESC"
    );
    $stmt->execute([$id_usuario, $mes, $ano]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function resumoUltimos6Meses($id_usuario, $mes, $ano) {
    $pdo = conexao();
    $meses = [];
    for ($i = 5; $i >= 0; $i--) {
        $dt = new DateTime("$ano-$mes-01");
        $dt->modify("-$i months");
        $meses[] = [
            'mes' => (int) $dt->format('n'),
            'ano' => (int) $dt->format('Y'),
        ];
    }

    $resultado = [];
    $stmt = $pdo->prepare(
        "SELECT tipo, SUM(valor) AS total
         FROM transacoes
         WHERE id_usuario = ? AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
         GROUP BY tipo"
    );

    foreach ($meses as $m) {
        $stmt->execute([$id_usuario, $m['mes'], $m['ano']]);
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $entradas = 0;
        $saidas = 0;
        foreach ($linhas as $linha) {
            if ($linha['tipo'] === 'entrada') $entradas = (float) $linha['total'];
            if ($linha['tipo'] === 'saida') $saidas = (float) $linha['total'];
        }
        $resultado[] = [
            'mes' => $m['mes'],
            'ano' => $m['ano'],
            'entradas' => $entradas,
            'saidas' => $saidas,
        ];
    }

    return $resultado;
}
