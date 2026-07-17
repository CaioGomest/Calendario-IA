<?php

require_once __DIR__ . '/../config/conexao.php';

function validaSecretInterno() {
    $secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    return $secret !== '' && $secret === INTERNAL_SECRET;
}

function iniciaSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function fazLoginCliente($usuario) {
    iniciaSessao();
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nome'] = $usuario['nome'];
    $_SESSION['email'] = $usuario['email'];
}

function fazLogoutCliente() {
    iniciaSessao();
    $_SESSION = [];
    session_destroy();
}

function usuarioLogadoId() {
    iniciaSessao();
    return $_SESSION['id_usuario'] ?? null;
}

function exigeLoginCliente() {
    if (!usuarioLogadoId()) {
        header('Location: login.php');
        exit;
    }
}

function fazLoginAdmin($admin) {
    iniciaSessao();
    $_SESSION['id_admin'] = $admin['id_admin'];
    $_SESSION['nome_admin'] = $admin['nome'];
    $_SESSION['email_admin'] = $admin['email'];
}

function fazLogoutAdmin() {
    iniciaSessao();
    $_SESSION = [];
    session_destroy();
}

function adminLogadoId() {
    iniciaSessao();
    return $_SESSION['id_admin'] ?? null;
}

function exigeLoginAdmin() {
    if (adminLogadoId() === null) {
        header('Location: login.php');
        exit;
    }
}

function buscaAdminPorEmail($email) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM administradores WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscaAdminPorId($id_admin) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM administradores WHERE id_admin = ?');
    $stmt->execute([$id_admin]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
