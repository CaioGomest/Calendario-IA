<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../../funcoes/funcoesEventos.php';

if (!validaSecretInterno()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
    exit;
}

$id_usuario = (int) ($_GET['id_usuario'] ?? 0);

if (!$id_usuario) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Parâmetro id_usuario é obrigatório']);
    exit;
}

$filtro = [];
if (!empty($_GET['data_inicio'])) {
    $filtro['data_inicio'] = $_GET['data_inicio'];
}
if (!empty($_GET['data_fim'])) {
    $filtro['data_fim'] = $_GET['data_fim'];
}
if (!empty($_GET['limite'])) {
    $filtro['limite'] = (int) $_GET['limite'];
}

$eventos = listaEventosUsuario($id_usuario, $filtro);

$data = array_map(function ($e) {
    return [
        'id_evento' => (int) $e['id_evento'],
        'titulo' => $e['titulo'],
        'descricao' => $e['descricao'],
        'data_inicio' => $e['data_inicio'],
        'data_fim' => $e['data_fim'],
        'id_google_event' => $e['id_google_event'],
        'lembrete' => (bool) $e['lembrete'],
        'lembrete_enviado' => (bool) $e['lembrete_enviado'],
    ];
}, $eventos);

echo json_encode(['ok' => true, 'data' => $data]);
