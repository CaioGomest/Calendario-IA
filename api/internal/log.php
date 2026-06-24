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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
    exit;
}

$corpo = json_decode(file_get_contents('php://input'), true);

$id_usuario = (int) ($corpo['id_usuario'] ?? 0);
$direcao = $corpo['direcao'] ?? '';
$conteudo = trim($corpo['conteudo'] ?? '');

if (!$id_usuario || !in_array($direcao, ['entrada', 'saida'], true) || $conteudo === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Campos obrigatórios: id_usuario, direcao (entrada|saida), conteudo']);
    exit;
}

$id_log = insereLogMensagem([
    'id_usuario' => $id_usuario,
    'direcao' => $direcao,
    'conteudo' => $conteudo,
]);

echo json_encode(['ok' => true, 'data' => ['id_log' => $id_log]]);
