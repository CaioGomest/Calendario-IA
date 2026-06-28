<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../../funcoes/funcoesConfiguracao.php';

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

$telefone = trim($_GET['telefone'] ?? '');

if ($telefone === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Parâmetro telefone é obrigatório']);
    exit;
}

$usuario = buscaUsuarioPorTelefone($telefone);

if (!$usuario) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'erro' => 'Usuário não encontrado']);
    exit;
}

echo json_encode(['ok' => true, 'data' => [
    'id_usuario' => (int) $usuario['id_usuario'],
    'nome' => $usuario['nome'],
    'email' => $usuario['email'],
    'telefone' => $usuario['telefone'],
    'plano' => $usuario['plano'],
    'plano_expira_em' => $usuario['plano_expira_em'],
    'ativo' => (bool) $usuario['ativo'],
    'fuso_horario' => $usuario['fuso_horario'],
    'modo_silencio' => (bool) $usuario['modo_silencio'],
    'antecedencia_lembrete_min' => (int) $usuario['antecedencia_lembrete_min'],
    'token_acesso_google' => $usuario['token_acesso_google'],
    'token_refresh_google' => $usuario['token_refresh_google'],
    'token_google_expira_em' => $usuario['token_google_expira_em'],
    'idioma' => buscaConfiguracao('idioma_padrao') ?? IDIOMA_PADRAO,
]]);
