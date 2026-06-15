<?php

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
