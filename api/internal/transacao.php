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
    $tipo = trim($corpo['tipo'] ?? '');
    $valor = (float) ($corpo['valor'] ?? 0);
    $descricao = trim($corpo['descricao'] ?? '');
    $data_transacao = trim($corpo['data_transacao'] ?? '');

    if (!$id_usuario || !in_array($tipo, ['entrada', 'saida'], true) || $valor <= 0 || $descricao === '' || $data_transacao === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campos obrigatórios: id_usuario, tipo (entrada|saida), valor, descricao, data_transacao']);
        exit;
    }

    $id_transacao = insereTransacao([
        'id_usuario' => $id_usuario,
        'tipo' => $tipo,
        'valor' => $valor,
        'descricao' => $descricao,
        'categoria' => $corpo['categoria'] ?? 'outros',
        'data_transacao' => $data_transacao,
    ]);

    echo json_encode(['ok' => true, 'data' => ['id_transacao' => $id_transacao]]);
    exit;
}

if ($metodo === 'GET') {
    $id_usuario = (int) ($_GET['id_usuario'] ?? 0);

    if (!$id_usuario) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: id_usuario']);
        exit;
    }

    $filtro = [];
    if (!empty($_GET['data_inicio']) && !empty($_GET['data_fim'])) {
        $filtro['data_inicio'] = $_GET['data_inicio'];
        $filtro['data_fim'] = $_GET['data_fim'];
    } else {
        if (!empty($_GET['mes'])) $filtro['mes'] = $_GET['mes'];
        if (!empty($_GET['ano'])) $filtro['ano'] = $_GET['ano'];
    }
    if (!empty($_GET['tipo'])) $filtro['tipo'] = $_GET['tipo'];
    if (!empty($_GET['categoria'])) $filtro['categoria'] = $_GET['categoria'];
    if (!empty($_GET['pagina'])) $filtro['pagina'] = $_GET['pagina'];
    if (!empty($_GET['por_pagina'])) $filtro['por_pagina'] = $_GET['por_pagina'];

    $resultado = listaTransacoes($id_usuario, $filtro);

    $periodo_inicio = null;
    $periodo_fim = null;
    if (!empty($filtro['data_inicio']) && !empty($filtro['data_fim'])) {
        $periodo_inicio = $filtro['data_inicio'];
        $periodo_fim = $filtro['data_fim'];
        $resultado['resumo'] = resumoPeriodo($id_usuario, $periodo_inicio, $periodo_fim);
    } elseif (!empty($filtro['mes']) && !empty($filtro['ano'])) {
        $mes = (int) $filtro['mes'];
        $ano = (int) $filtro['ano'];
        $periodo_inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $periodo_fim = date('Y-m-t', strtotime($periodo_inicio));
        $resultado['resumo'] = resumoMensal($id_usuario, $mes, $ano);
    }

    if ($periodo_inicio && $periodo_fim && (empty($filtro['tipo']) || $filtro['tipo'] === 'saida')) {
        $ocorrencias = calculaOcorrenciasRecorrentes($id_usuario, $periodo_inicio, $periodo_fim);
        if (!empty($filtro['categoria'])) {
            $ocorrencias = array_values(array_filter($ocorrencias, function ($o) use ($filtro) {
                return $o['categoria'] === $filtro['categoria'];
            }));
        }
        if ($ocorrencias) {
            $resultado['transacoes'] = array_merge($resultado['transacoes'], $ocorrencias);
            usort($resultado['transacoes'], function ($a, $b) {
                return strcmp($b['data_transacao'], $a['data_transacao']);
            });
            if (isset($resultado['resumo'])) {
                $soma_ocorrencias = array_sum(array_column($ocorrencias, 'valor'));
                $resultado['resumo']['total_saidas'] += $soma_ocorrencias;
                $resultado['resumo']['saldo'] -= $soma_ocorrencias;
            }
        }
    }

    echo json_encode(['ok' => true, 'data' => $resultado]);
    exit;
}

if ($metodo === 'DELETE') {
    $id_transacao = (int) ($corpo['id_transacao'] ?? 0);

    if (!$id_transacao) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Campo obrigatório: id_transacao']);
        exit;
    }

    $deletado = deletaTransacao($id_transacao);

    if (!$deletado) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'Transação não encontrada']);
        exit;
    }

    echo json_encode(['ok' => true, 'data' => ['id_transacao' => $id_transacao]]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
