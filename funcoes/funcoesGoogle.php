<?php

require_once __DIR__ . '/../config/conexao.php';

function buscaTokensGoogle($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT token_acesso_google, token_refresh_google, token_google_expira_em FROM usuarios WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function renovaTokenGoogle($id_usuario, $token_acesso, $token_expira_em) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET token_acesso_google = ?, token_google_expira_em = ? WHERE id_usuario = ?');
    $stmt->execute([$token_acesso, $token_expira_em, $id_usuario]);
}
