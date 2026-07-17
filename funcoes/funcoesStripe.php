<?php

require_once __DIR__ . '/../config/config.php';

function stripeRequest($metodo, $endpoint, $params = []) {
    $ch = curl_init('https://api.stripe.com' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($metodo === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    } elseif ($metodo === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $resposta = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $dados = json_decode($resposta, true);

    if ($http_code >= 400) {
        $msg = $dados['error']['message'] ?? 'Erro desconhecido na API Stripe';
        error_log("Stripe API erro [{$http_code}] {$metodo} {$endpoint}: {$msg}");
        return ['error' => $msg, 'http_code' => $http_code];
    }

    return $dados;
}

function criaClienteStripe($email, $nome) {
    return stripeRequest('POST', '/v1/customers', [
        'email' => $email,
        'name' => $nome,
    ]);
}

function criaPrecoStripe($dados) {
    $moeda = strtolower(function_exists('moedaSistema') ? moedaSistema() : 'brl');

    $intervalos = [
        'mensal' => ['interval' => 'month', 'count' => 1],
        'trimestral' => ['interval' => 'month', 'count' => 3],
        'anual' => ['interval' => 'year', 'count' => 1],
    ];
    $ciclo = $intervalos[$dados['ciclo']] ?? $intervalos['mensal'];
    $valor_centavos = (int) round((float)$dados['preco'] * 100);

    return stripeRequest('POST', '/v1/prices', [
        'currency' => $moeda,
        'unit_amount' => $valor_centavos,
        'recurring[interval]' => $ciclo['interval'],
        'recurring[interval_count]' => $ciclo['count'],
        'product_data[name]' => $dados['nome_plano'],
    ]);
}

function criaAssinaturaStripe($customer_id, $dados) {
    $preco = criaPrecoStripe($dados);
    if (isset($preco['error'])) {
        return $preco;
    }

    $params = [
        'customer' => $customer_id,
        'items[0][price]' => $preco['id'],
        'payment_behavior' => 'default_incomplete',
        'payment_settings[save_default_payment_method]' => 'on_subscription',
        'expand[]' => 'latest_invoice.payment_intent',
        'metadata[id_usuario]' => $dados['id_usuario'],
        'metadata[id_plano]' => $dados['id_plano'],
    ];

    if (!empty($dados['dias_teste']) && (int)$dados['dias_teste'] > 0) {
        $params['trial_period_days'] = (int)$dados['dias_teste'];
    }

    return stripeRequest('POST', '/v1/subscriptions', $params);
}

function buscaAssinaturaStripe($subscription_id) {
    return stripeRequest('GET', '/v1/subscriptions/' . $subscription_id);
}

function cancelaAssinaturaStripe($subscription_id) {
    return stripeRequest('DELETE', '/v1/subscriptions/' . $subscription_id);
}

function criaPortalSession($customer_id, $url_retorno) {
    return stripeRequest('POST', '/v1/billing_portal/sessions', [
        'customer' => $customer_id,
        'return_url' => $url_retorno,
    ]);
}

function validaAssinaturaStripe($payload, $sig_header) {
    if (STRIPE_WEBHOOK_SECRET === '' || $sig_header === '') {
        return false;
    }

    $partes = [];
    foreach (explode(',', $sig_header) as $item) {
        $par = explode('=', $item, 2);
        if (count($par) === 2) {
            $partes[$par[0]] = $par[1];
        }
    }

    $timestamp = $partes['t'] ?? '';
    $assinatura = $partes['v1'] ?? '';

    if ($timestamp === '' || $assinatura === '') {
        return false;
    }

    if (abs(time() - (int)$timestamp) > 300) {
        return false;
    }

    $esperado = hash_hmac('sha256', $timestamp . '.' . $payload, STRIPE_WEBHOOK_SECRET);
    return hash_equals($esperado, $assinatura);
}
