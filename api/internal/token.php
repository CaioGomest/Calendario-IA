<?php

header('Content-Type: application/json');
header_remove('X-Powered-By');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../../funcoes/funcoesGoogle.php';

if (!validaSecretInterno()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Não autorizado']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$corpo = json_decode(file_get_contents('php://input'), true);

if ($metodo === 'POST') {
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
    exit;
}

if ($metodo === 'DELETE') {
    $id_usuario = (int) ($corpo['id_usuario'] ?? 0);

    if (!$id_usuario) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: id_usuario']);
        exit;
    }

    desconectaGoogle($id_usuario);

    echo json_encode(['ok' => true, 'data' => ['id_usuario' => $id_usuario]]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
