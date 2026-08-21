<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../funcoes/funcoesStripe.php';
require_once __DIR__ . '/../../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../../funcoes/funcoesPlanos.php';
require_once __DIR__ . '/../../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../../funcoes/funcoesEmail.php';
require_once __DIR__ . '/../../funcoes/funcoesIdioma.php';

$payload = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!validaAssinaturaStripe($payload, $sig_header)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Assinatura inválida']);
    exit;
}

$evento = json_decode($payload, true);
$tipo = $evento['type'] ?? '';
$objeto = $evento['data']['object'] ?? [];

switch ($tipo) {
    case 'checkout.session.completed':
        $customer_id = $objeto['customer'] ?? '';
        $subscription_id = $objeto['subscription'] ?? '';
        $id_usuario = (int) ($objeto['metadata']['id_usuario'] ?? 0);

        if ($id_usuario) {
            $usuario = buscaUsuarioPorId($id_usuario);
        } elseif ($customer_id) {
            $usuario = buscaUsuarioPorStripeCustomer($customer_id);
        } else {
            $usuario = null;
        }

        if ($usuario) {
            $expira_em = null;
            $plano_novo = null;
            $id_plano_novo = null;
            if ($subscription_id) {
                $sub = stripeRequest('GET', '/v1/subscriptions/' . $subscription_id);
                if (!isset($sub['error'])) {
                    if (!empty($sub['current_period_end'])) {
                        $expira_em = date('Y-m-d H:i:s', (int)$sub['current_period_end']);
                    }
                    $plano_novo = mapeiaStatusStripeParaPlano($sub['status'] ?? '');
                    $id_plano_novo = (int)($sub['metadata']['id_plano'] ?? 0) ?: null;
                }
            }

            if ($plano_novo !== null) {
                atualizaPlanoUsuario((int)$usuario['id_usuario'], $plano_novo, $expira_em, $id_plano_novo);
            }
            atualizaStripeUsuario((int)$usuario['id_usuario'], $customer_id, $subscription_id);
        }
        break;

    case 'customer.subscription.updated':
        $customer_id = $objeto['customer'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario && !empty($objeto['current_period_end'])) {
            $expira_em = date('Y-m-d H:i:s', (int)$objeto['current_period_end']);
            $status = $objeto['status'] ?? '';
            $plano = mapeiaStatusStripeParaPlano($status);
            if ($plano === null) {
                $plano = $usuario['plano'];
            }
            $id_plano_novo = (int)($objeto['metadata']['id_plano'] ?? 0) ?: null;
            atualizaPlanoUsuario((int)$usuario['id_usuario'], $plano, $expira_em, $id_plano_novo);
        }
        break;

    case 'customer.subscription.deleted':
        $customer_id = $objeto['customer'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario) {
            atualizaPlanoUsuario((int)$usuario['id_usuario'], 'cancelado', null);
            atualizaStripeUsuario((int)$usuario['id_usuario'], $customer_id, null);
        }
        break;

    case 'invoice.paid':
    case 'invoice.payment_succeeded':
        $customer_id = $objeto['customer'] ?? '';
        $subscription_id = $objeto['subscription'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario && $subscription_id) {
            $sub = stripeRequest('GET', '/v1/subscriptions/' . $subscription_id);
            $status_mapeado = mapeiaStatusStripeParaPlano($sub['status'] ?? '');
            if (!isset($sub['error']) && $status_mapeado !== null) {
                $expira_em = !empty($sub['current_period_end'])
                    ? date('Y-m-d H:i:s', (int)$sub['current_period_end'])
                    : null;
                $id_plano_novo = (int)($sub['metadata']['id_plano'] ?? 0) ?: null;
                atualizaPlanoUsuario((int)$usuario['id_usuario'], $status_mapeado, $expira_em, $id_plano_novo);
                atualizaStripeUsuario((int)$usuario['id_usuario'], $customer_id, $subscription_id);

                if (!empty($usuario['pagamento_falhou'])) {
                    atualizaPagamentoFalhouUsuario((int)$usuario['id_usuario'], false);
                }

                $valor_pago = ((int) ($objeto['amount_paid'] ?? 0)) / 100;
                if ($valor_pago > 0) {
                    $id_pagamento = registraPagamento([
                        'id_usuario' => (int)$usuario['id_usuario'],
                        'id_plano' => (int) ($sub['metadata']['id_plano'] ?? 0),
                        'ciclo' => cicloDaAssinaturaStripe($sub),
                        'valor' => $valor_pago,
                        'stripe_invoice_id' => $objeto['id'] ?? null,
                    ]);

                    if ($id_pagamento > 0) {
                        $indicacao = buscaIndicacaoPorUsuario((int)$usuario['id_usuario']);
                        if ($indicacao) {
                            $afiliado = buscaAfiliadoPorId($indicacao['id_afiliado']);
                            if ($afiliado) {
                                $valor_comissao = round($valor_pago * $afiliado['comissao_percentual'] / 100, 2);
                                registraComissaoAfiliado($indicacao['id_indicacao'], $id_pagamento, $valor_comissao);
                            }
                        }
                    }
                }
            }
        }
        break;

    case 'invoice.payment_failed':
        $customer_id = $objeto['customer'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario) {
            atualizaPagamentoFalhouUsuario((int)$usuario['id_usuario'], true);

            $valor_falho = ((int) ($objeto['amount_due'] ?? $objeto['total'] ?? 0)) / 100;
            if ($valor_falho > 0) {
                registraPagamento([
                    'id_usuario' => (int)$usuario['id_usuario'],
                    'id_plano' => (int) ($objeto['parent']['subscription_details']['metadata']['id_plano'] ?? 0) ?: null,
                    'ciclo' => 'mensal',
                    'valor' => $valor_falho,
                    'status' => 'falhou',
                    'stripe_invoice_id' => $objeto['id'] ?? null,
                ]);
            }

            $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $link = $protocolo . '://' . $_SERVER['HTTP_HOST'] . '/cliente/conta';

            $assunto = sprintf(traduz('email_pagamento_falhou_assunto'), nomeApp());
            $corpo = sprintf(traduz('email_pagamento_falhou_corpo'),
                htmlspecialchars($usuario['nome']),
                $link,
                $link,
                nomeApp()
            );
            enviaEmail($usuario['email'], $assunto, $corpo);

            error_log("Stripe: pagamento falhou para usuario {$usuario['id_usuario']} (customer {$customer_id})");
        }
        break;

    case 'charge.refunded':
        $customer_id = $objeto['customer'] ?? '';
        $invoice_id = $objeto['invoice'] ?? '';

        if (!$invoice_id && !empty($objeto['payment_intent'])) {
            $pi = stripeRequest('GET', '/v1/payment_intents/' . $objeto['payment_intent']);
            $invoice_id = $pi['payment_details']['order_reference'] ?? '';
        }

        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario && $invoice_id) {
            $pagamento = buscaPagamentoPorInvoice($invoice_id);

            if ($pagamento && $pagamento['status'] === 'pago') {
                $era_mais_recente = ehPagamentoMaisRecentePago((int)$usuario['id_usuario'], $pagamento['criado_em']);
                marcaPagamentoReembolsado($invoice_id);

                if ($era_mais_recente && !empty($usuario['stripe_subscription_id'])) {
                    cancelaAssinaturaStripe($usuario['stripe_subscription_id']);
                    atualizaPlanoUsuario((int)$usuario['id_usuario'], 'cancelado', null);
                    atualizaStripeUsuario((int)$usuario['id_usuario'], $customer_id, null);
                }
            }
        }
        break;
}

http_response_code(200);
echo json_encode(['ok' => true]);
