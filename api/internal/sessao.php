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

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $id_usuario = (int) ($_GET['id_usuario'] ?? 0);

    if (!$id_usuario) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Parâmetro id_usuario é obrigatório']);
        exit;
    }

    $sessao = buscaSessaoConversa($id_usuario);

    echo json_encode(['ok' => true, 'data' => [
        'id_usuario' => $id_usuario,
        'contexto' => $sessao ? $sessao['contexto'] : null,
        'atualizado_em' => $sessao ? $sessao['atualizado_em'] : null,
    ]]);
    exit;
}

if ($metodo === 'PUT') {
    $corpo = json_decode(file_get_contents('php://input'), true);

    $id_usuario = (int) ($corpo['id_usuario'] ?? 0);
    $contexto = $corpo['contexto'] ?? '';

    if (!$id_usuario) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: id_usuario']);
        exit;
    }

    $id_sessao = atualizaSessaoConversa($id_usuario, $contexto);

    echo json_encode(['ok' => true, 'data' => ['id_sessao' => $id_sessao]]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
