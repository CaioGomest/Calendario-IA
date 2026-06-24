<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../../funcoes/funcoesGoogle.php';

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
$token_acesso = trim($corpo['token_acesso'] ?? '');
$token_expira_em = trim($corpo['token_expira_em'] ?? '');

if (!$id_usuario || $token_acesso === '' || $token_expira_em === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Campos obrigatórios: id_usuario, token_acesso, token_expira_em']);
    exit;
}

renovaTokenGoogle($id_usuario, $token_acesso, $token_expira_em);

echo json_encode(['ok' => true, 'data' => ['id_usuario' => $id_usuario]]);
