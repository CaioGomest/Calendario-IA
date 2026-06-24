<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../funcoes/funcoesStripe.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';

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
            if ($subscription_id) {
                $sub = stripeRequest('GET', '/v1/subscriptions/' . $subscription_id);
                if (!isset($sub['error']) && !empty($sub['current_period_end'])) {
                    $expira_em = date('Y-m-d H:i:s', (int)$sub['current_period_end']);
                }
            }

            atualizaPlanoUsuario((int)$usuario['id_usuario'], 'ativo', $expira_em);
            atualizaStripeUsuario((int)$usuario['id_usuario'], $customer_id, $subscription_id);
        }
        break;

    case 'customer.subscription.updated':
        $customer_id = $objeto['customer'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario && !empty($objeto['current_period_end'])) {
            $expira_em = date('Y-m-d H:i:s', (int)$objeto['current_period_end']);
            $status = $objeto['status'] ?? '';
            $plano = in_array($status, ['active', 'trialing']) ? 'ativo' : $usuario['plano'];
            atualizaPlanoUsuario((int)$usuario['id_usuario'], $plano, $expira_em);
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

    case 'invoice.payment_succeeded':
        $customer_id = $objeto['customer'] ?? '';
        $subscription_id = $objeto['subscription'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario && $subscription_id) {
            $sub = stripeRequest('GET', '/v1/subscriptions/' . $subscription_id);
            if (!isset($sub['error']) && in_array($sub['status'] ?? '', ['active', 'trialing'])) {
                $expira_em = !empty($sub['current_period_end'])
                    ? date('Y-m-d H:i:s', (int)$sub['current_period_end'])
                    : null;
                atualizaPlanoUsuario((int)$usuario['id_usuario'], 'ativo', $expira_em);
                atualizaStripeUsuario((int)$usuario['id_usuario'], $customer_id, $subscription_id);
            }
        }
        break;

    case 'invoice.payment_failed':
        $customer_id = $objeto['customer'] ?? '';
        $usuario = $customer_id ? buscaUsuarioPorStripeCustomer($customer_id) : null;

        if ($usuario) {
            error_log("Stripe: pagamento falhou para usuario {$usuario['id_usuario']} (customer {$customer_id})");
        }
        break;
}

http_response_code(200);
echo json_encode(['ok' => true]);
