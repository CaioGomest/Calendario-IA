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

function buscaStatusEventoGoogle($token_acesso, $id_google_event) {
    $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events/' . urlencode($id_google_event));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token_acesso],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resposta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($codigo !== 200) return 'nao_encontrado';

    $dados = json_decode($resposta, true) ?: [];
    return $dados['status'] ?? 'nao_encontrado';
}

function listaEventosGoogleCalendar($token_acesso, $fuso_horario = 'America/Mexico_City', $limite = 10, $data_inicio = null, $data_fim = null, $ordem = 'asc') {
    $tz = new DateTimeZone($fuso_horario);
    $agora = new DateTime('now', $tz);

    if ($data_inicio) {
        $timeMin = (new DateTime($data_inicio, $tz))->format('c');
    } elseif ($ordem === 'desc') {
        $timeMin = (new DateTime('1970-01-01', $tz))->format('c');
    } else {
        $timeMin = $agora->format('c');
    }

    $timeMax = null;
    if ($data_fim) {
        $timeMax = (new DateTime($data_fim, $tz))->setTime(23, 59, 59)->format('c');
    } elseif ($ordem === 'desc' && !$data_inicio) {
        $timeMax = $agora->format('c');
    }

    $params = [
        'timeMin' => $timeMin,
        'maxResults' => $ordem === 'desc' ? 2500 : $limite,
        'singleEvents' => 'true',
        'orderBy' => 'startTime',
    ];
    if ($timeMax) $params['timeMax'] = $timeMax;

    $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events?' . http_build_query($params));
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

    if ($ordem === 'desc') {
        $eventos = array_reverse(array_slice($eventos, -$limite));
    } else {
        $eventos = array_slice($eventos, 0, $limite);
    }

    return $eventos;
}
