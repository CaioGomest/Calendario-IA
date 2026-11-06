<?php

header('Content-Type: application/json');
header_remove('X-Powered-By');

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

        if ($usuario && !empty($objeto['id']) && assinaturaPertenceAoUsuario($usuario, $objeto['id'])) {
            // le o estado atual na Stripe em vez de confiar no payload: evento atrasado ou fora de
            // ordem nao pode reverter o plano. past_due/unpaid/canceled nao estendem o acesso: so um
            // pagamento confirmado (invoice.paid) ou o fim da assinatura (deleted) mudam isso
            $sub = buscaAssinaturaStripe($objeto['id']);
            $plano = isset($sub['error']) ? null : mapeiaStatusStripeParaPlano($sub['status'] ?? '');
            $periodo_fim = $plano !== null ? fimPeriodoAssinaturaStripe($sub) : null;

            if ($periodo_fim) {
                atualizaPlanoUsuario(
                    (int)$usuario['id_usuario'],
                    $plano,
                    date('Y-m-d H:i:s', (int)$periodo_fim),
                    (int)($sub['metadata']['id_plano'] ?? 0) ?: null,
                    !empty($sub['cancel_at_period_end'])
                );
                atualizaStripeUsuario((int)$usuario['id_usuario'], $customer_id, $sub['id']);
            }
        }
        break;

    case 'customer.subscription.deleted':
        $customer_id = $objeto['customer'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario && assinaturaPertenceAoUsuario($usuario, $objeto['id'] ?? '')) {
            $ja_cancelado = $usuario['plano'] === 'cancelado';
            atualizaPlanoUsuario((int)$usuario['id_usuario'], 'cancelado', null);
            atualizaStripeUsuario((int)$usuario['id_usuario'], $customer_id, null);
            if (!$ja_cancelado) {
                enviaEmailAssinaturaEvento($usuario, 'assinatura_encerrada');
            }
        }
        break;

    case 'customer.subscription.trial_will_end':
        $customer_id = $objeto['customer'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario && assinaturaPertenceAoUsuario($usuario, $objeto['id'] ?? '')
            && !empty($objeto['trial_end']) && empty($objeto['cancel_at_period_end'])) {
            enviaEmailAssinaturaEvento($usuario, 'fim_teste', [
                'valor' => textoCobrancaAssinaturaStripe($objeto, idiomaDoUsuario($usuario)),
                'data' => date('d/m/Y', (int)$objeto['trial_end']),
            ]);
        }
        break;

    case 'invoice.upcoming':
        $customer_id = $objeto['customer'] ?? '';
        $subscription_id = $objeto['subscription'] ?? $objeto['parent']['subscription_details']['subscription'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;
        $data_cobranca = $objeto['next_payment_attempt'] ?? $objeto['lines']['data'][0]['period']['start'] ?? null;
        $valor_cobranca = ((int)($objeto['amount_due'] ?? 0)) / 100;

        if ($usuario && $subscription_id && assinaturaPertenceAoUsuario($usuario, $subscription_id) && $data_cobranca && $valor_cobranca > 0) {
            enviaEmailAssinaturaEvento($usuario, 'renovacao_proxima', [
                'valor' => formataPrecoPlano($valor_cobranca, idiomaDoUsuario($usuario)),
                'data' => date('d/m/Y', (int)$data_cobranca),
            ]);
        }
        break;

    case 'invoice.payment_action_required':
        $customer_id = $objeto['customer'] ?? '';
        $subscription_id = $objeto['subscription'] ?? $objeto['parent']['subscription_details']['subscription'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario && $subscription_id && assinaturaPertenceAoUsuario($usuario, $subscription_id)) {
            enviaEmailAssinaturaEvento($usuario, 'acao_necessaria', ['link' => $objeto['hosted_invoice_url'] ?? '']);
        }
        break;

    case 'invoice.paid':
    case 'invoice.payment_succeeded':
        $customer_id = $objeto['customer'] ?? '';
        $subscription_id = $objeto['subscription'] ?? $objeto['parent']['subscription_details']['subscription'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario && $subscription_id) {
            $sub = stripeRequest('GET', '/v1/subscriptions/' . $subscription_id);
            $status_mapeado = mapeiaStatusStripeParaPlano($sub['status'] ?? '');
            if (!isset($sub['error']) && $status_mapeado !== null) {
                $periodo_fim = $sub['current_period_end'] ?? $sub['items']['data'][0]['current_period_end'] ?? null;
                $expira_em = !empty($periodo_fim) ? date('Y-m-d H:i:s', (int)$periodo_fim) : null;
                $id_plano_novo = (int)($sub['metadata']['id_plano'] ?? 0) ?: null;

                // pagamento de assinatura antiga/orfa continua registrado (o dinheiro entrou), mas nao mexe no plano de quem tem outra
                if (assinaturaPertenceAoUsuario($usuario, $subscription_id)) {
                    atualizaPlanoUsuario((int)$usuario['id_usuario'], $status_mapeado, $expira_em, $id_plano_novo, !empty($sub['cancel_at_period_end']));
                    atualizaStripeUsuario((int)$usuario['id_usuario'], $customer_id, $subscription_id);

                    if (!empty($usuario['pagamento_falhou'])) {
                        atualizaPagamentoFalhouUsuario((int)$usuario['id_usuario'], false);
                    }
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
                        if ($indicacao && strtotime($indicacao['criado_em']) >= strtotime('-12 months')) {
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
        $subscription_id = $objeto['subscription'] ?? $objeto['parent']['subscription_details']['subscription'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        // falha de cobranca de assinatura antiga/orfa nao marca "pagamento pendente" em quem tem outra em dia
        if ($usuario && assinaturaPertenceAoUsuario($usuario, $subscription_id)) {
            atualizaPagamentoFalhouUsuario((int)$usuario['id_usuario'], true);

            $valor_falho = ((int) ($objeto['amount_due'] ?? $objeto['total'] ?? 0)) / 100;
            $id_plano_falho = (int) ($objeto['parent']['subscription_details']['metadata']['id_plano'] ?? 0) ?: null;
            $plano_falho = $id_plano_falho ? buscaPlanoPorId($id_plano_falho) : null;
            if ($valor_falho > 0) {
                registraPagamento([
                    'id_usuario' => (int)$usuario['id_usuario'],
                    'id_plano' => $id_plano_falho,
                    'ciclo' => $plano_falho['ciclo'] ?? 'mensal',
                    'valor' => $valor_falho,
                    'status' => 'falhou',
                    'stripe_invoice_id' => $objeto['id'] ?? null,
                ]);
            }

            $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $link = $protocolo . '://' . $_SERVER['HTTP_HOST'] . '/cliente/conta';

            $idioma_usuario = idiomaDoUsuario($usuario);
            $assunto = sprintf(traduzPara('email_pagamento_falhou_assunto', $idioma_usuario), nomeApp());
            $corpo = sprintf(traduzPara('email_pagamento_falhou_corpo', $idioma_usuario),
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
        // reembolso parcial (cortesia, ajuste) nao encerra o plano nem muda o status do pagamento;
        // so o reembolso total e tratado como desistencia
        if (empty($objeto['refunded'])) {
            error_log('Stripe: reembolso parcial do charge ' . ($objeto['id'] ?? '') . ' ignorado');
            break;
        }

        $customer_id = $objeto['customer'] ?? '';
        $invoice_id = $objeto['invoice'] ?? '';

        if (!$invoice_id && !empty($objeto['payment_intent'])) {
            $pi = stripeRequest('GET', '/v1/payment_intents/' . $objeto['payment_intent']);
            $invoice_id = $pi['payment_details']['order_reference'] ?? '';
        }

        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario && $invoice_id) {
            $pagamento = buscaPagamentoPorInvoice($invoice_id);

            // 'reembolsado' tambem entra: se o cancelamento na Stripe falhou e o webhook foi reenviado,
            // o pagamento ja esta marcado mas a assinatura ainda precisa ser encerrada
            if ($pagamento && in_array($pagamento['status'], ['pago', 'reembolsado'], true)) {
                $era_mais_recente = ehPagamentoMaisRecentePago((int)$usuario['id_usuario'], $pagamento['criado_em']);
                if ($pagamento['status'] === 'pago') {
                    marcaPagamentoReembolsado($invoice_id);
                    estornaComissaoPorPagamento((int)$pagamento['id_pagamento']);
                }

                if ($era_mais_recente && !empty($usuario['stripe_subscription_id'])) {
                    $cancelamento = cancelaAssinaturaStripe($usuario['stripe_subscription_id']);
                    if (isset($cancelamento['error']) && ($cancelamento['code'] ?? '') !== 'resource_missing') {
                        // nao zera o vinculo local: responde 500 pra Stripe reenviar o evento
                        error_log("Stripe: falha ao cancelar assinatura {$usuario['stripe_subscription_id']} apos reembolso total");
                        http_response_code(500);
                        echo json_encode(['ok' => false, 'erro' => 'Falha ao cancelar assinatura']);
                        exit;
                    }
                    atualizaPlanoUsuario((int)$usuario['id_usuario'], 'cancelado', null);
                    atualizaStripeUsuario((int)$usuario['id_usuario'], $customer_id, null);
                    enviaEmailAssinaturaEvento($usuario, 'assinatura_encerrada');
                }
            }
        }
        break;
}

http_response_code(200);
echo json_encode(['ok' => true]);
