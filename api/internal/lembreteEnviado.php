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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
    exit;
}

$corpo = json_decode(file_get_contents('php://input'), true);

$id_evento = (int) ($corpo['id_evento'] ?? 0);

if (!$id_evento) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: id_evento']);
    exit;
}

marcaLembreteEnviado($id_evento);

echo json_encode(['ok' => true, 'data' => ['id_evento' => $id_evento]]);
