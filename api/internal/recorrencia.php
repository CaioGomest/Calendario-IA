<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../../funcoes/funcoesFinancas.php';

if (!validaSecretInterno()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Não autorizado']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$corpo = json_decode(file_get_contents('php://input'), true);

if ($metodo === 'POST') {
    $id_usuario = (int) ($corpo['id_usuario'] ?? 0);
    $descricao = trim($corpo['descricao'] ?? '');
    $valor = (float) ($corpo['valor'] ?? 0);
    $ciclo = trim($corpo['ciclo'] ?? 'mensal');
    $data_inicio = trim($corpo['data_inicio'] ?? date('Y-m-d'));

    if (!$id_usuario || $descricao === '' || $valor <= 0 || !in_array($ciclo, ['semanal', 'mensal', 'anual'], true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campos obrigatórios: id_usuario, descricao, valor, ciclo (semanal|mensal|anual)']);
        exit;
    }

    $id_recorrencia = insereRecorrencia([
        'id_usuario' => $id_usuario,
        'descricao' => $descricao,
        'valor' => $valor,
        'categoria' => $corpo['categoria'] ?? 'outros',
        'ciclo' => $ciclo,
        'proxima_cobranca' => $data_inicio,
    ]);

    echo json_encode(['ok' => true, 'data' => ['id_recorrencia' => $id_recorrencia]]);
    exit;
}

if ($metodo === 'GET') {
    $id_usuario = (int) ($_GET['id_usuario'] ?? 0);

    if (!$id_usuario) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: id_usuario']);
        exit;
    }

    echo json_encode(['ok' => true, 'data' => listaRecorrenciasAtivasUsuario($id_usuario)]);
    exit;
}

if ($metodo === 'DELETE') {
    $id_recorrencia = (int) ($corpo['id_recorrencia'] ?? 0);

    if (!$id_recorrencia) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: id_recorrencia']);
        exit;
    }

    $cancelada = cancelaRecorrencia($id_recorrencia);

    if (!$cancelada) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'Recorrência não encontrada']);
        exit;
    }

    echo json_encode(['ok' => true, 'data' => ['id_recorrencia' => $id_recorrencia]]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
