<?php

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';

function geraCodigoAfiliadoUnico($nome) {
    $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($nome, 0, 8)));
    if ($base === '') {
        $base = 'AFF';
    }
    do {
        $codigo = $base . rand(100, 999);
    } while (buscaAfiliadoPorCodigo($codigo));
    return $codigo;
}

function insereAfiliado($dados) {
    $pdo = conexao();
    $codigo = $dados['codigo'] ?? geraCodigoAfiliadoUnico($dados['nome']);
    $stmt = $pdo->prepare('INSERT INTO afiliados (nome, email, senha_hash, codigo, comissao_percentual) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $dados['nome'],
        $dados['email'],
        password_hash($dados['senha'], PASSWORD_DEFAULT),
        $codigo,
        $dados['comissao_percentual'] ?? 10.00,
    ]);
    return (int) $pdo->lastInsertId();
}

function buscaAfiliadoPorCodigo($codigo) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM afiliados WHERE codigo = ? AND ativo = 1');
    $stmt->execute([$codigo]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscaAfiliadoPorEmail($email) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM afiliados WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscaAfiliadoPorId($id_afiliado) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM afiliados WHERE id_afiliado = ?');
    $stmt->execute([$id_afiliado]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function listaAfiliados() {
    $pdo = conexao();
    $stmt = $pdo->query('SELECT * FROM afiliados ORDER BY criado_em DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function geraCookieAfiliadoAssinado($codigo) {
    $assinatura = hash_hmac('sha256', $codigo, AFFILIATE_SECRET);
    return $codigo . '|' . $assinatura;
}

function validaCookieAfiliadoAssinado($valor_cookie) {
    $partes = explode('|', (string) $valor_cookie, 2);
    if (count($partes) !== 2) {
        return false;
    }
    [$codigo, $assinatura] = $partes;
    $assinatura_esperada = hash_hmac('sha256', $codigo, AFFILIATE_SECRET);
    if (!hash_equals($assinatura_esperada, $assinatura)) {
        return false;
    }
    return $codigo;
}

function insereIndicacaoAfiliado($id_afiliado, $id_usuario_indicado) {
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT IGNORE INTO afiliados_indicacoes (id_afiliado, id_usuario_indicado) VALUES (?, ?)');
    $stmt->execute([$id_afiliado, $id_usuario_indicado]);
}

function buscaIndicacaoPorUsuario($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM afiliados_indicacoes WHERE id_usuario_indicado = ?');
    $stmt->execute([$id_usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function registraComissaoAfiliado($id_indicacao, $id_pagamento, $valor) {
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT IGNORE INTO afiliados_comissoes (id_indicacao, id_pagamento, valor) VALUES (?, ?, ?)');
    $stmt->execute([$id_indicacao, $id_pagamento, $valor]);
}

function contaIndicacoesPorAfiliado($id_afiliado) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM afiliados_indicacoes WHERE id_afiliado = ?');
    $stmt->execute([$id_afiliado]);
    return (int) $stmt->fetchColumn();
}

function somaComissoesPorAfiliado($id_afiliado) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(c.valor), 0) FROM afiliados_comissoes c
         INNER JOIN afiliados_indicacoes i ON i.id_indicacao = c.id_indicacao
         WHERE i.id_afiliado = ?'
    );
    $stmt->execute([$id_afiliado]);
    return (float) $stmt->fetchColumn();
}

function listaUltimasComissoesAfiliado($id_afiliado, $limite = 20) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        'SELECT c.* FROM afiliados_comissoes c
         INNER JOIN afiliados_indicacoes i ON i.id_indicacao = c.id_indicacao
         WHERE i.id_afiliado = ?
         ORDER BY c.criado_em DESC
         LIMIT ' . (int) $limite
    );
    $stmt->execute([$id_afiliado]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listaComissoesAfiliado($id_afiliado, $pagina = 1, $por_pagina = 20) {
    $pdo = conexao();

    $stmt_total = $pdo->prepare(
        'SELECT COUNT(*) FROM afiliados_comissoes c
         INNER JOIN afiliados_indicacoes i ON i.id_indicacao = c.id_indicacao
         WHERE i.id_afiliado = ?'
    );
    $stmt_total->execute([$id_afiliado]);
    $total = (int) $stmt_total->fetchColumn();

    $pagina = max(1, (int) $pagina);
    $offset = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->prepare(
        'SELECT c.* FROM afiliados_comissoes c
         INNER JOIN afiliados_indicacoes i ON i.id_indicacao = c.id_indicacao
         WHERE i.id_afiliado = ?
         ORDER BY c.criado_em DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->bindValue(1, $id_afiliado);
    $stmt->bindValue(2, $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'comissoes' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'pagina' => $pagina,
        'total_paginas' => (int) ceil($total / $por_pagina),
    ];
}

function comissoesPorMes($id_afiliado, $meses = 6) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT DATE_FORMAT(c.criado_em, '%Y-%m') AS mes, SUM(c.valor) AS total
         FROM afiliados_comissoes c
         INNER JOIN afiliados_indicacoes i ON i.id_indicacao = c.id_indicacao
         WHERE i.id_afiliado = ? AND c.criado_em >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
         GROUP BY mes"
    );
    $stmt->execute([$id_afiliado, $meses]);
    $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return montaSerieMensal($linhas, $meses);
}

function indicacoesPorMes($id_afiliado, $meses = 6) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes, COUNT(*) AS total
         FROM afiliados_indicacoes
         WHERE id_afiliado = ? AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
         GROUP BY mes"
    );
    $stmt->execute([$id_afiliado, $meses]);
    $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return montaSerieMensal($linhas, $meses);
}

