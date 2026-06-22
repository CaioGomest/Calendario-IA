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
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $criada = true;
}

function inserePlano($dados) {
    garanteTabelaPlanos();
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO planos (nome, descricao, ciclo, preco, dias_teste, ativo) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $dados['nome'],
        $dados['descricao'] ?: null,
        $dados['ciclo'],
        $dados['preco'],
        (int) $dados['dias_teste'],
        isset($dados['ativo']) ? (int) $dados['ativo'] : 1,
    ]);
    return (int) $pdo->lastInsertId();
}

function atualizaPlano($id_plano, $dados) {
    garanteTabelaPlanos();
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE planos SET nome = ?, descricao = ?, ciclo = ?, preco = ?, dias_teste = ? WHERE id_plano = ?');
    $stmt->execute([
        $dados['nome'],
        $dados['descricao'] ?: null,
        $dados['ciclo'],
        $dados['preco'],
        (int) $dados['dias_teste'],
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

function contaPlanos() {
    garanteTabelaPlanos();
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM planos')->fetchColumn();
}
