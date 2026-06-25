<?php

require_once __DIR__ . '/../config/conexao.php';

function buscaUsuarioPorEmail($email) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscaUsuarioPorTelefone($telefone) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE telefone = ? AND deletado = 0');
    $stmt->execute([$telefone]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscaUsuarioPorId($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function insereUsuario($dados) {
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)');
    $stmt->execute([
        $dados['nome'],
        $dados['email'],
        password_hash($dados['senha'], PASSWORD_DEFAULT),
    ]);
    return (int) $pdo->lastInsertId();
}

function atualizaPlanoUsuario($id_usuario, $plano, $plano_expira_em) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET plano = ?, plano_expira_em = ? WHERE id_usuario = ?');
    $stmt->execute([$plano, $plano_expira_em, $id_usuario]);
}

function atualizaTokensGoogle($id_usuario, $token_acesso, $token_refresh, $token_expira_em) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET token_acesso_google = ?, token_refresh_google = ?, token_google_expira_em = ? WHERE id_usuario = ?');
    $stmt->execute([$token_acesso, $token_refresh, $token_expira_em, $id_usuario]);
}

function atualizaTelefoneUsuario($id_usuario, $telefone) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET telefone = ? WHERE id_usuario = ?');
    $stmt->execute([$telefone, $id_usuario]);
}

function atualizaModoSilencio($id_usuario, $modo_silencio) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET modo_silencio = ? WHERE id_usuario = ?');
    $stmt->execute([$modo_silencio ? 1 : 0, $id_usuario]);
}

function atualizaAntecedenciaLembrete($id_usuario, $antecedencia_min) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET antecedencia_lembrete_min = ? WHERE id_usuario = ?');
    $stmt->execute([$antecedencia_min, $id_usuario]);
}

function listaUsuarios($filtro = []) {
    $pdo = conexao();
    $sql = 'SELECT * FROM usuarios';
    $params = [];
    $condicoes = ['deletado = 0'];

    if (!empty($filtro['plano'])) {
        $condicoes[] = 'plano = ?';
        $params[] = $filtro['plano'];
    }
    if (isset($filtro['ativo'])) {
        $condicoes[] = 'ativo = ?';
        $params[] = (int) $filtro['ativo'];
    }
    if (!empty($filtro['busca'])) {
        $condicoes[] = '(nome LIKE ? OR email LIKE ? OR telefone LIKE ?)';
        $termo = '%' . $filtro['busca'] . '%';
        $params[] = $termo;
        $params[] = $termo;
        $params[] = $termo;
    }

    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    $sql .= ' ORDER BY criado_em DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deletaUsuario($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET deletado = 1 WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
}

function contaUsuariosPorPlano() {
    $pdo = conexao();
    $stmt = $pdo->query('SELECT plano, COUNT(*) as total FROM usuarios WHERE deletado = 0 GROUP BY plano');
    $resultado = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resultado[$row['plano']] = (int) $row['total'];
    }
    return $resultado;
}

function contaTotalUsuarios() {
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE deletado = 0')->fetchColumn();
}

function contaUsuariosAtivos() {
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE ativo = 1 AND deletado = 0')->fetchColumn();
}

function atualizaAtivoUsuario($id_usuario, $ativo) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET ativo = ? WHERE id_usuario = ?');
    $stmt->execute([$ativo ? 1 : 0, $id_usuario]);
}

function atualizaUsuario($id_usuario, $dados) {
    $pdo = conexao();
    $campos = [];
    $params = [];

    foreach (['nome', 'email', 'telefone', 'plano', 'fuso_horario'] as $campo) {
        if (array_key_exists($campo, $dados)) {
            $campos[] = "$campo = ?";
            $params[] = $dados[$campo];
        }
    }

    if (isset($dados['plano_expira_em'])) {
        $campos[] = 'plano_expira_em = ?';
        $params[] = $dados['plano_expira_em'] ?: null;
    }

    if (empty($campos)) return;

    $params[] = $id_usuario;
    $sql = 'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id_usuario = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function contaUsuariosNovosEsteMes() {
    $pdo = conexao();
    return (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE deletado = 0 AND criado_em >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
}

function contaUsuariosNovosEstaSemana() {
    $pdo = conexao();
    return (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE deletado = 0 AND criado_em >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)")->fetchColumn();
}

function contaUsuariosPorDia($dias = 30) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT DATE(criado_em) AS dia, COUNT(*) AS total
         FROM usuarios WHERE deletado = 0 AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(criado_em) ORDER BY dia ASC"
    );
    $stmt->execute([$dias]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contaUsuariosPorPlanoDetalhado() {
    $pdo = conexao();
    $stmt = $pdo->query(
        "SELECT plano, ativo, COUNT(*) AS total FROM usuarios WHERE deletado = 0 GROUP BY plano, ativo"
    );
    $resultado = ['trial' => 0, 'ativo' => 0, 'cancelado' => 0, 'inativos' => 0];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!(int)$row['ativo']) {
            $resultado['inativos'] += (int)$row['total'];
        } else {
            $resultado[$row['plano']] = (int)$row['total'];
        }
    }
    return $resultado;
}

function atualizaStripeUsuario($id_usuario, $stripe_customer_id, $stripe_subscription_id) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET stripe_customer_id = ?, stripe_subscription_id = ? WHERE id_usuario = ?');
    $stmt->execute([$stripe_customer_id, $stripe_subscription_id, $id_usuario]);
}

function buscaUsuarioPorStripeCustomer($stripe_customer_id) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE stripe_customer_id = ? AND deletado = 0');
    $stmt->execute([$stripe_customer_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function contaCancelamentosPorDia($dias = 30) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT DATE(criado_em) AS dia, COUNT(*) AS total
         FROM usuarios WHERE plano = 'cancelado' AND deletado = 0
         AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(criado_em) ORDER BY dia ASC"
    );
    $stmt->execute([$dias]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contaCancelados() {
    $pdo = conexao();
    return (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE plano = 'cancelado' AND deletado = 0")->fetchColumn();
}

function contaCanceladosEsteMes() {
    $pdo = conexao();
    return (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE plano = 'cancelado' AND deletado = 0 AND criado_em >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
}

function criaTokenRecuperacao($id_usuario) {
    $pdo = conexao();
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare('UPDATE usuarios SET token_recuperacao = ?, token_recuperacao_expira = ? WHERE id_usuario = ?');
    $stmt->execute([$token, $expira, $id_usuario]);
    return $token;
}

function buscaUsuarioPorTokenRecuperacao($token) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE token_recuperacao = ? AND token_recuperacao_expira > NOW() AND deletado = 0');
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function deletaTokenRecuperacao($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET token_recuperacao = NULL, token_recuperacao_expira = NULL WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
}

function atualizaSenhaUsuario($id_usuario, $nova_senha) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id_usuario = ?');
    $stmt->execute([password_hash($nova_senha, PASSWORD_DEFAULT), $id_usuario]);
}

function insereUsuarioGoogle($nome, $email) {
    $pdo = conexao();
    $senha_random = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)');
    $stmt->execute([$nome, $email, $senha_random]);
    return (int) $pdo->lastInsertId();
}

function insereUsuarioAdmin($dados) {
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, telefone, plano) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $dados['nome'],
        $dados['email'],
        password_hash($dados['senha'], PASSWORD_DEFAULT),
        $dados['telefone'] ?: null,
        $dados['plano'] ?? 'trial',
    ]);
    return (int) $pdo->lastInsertId();
}
