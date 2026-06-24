<?php

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/config.php';

function geraUrlAutorizacaoGoogle() {
    $params = http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile https://www.googleapis.com/auth/calendar',
        'access_type' => 'offline',
        'prompt' => 'consent',
    ]);
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
}

function trocaCodigoGooglePorTokens($code) {
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'grant_type' => 'authorization_code',
        ]),
        CURLOPT_TIMEOUT => 15,
    ]);
    $resposta = curl_exec($ch);
    curl_close($ch);
    return json_decode($resposta, true) ?: [];
}

function buscaPerfilGoogle($access_token) {
    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $access_token],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resposta = curl_exec($ch);
    curl_close($ch);
    return json_decode($resposta, true) ?: [];
}

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
