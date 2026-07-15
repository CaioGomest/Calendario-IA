<?php
require_once __DIR__ . '/../../funcoes/funcoesFinancas.php';

$pdo = conexao();
$id_usuario_teste = (int) $pdo->query('SELECT id_usuario FROM usuarios WHERE deletado = 0 LIMIT 1')->fetchColumn();

if (!$id_usuario_teste) {
    return; // nenhum usuário no banco, pula os testes
}

$pdo->beginTransaction();

try {
    testar('Finanças', 'categoriasValidas retorna array não vazio', function () {
        esperaVerdadeiro(count(categoriasValidas()) > 0);
    });

    testar('Finanças', 'validaCategoria aceita categoria existente', function () {
        espera(validaCategoria('alimentacao'), true);
    });

    testar('Finanças', 'validaCategoria rejeita categoria inexistente', function () {
        espera(validaCategoria('naoexiste'), false);
    });

    $id_transacao = null;

    testar('Finanças', 'insereTransacao retorna id inteiro positivo', function () use ($id_usuario_teste, &$id_transacao) {
        $id_transacao = insereTransacao([
            'id_usuario'     => $id_usuario_teste,
            'tipo'           => 'saida',
            'valor'          => 99.90,
            'descricao'      => '[TESTE] Almoço',
            'categoria'      => 'alimentacao',
            'data_transacao' => date('Y-m-d'),
        ]);
        esperaVerdadeiro($id_transacao > 0);
    });

    testar('Finanças', 'buscaTransacaoPorId retorna o registro inserido', function () use (&$id_transacao) {
        $t = buscaTransacaoPorId($id_transacao);
        espera($t['descricao'], '[TESTE] Almoço');
        espera($t['tipo'], 'saida');
        espera((float) $t['valor'], 99.90);
    });

    testar('Finanças', 'atualizaTransacao altera campo descricao', function () use (&$id_transacao) {
        atualizaTransacao($id_transacao, ['descricao' => '[TESTE] Jantar']);
        $t = buscaTransacaoPorId($id_transacao);
        espera($t['descricao'], '[TESTE] Jantar');
    });

    testar('Finanças', 'insereTransacao com tipo=entrada força categoria=entrada', function () use ($id_usuario_teste) {
        $id = insereTransacao([
            'id_usuario'     => $id_usuario_teste,
            'tipo'           => 'entrada',
            'valor'          => 1000.00,
            'descricao'      => '[TESTE] Salário',
            'categoria'      => 'alimentacao', // deve ser ignorado
            'data_transacao' => date('Y-m-d'),
        ]);
        $t = buscaTransacaoPorId($id);
        espera($t['categoria'], 'entrada');
    });

    testar('Finanças', 'listaTransacoes encontra registro pelo filtro busca', function () use ($id_usuario_teste) {
        $resultado = listaTransacoes($id_usuario_teste, [
            'mes'    => date('n'),
            'ano'    => date('Y'),
            'busca'  => '[TESTE]',
        ]);
        esperaVerdadeiro($resultado['total'] >= 1);
        esperaVerdadeiro(is_array($resultado['transacoes']));
    });

    testar('Finanças', 'resumoMensal soma entradas e saidas', function () use ($id_usuario_teste) {
        $resumo = resumoMensal($id_usuario_teste, date('n'), date('Y'));
        esperaVerdadeiro($resumo['total_entradas'] >= 1000.00);
        esperaVerdadeiro($resumo['total_saidas'] >= 99.90);
        espera($resumo['saldo'], $resumo['total_entradas'] - $resumo['total_saidas']);
    });

    testar('Finanças', 'saidasPorCategoria retorna array agrupado', function () use ($id_usuario_teste) {
        $saidas = saidasPorCategoria($id_usuario_teste, date('n'), date('Y'));
        esperaVerdadeiro(is_array($saidas));
        esperaVerdadeiro(count($saidas) >= 1);
        esperaVerdadeiro(array_key_exists('categoria', $saidas[0]));
        esperaVerdadeiro(array_key_exists('total', $saidas[0]));
    });

    testar('Finanças', 'resumoUltimos6Meses retorna exatamente 6 entradas', function () use ($id_usuario_teste) {
        $meses = resumoUltimos6Meses($id_usuario_teste, date('n'), date('Y'));
        espera(count($meses), 6);
        esperaVerdadeiro(array_key_exists('entradas', $meses[0]));
        esperaVerdadeiro(array_key_exists('saidas', $meses[0]));
    });

    testar('Finanças', 'deletaTransacao remove o registro', function () use (&$id_transacao) {
        $ok = deletaTransacao($id_transacao);
        espera($ok, true);
        esperaNulo(buscaTransacaoPorId($id_transacao));
    });

    testar('Finanças', 'buscaTransacaoPorId retorna null para id inexistente', function () {
        esperaNulo(buscaTransacaoPorId(999999999));
    });

    testar('Finanças', 'deletaTransacao retorna false para id inexistente', function () {
        espera(deletaTransacao(999999999), false);
    });
} finally {
    $pdo->rollBack();
}