function montaSerieMensal($linhas, $meses) {
    $resultado = [];
    for ($i = $meses - 1; $i >= 0; $i--) {
        $mes = date('Y-m', strtotime("-{$i} months"));
        $total = 0;
        foreach ($linhas as $l) {
            if ($l['mes'] === $mes) { $total = (float) $l['total']; break; }
        }
        $resultado[] = [
            'mes' => date('M', strtotime($mes . '-01')),
            'total' => $total,
        ];
    }
    return $resultado;
}

function registraCliqueAfiliado($id_afiliado) {
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO afiliados_cliques (id_afiliado) VALUES (?)');
    $stmt->execute([$id_afiliado]);
}

function contaCliquesPorAfiliado($id_afiliado) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM afiliados_cliques WHERE id_afiliado = ?');
    $stmt->execute([$id_afiliado]);
    return (int) $stmt->fetchColumn();
}

function saldoDisponivelAfiliado($id_afiliado) {
    $total_comissoes = somaComissoesPorAfiliado($id_afiliado);

    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(valor), 0) FROM afiliados_saques
         WHERE id_afiliado = ? AND status IN ('pendente', 'pago')"
    );
    $stmt->execute([$id_afiliado]);
    $total_reservado = (float) $stmt->fetchColumn();

    return round($total_comissoes - $total_reservado, 2);
}

function insereSaqueAfiliado($id_afiliado, $valor, $email_paypal) {
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO afiliados_saques (id_afiliado, valor, email_paypal) VALUES (?, ?, ?)');
    $stmt->execute([$id_afiliado, $valor, $email_paypal]);
    return (int) $pdo->lastInsertId();
}

function listaSaquesPorAfiliado($id_afiliado) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM afiliados_saques WHERE id_afiliado = ? ORDER BY criado_em DESC');
    $stmt->execute([$id_afiliado]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listaTodosSaques($filtro = []) {
    $pdo = conexao();
    $sql = 'SELECT s.*, a.nome AS nome_afiliado, a.email AS email_afiliado
            FROM afiliados_saques s
            INNER JOIN afiliados a ON a.id_afiliado = s.id_afiliado';
    $params = [];

    if (!empty($filtro['status'])) {
        $sql .= ' WHERE s.status = ?';
        $params[] = $filtro['status'];
    }

    $sql .= ' ORDER BY s.criado_em DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscaSaquePorId($id_saque) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM afiliados_saques WHERE id_saque = ?');
    $stmt->execute([$id_saque]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function atualizaStatusSaque($id_saque, $status) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE afiliados_saques SET status = ? WHERE id_saque = ?');
    $stmt->execute([$status, $id_saque]);
}
