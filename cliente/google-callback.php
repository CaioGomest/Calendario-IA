<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesGoogle.php';

iniciaSessao();

$code = $_GET['code'] ?? '';

if ($code === '') {
    header('Location: login.php');
    exit;
}

$tokens = trocaCodigoGooglePorTokens($code);

if (empty($tokens['access_token'])) {
    $_SESSION['erro_google'] = traduz('erro_google_login');
    header('Location: login.php');
    exit;
}

$perfil = buscaPerfilGoogle($tokens['access_token']);

if (empty($perfil['email'])) {
    $_SESSION['erro_google'] = traduz('erro_google_login');
    header('Location: login.php');
    exit;
}

$email = $perfil['email'];
$nome = $perfil['name'] ?? $email;
$token_acesso = $tokens['access_token'];
$token_refresh = $tokens['refresh_token'] ?? null;
$token_expira_em = isset($tokens['expires_in'])
    ? date('Y-m-d H:i:s', time() + (int)$tokens['expires_in'])
    : null;

$usuario = buscaUsuarioPorEmail($email);
$novo = false;

if (!$usuario) {
    $id_novo = insereUsuarioGoogle($nome, $email);
    $usuario = buscaUsuarioPorId($id_novo);
    $novo = true;
}

atualizaTokensGoogle(
    (int)$usuario['id_usuario'],
    $token_acesso,
    $token_refresh,
    $token_expira_em
);

fazLoginCliente($usuario);

if ($novo) {
    header('Location: pago.php');
} else {
    header('Location: home.php');
}
exit;
