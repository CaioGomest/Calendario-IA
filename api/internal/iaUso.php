<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../../funcoes/funcoesIA.php';

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
$provedor = trim($corpo['provedor'] ?? '');

if ($provedor === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: provedor']);
    exit;
}

$id_log = insereLogIaUso([
    'id_usuario' => (int) ($corpo['id_usuario'] ?? 0),
    'provedor' => $provedor,
    'tokens_entrada' => isset($corpo['tokens_entrada']) ? (int) $corpo['tokens_entrada'] : null,
    'tokens_saida' => isset($corpo['tokens_saida']) ? (int) $corpo['tokens_saida'] : null,
]);

echo json_encode(['ok' => true, 'data' => ['id_log' => $id_log]]);
