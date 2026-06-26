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
$corpo = json_decode(file_get_contents('php://input'), true);

if ($metodo === 'POST') {
    $id_usuario = (int) ($corpo['id_usuario'] ?? 0);
    $titulo = trim($corpo['titulo'] ?? '');
    $data_inicio = trim($corpo['data_inicio'] ?? '');

    if (!$id_usuario || $titulo === '' || $data_inicio === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campos obrigatórios: id_usuario, titulo, data_inicio']);
        exit;
    }

    $id_evento = insereEvento([
        'id_usuario' => $id_usuario,
        'titulo' => $titulo,
        'descricao' => $corpo['descricao'] ?? null,
        'data_inicio' => $data_inicio,
        'data_fim' => $corpo['data_fim'] ?? null,
        'id_google_event' => $corpo['id_google_event'] ?? null,
        'lembrete' => $corpo['lembrete'] ?? 1,
    ]);

    echo json_encode(['ok' => true, 'data' => ['id_evento' => $id_evento]]);
    exit;
}

if ($metodo === 'PUT') {
    $id_evento = (int) ($corpo['id_evento'] ?? 0);

    if (!$id_evento) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: id_evento']);
        exit;
    }

    unset($corpo['id_evento']);
    $atualizado = atualizaEvento($id_evento, $corpo);

    if (!$atualizado) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'Evento não encontrado ou nenhum campo alterado']);
        exit;
    }

    echo json_encode(['ok' => true, 'data' => ['id_evento' => $id_evento]]);
    exit;
}

if ($metodo === 'DELETE') {
    $id_evento = (int) ($corpo['id_evento'] ?? 0);

    if (!$id_evento) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: id_evento']);
        exit;
    }

    $deletado = deletaEvento($id_evento);

    if (!$deletado) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'Evento não encontrado']);
        exit;
    }

    echo json_encode(['ok' => true, 'data' => ['id_evento' => $id_evento]]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
