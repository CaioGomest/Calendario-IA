<?php
require_once __DIR__ . '/../../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../../funcoes/funcoesPlanos.php';
require_once __DIR__ . '/../../funcoes/funcoesIdioma.php';

function usuarioDeTeste($campos = []) {
    return array_merge([
        'id_usuario' => 999999999,
        'plano' => 'ativo',
        'plano_expira_em' => null,
        'id_plano' => null,
        'cancelado_em' => null,
    ], $campos);
}

function dataRelativa($ajuste) {
    return date('Y-m-d H:i:s', strtotime($ajuste));
}

testar('Assinatura', 'plano ativo sem data de expiração segue ativo', function () {
    esperaVerdadeiro(planoUsuarioAtivo(usuarioDeTeste()));
});

testar('Assinatura', 'plano ativo com período futuro segue ativo', function () {
    esperaVerdadeiro(planoUsuarioAtivo(usuarioDeTeste(['plano_expira_em' => dataRelativa('+10 days')])));
});

testar('Assinatura', 'período vencido há 1h segue ativo (carência da renovação)', function () {
    esperaVerdadeiro(planoUsuarioAtivo(usuarioDeTeste(['plano_expira_em' => dataRelativa('-1 hour')])));
});

testar('Assinatura', 'período vencido além da carência não é mais ativo', function () {
    espera(planoUsuarioAtivo(usuarioDeTeste(['plano_expira_em' => dataRelativa('-' . (HORAS_CARENCIA_ASSINATURA + 1) . ' hours')])), false);
});

testar('Assinatura', 'plano cancelado nunca é ativo, mesmo com data futura', function () {
    espera(planoUsuarioAtivo(usuarioDeTeste(['plano' => 'cancelado', 'plano_expira_em' => dataRelativa('+10 days')])), false);
});

testar('Assinatura', 'trial sem plano escolhido não é ativo', function () {
    espera(planoUsuarioAtivo(usuarioDeTeste(['plano' => 'trial', 'plano_expira_em' => dataRelativa('+3 days')])), false);
});

testar('Assinatura', 'trial com plano e prazo futuro é ativo', function () {
    esperaVerdadeiro(planoUsuarioAtivo(usuarioDeTeste(['plano' => 'trial', 'id_plano' => 3, 'plano_expira_em' => dataRelativa('+3 days')])));
});

testar('Assinatura', 'cancelamentoAgendado: ativo com cancelado_em', function () {
    esperaVerdadeiro(cancelamentoAgendado(usuarioDeTeste(['cancelado_em' => dataRelativa('-1 day')])));
});

testar('Assinatura', 'cancelamentoAgendado: ativo sem cancelado_em não', function () {
    espera(cancelamentoAgendado(usuarioDeTeste()), false);
});

testar('Assinatura', 'cancelamentoAgendado: plano já cancelado não conta como agendado', function () {
    espera(cancelamentoAgendado(usuarioDeTeste(['plano' => 'cancelado', 'cancelado_em' => dataRelativa('-1 day')])), false);
});

testar('Assinatura', 'teste grátis: cadastro novo é elegível', function () {
    esperaVerdadeiro(elegivelTesteGratis(usuarioDeTeste(['plano' => 'trial'])));
});

testar('Assinatura', 'teste grátis: quem já cancelou não é elegível', function () {
    espera(elegivelTesteGratis(usuarioDeTeste(['plano' => 'cancelado', 'cancelado_em' => dataRelativa('-1 day')])), false);
});

testar('Assinatura', 'teste grátis: quem já tem plano escolhido não é elegível', function () {
    espera(elegivelTesteGratis(usuarioDeTeste(['plano' => 'trial', 'id_plano' => 3])), false);
});

testar('Assinatura', 'traduzPara respeita o idioma pedido', function () {
    espera(traduzPara('upgrade_ciclo_mensal', 'pt-BR'), '/ mês');
    espera(traduzPara('upgrade_ciclo_mensal', 'es-MX'), '/ mes');
});

testar('Assinatura', 'traduzPara com idioma inválido cai no padrão sem montar caminho', function () {
    esperaVerdadeiro(traduzPara('upgrade_ciclo_mensal', '../../config/config') !== 'upgrade_ciclo_mensal');
});

testar('Assinatura', 'preço exibido mantém os centavos e o separador do idioma', function () {
    espera(formataValorPlano(33.9, 'pt-BR'), '33,90');
    espera(formataValorPlano(68, 'pt-BR'), '68');
    espera(formataValorPlano(1234.5, 'es-MX'), '1,234.50');
});

testar('Assinatura', 'pagamento pendente: assinatura viva com cobranca que falhou', function () {
    esperaVerdadeiro(pagamentoPendente(usuarioDeTeste(['pagamento_falhou' => 1])));
});

testar('Assinatura', 'pagamento pendente: some depois do cancelamento (nao ha o que acertar)', function () {
    espera(pagamentoPendente(usuarioDeTeste(['plano' => 'cancelado', 'pagamento_falhou' => 1])), false);
});

testar('Assinatura', 'pagamento pendente: sem a marca de falha nao ha pendencia', function () {
    espera(pagamentoPendente(usuarioDeTeste(['pagamento_falhou' => 0])), false);
});
