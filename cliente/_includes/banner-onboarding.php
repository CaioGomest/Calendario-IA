<?php
// espera $usuario ja no escopo de quem incluir este arquivo (home.php/conta.php/financas.php)
$proxima_etapa_onboarding = proximaEtapaOnboarding($usuario);
if ($proxima_etapa_onboarding):
    $rotulos_etapa_onboarding = [
        'pago' => traduz('onboarding_pendente_pago'),
        'google' => traduz('onboarding_pendente_google'),
        'whatsapp' => traduz('onboarding_pendente_whatsapp'),
    ];
    $botao_etapa_onboarding = traduz('onboarding_pendente_botao');
    $destino_etapa_onboarding = $proxima_etapa_onboarding;
    $exibir_banner_onboarding = true;

    // "continuar cadastro" so serve a quem ainda esta se cadastrando; os outros casos de "sem plano ativo" tem causa propria
    if ($proxima_etapa_onboarding === 'pago') {
        if (($usuario['plano'] ?? '') === 'cancelado') {
            // quem ja teve plano e cancelou precisa reativar
            $rotulos_etapa_onboarding['pago'] = traduz('onboarding_plano_cancelado');
            $botao_etapa_onboarding = traduz('upgrade_botao');
        } elseif (pagamentoPendente($usuario)) {
            // renovacao nao paga: a home ja mostra o alerta vermelho, nas outras paginas o aviso aponta pro cartao
            $exibir_banner_onboarding = empty($pagamento_falhou);
            $rotulos_etapa_onboarding['pago'] = traduz('onboarding_pagamento_pendente');
            $botao_etapa_onboarding = traduz('home_alerta_pagamento_botao');
            $destino_etapa_onboarding = 'conta?abrir=cartao';
        }
    }
    if ($exibir_banner_onboarding):
?>
<div class="cartao" style="display:flex;align-items:center;gap:10px;justify-content:space-between;flex-wrap:wrap;margin-bottom:14px;">
  <span>⚠️ <?= htmlspecialchars($rotulos_etapa_onboarding[$proxima_etapa_onboarding] ?? '') ?></span>
  <a href="<?= htmlspecialchars($destino_etapa_onboarding) ?>" class="botao botao-pequeno botao-primario"><?= htmlspecialchars($botao_etapa_onboarding) ?></a>
</div>
<?php endif; endif; ?>
