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

function garanteTokenGoogleValido($id_usuario) {
    $tokens = buscaTokensGoogle($id_usuario);
    if (!$tokens || !$tokens['token_acesso_google']) return null;

    $expira = new DateTime($tokens['token_google_expira_em']);
    $agora = new DateTime();

    if ($expira > $agora) {
        return $tokens['token_acesso_google'];
    }

    if (!$tokens['token_refresh_google']) return null;

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'refresh_token' => $tokens['token_refresh_google'],
            'grant_type' => 'refresh_token',
        ]),
        CURLOPT_TIMEOUT => 15,
    ]);
    $resposta = json_decode(curl_exec($ch), true) ?: [];
    curl_close($ch);

    if (empty($resposta['access_token'])) return null;

    $nova_expiracao = date('Y-m-d H:i:s', time() + $resposta['expires_in']);
    renovaTokenGoogle($id_usuario, $resposta['access_token'], $nova_expiracao);

    return $resposta['access_token'];
}

function listaEventosGoogleCalendar($token_acesso, $fuso_horario = 'America/Mexico_City', $limite = 10) {
    $agora = (new DateTime('now', new DateTimeZone($fuso_horario)))->format('c');
    $params = http_build_query([
        'timeMin' => $agora,
        'maxResults' => $limite,
        'singleEvents' => 'true',
        'orderBy' => 'startTime',
    ]);

    $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events?' . $params);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token_acesso],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resposta = json_decode(curl_exec($ch), true) ?: [];
    curl_close($ch);

    $eventos = [];
    foreach ($resposta['items'] ?? [] as $item) {
        $inicio = $item['start']['dateTime'] ?? $item['start']['date'] ?? null;
        $fim = $item['end']['dateTime'] ?? $item['end']['date'] ?? null;
        $eventos[] = [
            'id_google_event' => $item['id'],
            'titulo' => $item['summary'] ?? '(Sin título)',
            'descricao' => $item['description'] ?? null,
            'data_inicio' => $inicio,
            'data_fim' => $fim,
        ];
    }

    return $eventos;
}
