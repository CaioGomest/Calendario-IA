<?php

require_once __DIR__ . '/../config/conexao.php';

function insereLogIaUso($dados) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        'INSERT INTO logs_ia_uso (id_usuario, provedor, tokens_entrada, tokens_saida) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $dados['id_usuario'] ?: null,
        $dados['provedor'],
        $dados['tokens_entrada'] ?? null,
        $dados['tokens_saida'] ?? null,
    ]);
    return (int) $pdo->lastInsertId();
}

// USD por 1 milhao de tokens - atualizar aqui se os provedores reajustarem o preco
function precosIA() {
    return [
        'deepseek' => ['input' => 0.22, 'output' => 0.66],
        'gemini'   => ['input' => 0.75, 'output' => 3.75],
        'groq'     => ['input' => 0.59, 'output' => 0.79],
    ];
}

function resumoUsoIA($dias = 30) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT provedor, COUNT(*) AS total_mensagens,
                COALESCE(SUM(tokens_entrada), 0) AS tokens_entrada,
                COALESCE(SUM(tokens_saida), 0) AS tokens_saida
         FROM logs_ia_uso
         WHERE criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
         GROUP BY provedor
         ORDER BY total_mensagens DESC"
    );
    $stmt->execute([$dias]);
    $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $precos = precosIA();
    $taxa_dolar = 5.20; // cotacao aproximada USD->BRL, atualizar periodicamente

    foreach ($linhas as &$linha) {
        $preco = $precos[$linha['provedor']] ?? null;
        if ($preco) {
            $custo_usd = ($linha['tokens_entrada'] / 1000000 * $preco['input'])
                       + ($linha['tokens_saida'] / 1000000 * $preco['output']);
            $linha['custo_usd'] = round($custo_usd, 4);
            $linha['custo_brl'] = round($custo_usd * $taxa_dolar, 2);
        } else {
            $linha['custo_usd'] = null;
            $linha['custo_brl'] = null;
        }
    }
    unset($linha);

    return $linhas;
}
