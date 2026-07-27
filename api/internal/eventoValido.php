<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../../funcoes/funcoesEventos.php';
require_once __DIR__ . '/../../funcoes/funcoesGoogle.php';

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

$id_evento = (int) ($_GET['id_evento'] ?? 0);

if (!$id_evento) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: id_evento']);
    exit;
}

$evento = buscaEventoPorId($id_evento);

if (!$evento || empty($evento['id_google_event'])) {
    echo json_encode(['ok' => true, 'data' => ['valido' => true]]);
    exit;
}

$token_acesso = garanteTokenGoogleValido($evento['id_usuario']);

if (!$token_acesso) {
    echo json_encode(['ok' => true, 'data' => ['valido' => true]]);
    exit;
}

$status = buscaStatusEventoGoogle($token_acesso, $evento['id_google_event']);

if ($status === 'cancelled' || $status === 'nao_encontrado') {
    atualizaEvento($id_evento, ['lembrete' => 0]);
    echo json_encode(['ok' => true, 'data' => ['valido' => false]]);
    exit;
}

echo json_encode(['ok' => true, 'data' => ['valido' => true]]);
