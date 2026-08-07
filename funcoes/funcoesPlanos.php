<?php

require_once __DIR__ . '/../config/conexao.php';

function garanteTabelaPlanos() {
    static $criada = false;
    if ($criada) return;
    $pdo = conexao();
    $pdo->exec("CREATE TABLE IF NOT EXISTS planos (
        id_plano INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(120) NOT NULL,
        descricao VARCHAR(255) NULL,
        ciclo ENUM('mensal', 'trimestral', 'anual') NOT NULL DEFAULT 'mensal',
        preco DECIMAL(10,2) NOT NULL DEFAULT 0,
        dias_teste INT NOT NULL DEFAULT 0,
        etiqueta_texto VARCHAR(100) NULL,
        etiqueta_cor VARCHAR(20) NOT NULL DEFAULT 'amarelo',
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $criada = true;
}

function inserePlano($dados) {
    garanteTabelaPlanos();
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO planos (nome, descricao, ciclo, preco, dias_teste, etiqueta_texto, etiqueta_cor, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $dados['nome'],
        $dados['descricao'] ?: null,
        $dados['ciclo'],
        $dados['preco'],
        (int) $dados['dias_teste'],
        $dados['etiqueta_texto'] ?: null,
        $dados['etiqueta_cor'] ?? 'amarelo',
        isset($dados['ativo']) ? (int) $dados['ativo'] : 1,
    ]);
    return (int) $pdo->lastInsertId();
}

function atualizaPlano($id_plano, $dados) {
    garanteTabelaPlanos();
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE planos SET nome = ?, descricao = ?, ciclo = ?, preco = ?, dias_teste = ?, etiqueta_texto = ?, etiqueta_cor = ? WHERE id_plano = ?');
    $stmt->execute([
        $dados['nome'],
        $dados['descricao'] ?: null,
        $dados['ciclo'],
        $dados['preco'],
        (int) $dados['dias_teste'],
        $dados['etiqueta_texto'] ?: null,
        $dados['etiqueta_cor'] ?? 'amarelo',
        $id_plano,
    ]);
}

function atualizaAtivoPlano($id_plano, $ativo) {
    garanteTabelaPlanos();
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE planos SET ativo = ? WHERE id_plano = ?');
    $stmt->execute([$ativo ? 1 : 0, $id_plano]);
}

function deletaPlano($id_plano) {
    garanteTabelaPlanos();
    $pdo = conexao();
    $stmt = $pdo->prepare('DELETE FROM planos WHERE id_plano = ?');
    $stmt->execute([$id_plano]);
}

function buscaPlanoPorId($id_plano) {
    garanteTabelaPlanos();
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM planos WHERE id_plano = ?');
    $stmt->execute([$id_plano]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function listaPlanos($filtro = []) {
    garanteTabelaPlanos();
    $pdo = conexao();
    $sql = 'SELECT * FROM planos';
    $params = [];
    $condicoes = [];

    if (isset($filtro['ativo'])) {
        $condicoes[] = 'ativo = ?';
        $params[] = (int) $filtro['ativo'];
    }
    if (!empty($filtro['ciclo'])) {
        $condicoes[] = 'ciclo = ?';
        $params[] = $filtro['ciclo'];
    }

    if ($condicoes) {
        $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    }
    $sql .= ' ORDER BY criado_em DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscaPlanoSugerido() {
    garanteTabelaPlanos();
    $pdo = conexao();
    $stmt = $pdo->query("SELECT * FROM planos WHERE ativo = 1 ORDER BY FIELD(ciclo, 'trimestral', 'mensal', 'anual'), preco ASC LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function garanteTabelaPagamentos() {
    static $criada = false;
    if ($criada) return;
    $pdo = conexao();
    $pdo->exec("CREATE TABLE IF NOT EXISTS pagamentos (
        id_pagamento INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        id_plano INT NULL,
        ciclo ENUM('mensal', 'trimestral', 'anual') NOT NULL DEFAULT 'mensal',
        valor DECIMAL(10,2) NOT NULL,
        stripe_invoice_id VARCHAR(255) NULL UNIQUE,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    )");
    $criada = true;
}

function registraPagamento($dados) {
    garanteTabelaPagamentos();
    $pdo = conexao();
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO pagamentos (id_usuario, id_plano, ciclo, valor, stripe_invoice_id, criado_em) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $dados['id_usuario'],
        $dados['id_plano'] ?: null,
        $dados['ciclo'] ?? 'mensal',
        $dados['valor'],
        $dados['stripe_invoice_id'] ?? null,
        $dados['criado_em'] ?? date('Y-m-d H:i:s'),
    ]);
    return (int) $pdo->lastInsertId();
}

function calculaReceitaMensal() {
    garanteTabelaPagamentos();
    $pdo = conexao();
    $stmt = $pdo->query(
        "SELECT COALESCE(SUM(
            CASE ultimo.ciclo
                WHEN 'mensal' THEN ultimo.valor
                WHEN 'trimestral' THEN ultimo.valor / 3
                WHEN 'anual' THEN ultimo.valor / 12
            END
        ), 0) AS mrr
        FROM usuarios u
        JOIN (
            SELECT p1.id_usuario, p1.valor, p1.ciclo
            FROM pagamentos p1
            INNER JOIN (
                SELECT id_usuario, MAX(criado_em) AS ultimo_em FROM pagamentos GROUP BY id_usuario
            ) p2 ON p2.id_usuario = p1.id_usuario AND p2.ultimo_em = p1.criado_em
        ) ultimo ON ultimo.id_usuario = u.id_usuario
        WHERE u.deletado = 0 AND u.plano = 'ativo'"
    );
    return (float) $stmt->fetchColumn();
}

function calculaReceitaTotal() {
    garanteTabelaPagamentos();
    $pdo = conexao();
    return (float) $pdo->query('SELECT COALESCE(SUM(valor), 0) FROM pagamentos')->fetchColumn();
}

function receitaPorMes($meses = 6) {
    garanteTabelaPagamentos();
    $pdo = conexao();

    $stmt = $pdo->prepare(
        "SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes, SUM(valor) AS total
         FROM pagamentos
         WHERE criado_em >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
         GROUP BY mes ORDER BY mes ASC"
    );
    $stmt->execute([$meses]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado = [];
    for ($i = $meses - 1; $i >= 0; $i--) {
        $mes = date('Y-m', strtotime("-{$i} months"));
        $total = 0;
        foreach ($rows as $r) {
            if ($r['mes'] === $mes) { $total = (float)$r['total']; break; }
        }
        $resultado[] = [
            'mes' => date('M', strtotime($mes . '-01')),
            'receita' => round($total, 2),
        ];
    }
    return $resultado;
}

function contaPlanos() {
    garanteTabelaPlanos();
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM planos')->fetchColumn();
}
