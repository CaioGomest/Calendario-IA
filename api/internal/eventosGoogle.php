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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
    exit;
}

$id_usuario = (int) ($_GET['id_usuario'] ?? 0);

if (!$id_usuario) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Parâmetro id_usuario é obrigatório']);
    exit;
}

$token_acesso = garanteTokenGoogleValido($id_usuario);

if (!$token_acesso) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Token Google inválido ou expirado']);
    exit;
}

$fuso = $_GET['fuso_horario'] ?? 'America/Mexico_City';
$limite = (int) ($_GET['limite'] ?? 10);

$eventos = listaEventosGoogleCalendar($token_acesso, $fuso, $limite);

echo json_encode(['ok' => true, 'data' => $eventos]);
