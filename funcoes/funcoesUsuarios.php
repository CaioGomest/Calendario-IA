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
