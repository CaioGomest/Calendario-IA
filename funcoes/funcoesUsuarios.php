<?php

require_once __DIR__ . '/../config/conexao.php';

function buscaUsuarioPorEmail($email) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
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
