<?php

require_once __DIR__ . '/../config/conexao.php';

function inserePlano($dados) {
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
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE planos SET ativo = ? WHERE id_plano = ?');
    $stmt->execute([$ativo ? 1 : 0, $id_plano]);
}

function deletaPlano($id_plano) {
    $pdo = conexao();
    $stmt = $pdo->prepare('DELETE FROM planos WHERE id_plano = ?');
    $stmt->execute([$id_plano]);
}

function buscaPlanoPorId($id_plano) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM planos WHERE id_plano = ?');
    $stmt->execute([$id_plano]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function listaPlanos($filtro = []) {
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
    $pdo = conexao();
    $stmt = $pdo->query("SELECT * FROM planos WHERE ativo = 1 ORDER BY FIELD(ciclo, 'trimestral', 'mensal', 'anual'), preco ASC LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function registraPagamento($dados) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO pagamentos (id_usuario, id_plano, ciclo, valor, status, stripe_invoice_id, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $dados['id_usuario'],
        $dados['id_plano'] ?: null,
        $dados['ciclo'] ?? 'mensal',
        $dados['valor'],
        $dados['status'] ?? 'pago',
        $dados['stripe_invoice_id'] ?? null,
        $dados['criado_em'] ?? date('Y-m-d H:i:s'),
    ]);
    return (int) $pdo->lastInsertId();
}

function marcaPagamentoReembolsado($stripe_invoice_id) {
    $pdo = conexao();
    $stmt = $pdo->prepare("UPDATE pagamentos SET status = 'reembolsado' WHERE stripe_invoice_id = ? AND status = 'pago'");
    $stmt->execute([$stripe_invoice_id]);
}

function buscaPagamentoPorInvoice($stripe_invoice_id) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM pagamentos WHERE stripe_invoice_id = ?');
    $stmt->execute([$stripe_invoice_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function ehPagamentoMaisRecentePago($id_usuario, $criado_em) {
    $pdo = conexao();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pagamentos WHERE id_usuario = ? AND status = 'pago' AND criado_em > ?");
    $stmt->execute([$id_usuario, $criado_em]);
    return (int) $stmt->fetchColumn() === 0;
}

function listaPagamentosUsuario($id_usuario, $limite = 20) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM pagamentos WHERE id_usuario = ? ORDER BY criado_em DESC LIMIT ?');
    $stmt->bindValue(1, $id_usuario, PDO::PARAM_INT);
    $stmt->bindValue(2, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function _condicoesPagamentos($filtro) {
    $params = [];
    $condicoes = [];

    if (!empty($filtro['busca'])) {
        $condicoes[] = '(u.nome LIKE ? OR u.email LIKE ?)';
        $termo = '%' . $filtro['busca'] . '%';
        $params[] = $termo;
        $params[] = $termo;
    }
    if (!empty($filtro['id_plano'])) {
        $condicoes[] = 'p.id_plano = ?';
        $params[] = (int) $filtro['id_plano'];
    }
    if (!empty($filtro['id_usuario'])) {
        $condicoes[] = 'p.id_usuario = ?';
        $params[] = (int) $filtro['id_usuario'];
    }
    if (!empty($filtro['data_inicio'])) {
        $condicoes[] = 'DATE(p.criado_em) >= ?';
        $params[] = $filtro['data_inicio'];
    }
    if (!empty($filtro['data_fim'])) {
        $condicoes[] = 'DATE(p.criado_em) <= ?';
        $params[] = $filtro['data_fim'];
    }
    if (!empty($filtro['status'])) {
        $condicoes[] = 'p.status = ?';
        $params[] = $filtro['status'];
    }
    if (!empty($filtro['tipo']) && in_array($filtro['tipo'], ['novas', 'renovacoes'], true)) {
        $comparador = $filtro['tipo'] === 'novas' ? '=' : '>';
        $condicoes[] = "p.status = 'pago' AND p.criado_em $comparador (
            SELECT MIN(p3.criado_em) FROM pagamentos p3 WHERE p3.id_usuario = p.id_usuario AND p3.status = 'pago'
        )";
    }

    return [$condicoes, $params];
}

function listaPagamentos($filtro = [], $pagina = 1, $por_pagina = 30) {
    $pdo = conexao();
    $sql = "SELECT p.*, u.nome AS nome_usuario, u.email AS email_usuario, pl.nome AS nome_plano,
            CASE
                WHEN p.status != 'pago' THEN NULL
                WHEN p.criado_em = (SELECT MIN(p3.criado_em) FROM pagamentos p3 WHERE p3.id_usuario = p.id_usuario AND p3.status = 'pago') THEN 'nova'
                ELSE 'renovacao'
            END AS tipo_pagamento
            FROM pagamentos p
            JOIN usuarios u ON u.id_usuario = p.id_usuario
            LEFT JOIN planos pl ON pl.id_plano = p.id_plano";

    [$condicoes, $params] = _condicoesPagamentos($filtro);
    if ($condicoes) {
        $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    }
    $sql .= ' ORDER BY p.criado_em DESC';

    $pagina = max(1, (int) $pagina);
    $por_pagina = max(1, (int) $por_pagina);
    $sql .= ' LIMIT ' . $por_pagina . ' OFFSET ' . (($pagina - 1) * $por_pagina);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function resumoPagamentos($filtro = []) {
    $pdo = conexao();
    [$condicoes, $params] = _condicoesPagamentos($filtro);

    $sql = "SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN p.status = 'pago' THEN p.valor ELSE 0 END), 0) AS receita
            FROM pagamentos p
            JOIN usuarios u ON u.id_usuario = p.id_usuario";
    if ($condicoes) {
        $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $linha = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'total' => (int) $linha['total'],
        'receita' => (float) $linha['receita'],
    ];
}

function calculaReceitaMensal() {
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
                SELECT id_usuario, MAX(criado_em) AS ultimo_em FROM pagamentos WHERE status = 'pago' GROUP BY id_usuario
            ) p2 ON p2.id_usuario = p1.id_usuario AND p2.ultimo_em = p1.criado_em
            WHERE p1.status = 'pago'
        ) ultimo ON ultimo.id_usuario = u.id_usuario
        WHERE u.deletado = 0 AND u.plano = 'ativo'"
    );
    return (float) $stmt->fetchColumn();
}

function calculaReceitaTotal() {
    $pdo = conexao();
    return (float) $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE status = 'pago'")->fetchColumn();
}

function receitaPorMes($meses = 6) {
    $pdo = conexao();

    $stmt = $pdo->prepare(
        "SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes, SUM(valor) AS total
         FROM pagamentos
         WHERE status = 'pago' AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
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

function contaNovasAssinaturasMes() {
    $pdo = conexao();
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM (
            SELECT id_usuario, MIN(criado_em) AS primeiro_pagamento
            FROM pagamentos WHERE status = 'pago' GROUP BY id_usuario
        ) t WHERE t.primeiro_pagamento >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    )->fetchColumn();
}

function contaRenovacoesMes() {
    $pdo = conexao();
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM pagamentos p
         WHERE p.status = 'pago'
         AND p.criado_em >= DATE_FORMAT(NOW(), '%Y-%m-01')
         AND p.criado_em > (
             SELECT MIN(p2.criado_em) FROM pagamentos p2
             WHERE p2.id_usuario = p.id_usuario AND p2.status = 'pago'
         )"
    )->fetchColumn();
}

function contaFalhasMes() {
    $pdo = conexao();
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM pagamentos WHERE status = 'falhou' AND criado_em >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    )->fetchColumn();
}

function contaPlanos() {
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM planos')->fetchColumn();
}
