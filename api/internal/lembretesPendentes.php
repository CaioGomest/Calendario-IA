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

$pendentes = listaEventosPendentesLembrete();

$data = array_map(function ($e) {
    return [
        'id_evento' => (int) $e['id_evento'],
        'id_usuario' => (int) $e['id_usuario'],
        'titulo' => $e['titulo'],
        'data_inicio' => $e['data_inicio'],
        'telefone' => $e['telefone'],
        'antecedencia_lembrete_min' => (int) $e['antecedencia_lembrete_min'],
        'modo_silencio' => (bool) $e['modo_silencio'],
        'fuso_horario' => $e['fuso_horario'],
    ];
}, $pendentes);

echo json_encode(['ok' => true, 'data' => $data]);
