<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funcoesUsuarios.php';

function stripeRequest($metodo, $endpoint, $params = [], $idempotency_key = null) {
    $headers = ['Content-Type: application/x-www-form-urlencoded'];
    if ($idempotency_key) {
        $headers[] = 'Idempotency-Key: ' . $idempotency_key;
    }
    $ch = curl_init('https://api.stripe.com' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
        CURLOPT_HTTPHEADER => $headers,
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
    $erro_curl = curl_error($ch);
    curl_close($ch);

    $dados = json_decode((string) $resposta, true);

    // sem resposta (timeout, DNS, rede) nunca pode virar null: quem chama testa isset($r['error'])
    if ($resposta === false || !is_array($dados)) {
        error_log("Stripe API sem resposta valida [{$http_code}] {$metodo} {$endpoint}: {$erro_curl}");
        return ['error' => $erro_curl ?: 'Resposta invalida da API Stripe', 'http_code' => 0];
    }

    if ($http_code >= 400) {
        $msg = $dados['error']['message'] ?? 'Erro desconhecido na API Stripe';
        error_log("Stripe API erro [{$http_code}] {$metodo} {$endpoint}: {$msg}");
        return ['error' => $msg, 'http_code' => $http_code, 'code' => $dados['error']['code'] ?? null];
    }

    return $dados;
}

function criaClienteStripe($email, $nome, $idempotency_key = null) {
    return stripeRequest('POST', '/v1/customers', [
        'email' => $email,
        'name' => $nome,
    ], $idempotency_key);
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

function removePagamentoPadraoStripe($customer_id) {
    return stripeRequest('POST', '/v1/customers/' . $customer_id, [
        'invoice_settings[default_payment_method]' => '',
    ]);
}

function definePagamentoPadraoStripe($customer_id, $payment_method_id) {
    return stripeRequest('POST', '/v1/customers/' . $customer_id, [
        'invoice_settings[default_payment_method]' => $payment_method_id,
    ]);
}

function criaSetupIntentStripe($customer_id, $idempotency_key = null) {
    return stripeRequest('POST', '/v1/setup_intents', [
        'customer' => $customer_id,
        'usage' => 'off_session',
        'payment_method_types[]' => 'card',
    ], $idempotency_key);
}

function buscaSetupIntentStripe($setup_intent_id) {
    return stripeRequest('GET', '/v1/setup_intents/' . $setup_intent_id);
}

function fimPeriodoAssinaturaStripe($sub) {
    return $sub['items']['data'][0]['current_period_end'] ?? $sub['current_period_end'] ?? null;
}

function criaAssinaturaStripe($customer_id, $dados, $idempotency_key = null) {
    $preco = criaPrecoStripe($dados);
    if (isset($preco['error'])) {
        return $preco;
    }

    $params = [
        'customer' => $customer_id,
        'items[0][price]' => $preco['id'],
        'payment_behavior' => 'default_incomplete',
        'payment_settings[save_default_payment_method]' => 'on_subscription',
        'expand' => ['latest_invoice.confirmation_secret', 'pending_setup_intent'],
        'metadata[id_usuario]' => $dados['id_usuario'],
        'metadata[id_plano]' => $dados['id_plano'],
    ];

    if (!empty($dados['dias_teste']) && (int)$dados['dias_teste'] > 0) {
        $params['trial_period_days'] = (int)$dados['dias_teste'];
        $params['trial_settings[end_behavior][missing_payment_method]'] = 'cancel';
    }

    return stripeRequest('POST', '/v1/subscriptions', $params, $idempotency_key);
}

function buscaAssinaturaStripe($subscription_id) {
    return stripeRequest('GET', '/v1/subscriptions/' . $subscription_id);
}

// Mesmo ciclo: proration_behavior=none, o novo preco so vale a partir do proximo
// ciclo (planos baratos, cobranca fracionada imediata arrisca ficar abaixo do
// minimo do processador de cartao).
// Ciclo diferente (mensal <-> trimestral <-> anual): a Stripe reinicia o ciclo e
// cobra o preco novo cheio na hora, entao sem credito do periodo nao usado o
// cliente pagaria duas vezes. Aqui o ciclo reinicia agora, com credito
// proporcional, e se a cobranca falhar a troca nao e aplicada.
function atualizaAssinaturaStripe($subscription_id, $item_id, $novo_price_id, $id_plano, $muda_ciclo = false) {
    $params = [
        'items[0][id]' => $item_id,
        'items[0][price]' => $novo_price_id,
        'metadata[id_plano]' => $id_plano,
    ];

    if ($muda_ciclo) {
        $params['proration_behavior'] = 'create_prorations';
        $params['billing_cycle_anchor'] = 'now';
        $params['payment_behavior'] = 'error_if_incomplete';
    } else {
        $params['proration_behavior'] = 'none';
    }

    return stripeRequest('POST', '/v1/subscriptions/' . $subscription_id, $params);
}

function mapeiaStatusStripeParaPlano($status) {
    if ($status === 'trialing') return 'trial';
    if ($status === 'active') return 'ativo';
    return null;
}

function cicloDaAssinaturaStripe($sub) {
    $recurring = $sub['items']['data'][0]['price']['recurring'] ?? [];
    $interval = $recurring['interval'] ?? 'month';
    $count = (int) ($recurring['interval_count'] ?? 1);

    if ($interval === 'year') return 'anual';
    if ($interval === 'month' && $count >= 3) return 'trimestral';
    return 'mensal';
}

function cancelaAssinaturaStripe($subscription_id) {
    return stripeRequest('DELETE', '/v1/subscriptions/' . $subscription_id);
}

// "R$ 68 / trimestre" no idioma do usuario, a partir do preco da assinatura (avisos de cobranca por e-mail)
function textoCobrancaAssinaturaStripe($sub, $idioma) {
    $centavos = (int) ($sub['items']['data'][0]['price']['unit_amount'] ?? 0);
    return formataPrecoPlano($centavos / 100, $idioma) . ' ' . traduzPara('upgrade_ciclo_' . cicloDaAssinaturaStripe($sub), $idioma);
}

// evento de assinatura antiga ou orfa (duplicada, descartada) nao pode mexer no plano de quem
// tem outra assinatura registrada; sem assinatura registrada o evento vale (checkout em andamento)
function assinaturaPertenceAoUsuario($usuario, $subscription_id) {
    return empty($usuario['stripe_subscription_id']) || $usuario['stripe_subscription_id'] === $subscription_id;
}

// cancelamento do cliente: para as cobrancas futuras mas o periodo ja pago segue valendo;
// a Stripe manda customer.subscription.deleted quando o periodo termina
function agendaCancelamentoAssinaturaStripe($subscription_id, $agendar) {
    return stripeRequest('POST', '/v1/subscriptions/' . $subscription_id, [
        'cancel_at_period_end' => $agendar ? 'true' : 'false',
    ]);
}

// assinaturas que ainda geram ou podem gerar cobranca; devolve lista ou ['error' => ...]
function listaAssinaturasVigentesStripe($customer_id) {
    $resposta = stripeRequest('GET', '/v1/subscriptions?' . http_build_query([
        'customer' => $customer_id,
        'status' => 'all',
        'limit' => 20,
    ]));
    if (isset($resposta['error'])) {
        return $resposta;
    }

    return array_values(array_filter($resposta['data'] ?? [], function ($sub) {
        return in_array($sub['status'] ?? '', ['active', 'trialing', 'past_due', 'unpaid'], true);
    }));
}

// agenda (ou desfaz) o cancelamento ao fim do periodo pago e so grava no banco se a Stripe confirmar,
// pra nunca marcar "cancelado" com a assinatura ainda cobrando. Alem da assinatura gravada, pega as
// vigentes do customer (vinculo perdido ou assinatura duplicada continuariam cobrando)
function alteraCancelamentoAgendadoUsuario($usuario, $agendar) {
    if (!MODO_DEV) {
        $ids = !empty($usuario['stripe_subscription_id']) ? [$usuario['stripe_subscription_id']] : [];

        if (!empty($usuario['stripe_customer_id'])) {
            $vigentes = listaAssinaturasVigentesStripe($usuario['stripe_customer_id']);
            if (isset($vigentes['error']) && !$ids) {
                return false;
            }
            if (!isset($vigentes['error'])) {
                $ids = array_values(array_unique(array_merge($ids, array_column($vigentes, 'id'))));
            }
        }

        foreach ($ids as $subscription_id) {
            if (isset(agendaCancelamentoAssinaturaStripe($subscription_id, $agendar)['error'])) {
                return false;
            }
        }
    }

    atualizaPlanoUsuario((int) $usuario['id_usuario'], $usuario['plano'], $usuario['plano_expira_em'], null, $agendar);
    return true;
}

// antes de criar assinatura: se a Stripe ja tem uma vigente para o cliente (webhook atrasado, outra aba,
// vinculo perdido), alinha o banco com ela em vez de cobrar em duplicidade.
// Devolve 'sincronizada' (ativa/em teste), 'pendente' (past_due: o cliente precisa acertar o cartao) ou null
function sincronizaAssinaturaVigenteUsuario($usuario) {
    if (MODO_DEV || empty($usuario['stripe_customer_id'])) {
        return null;
    }

    $vigentes = listaAssinaturasVigentesStripe($usuario['stripe_customer_id']);
    if (isset($vigentes['error'])) {
        return null;
    }

    foreach ($vigentes as $sub) {
        $plano = mapeiaStatusStripeParaPlano($sub['status'] ?? '');
        if ($plano === null) {
            continue;
        }
        $fim_periodo = fimPeriodoAssinaturaStripe($sub);
        atualizaPlanoUsuario(
            (int) $usuario['id_usuario'],
            $plano,
            $fim_periodo ? date('Y-m-d H:i:s', (int) $fim_periodo) : $usuario['plano_expira_em'],
            (int) ($sub['metadata']['id_plano'] ?? 0) ?: null,
            !empty($sub['cancel_at_period_end'])
        );
        atualizaStripeUsuario((int) $usuario['id_usuario'], $usuario['stripe_customer_id'], $sub['id']);
        return 'sincronizada';
    }

    return $vigentes ? 'pendente' : null;
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
