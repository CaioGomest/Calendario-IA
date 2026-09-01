<?php

require_once __DIR__ . '/../config/conexao.php';

function validaSecretInterno() {
    $secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    return $secret !== '' && $secret === INTERNAL_SECRET;
}

function verificaRecaptcha($token, $acao_esperada, $limite = 0.5) {
    if (MODO_DEV || !RECAPTCHA_SECRET_KEY) return true;
    if (!$token) return false;

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => RECAPTCHA_SECRET_KEY, 'response' => $token]),
        CURLOPT_TIMEOUT => 10,
    ]);
    $dados = json_decode(curl_exec($ch), true) ?: [];
    curl_close($ch);

    return !empty($dados['success'])
        && ($dados['action'] ?? '') === $acao_esperada
        && ($dados['score'] ?? 0) >= $limite;
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
        header('Location: login');
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
        header('Location: login');
        exit;
    }
}

function fazLoginAfiliado($afiliado) {
    iniciaSessao();
    $_SESSION['id_afiliado'] = $afiliado['id_afiliado'];
    $_SESSION['nome_afiliado'] = $afiliado['nome'];
    $_SESSION['email_afiliado'] = $afiliado['email'];
}

function fazLogoutAfiliado() {
    iniciaSessao();
    $_SESSION = [];
    session_destroy();
}

function afiliadoLogadoId() {
    iniciaSessao();
    return $_SESSION['id_afiliado'] ?? null;
}

function exigeLoginAfiliado() {
    if (afiliadoLogadoId() === null) {
        header('Location: login');
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
