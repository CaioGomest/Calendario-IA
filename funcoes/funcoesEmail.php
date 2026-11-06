<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funcoesConfiguracao.php';
require_once __DIR__ . '/funcoesIdioma.php';

function leRespostaSmtp($conexao) {
    $resposta = '';
    while ($linha = fgets($conexao, 512)) {
        $resposta .= $linha;
        if (isset($linha[3]) && $linha[3] === ' ') break;
    }
    return $resposta;
}

function enviaComandoSmtp($conexao, $comando) {
    fwrite($conexao, $comando . "\r\n");
    return leRespostaSmtp($conexao);
}

function enviaEmailSmtp($para, $assunto, $corpo_html, $remetente, $nome_app) {
    $conexao = @stream_socket_client('ssl://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 15);
    if (!$conexao) return false;

    leRespostaSmtp($conexao);
    enviaComandoSmtp($conexao, 'EHLO ' . SMTP_HOST);
    enviaComandoSmtp($conexao, 'AUTH LOGIN');
    enviaComandoSmtp($conexao, base64_encode(SMTP_USER));
    $resposta = enviaComandoSmtp($conexao, base64_encode(SMTP_PASS));
    if (substr($resposta, 0, 3) !== '235') { fclose($conexao); return false; }

    $resposta = enviaComandoSmtp($conexao, 'MAIL FROM: <' . SMTP_USER . '>');
    if (substr($resposta, 0, 3) !== '250') { fclose($conexao); return false; }

    $resposta = enviaComandoSmtp($conexao, 'RCPT TO: <' . $para . '>');
    if (substr($resposta, 0, 3) !== '250') { fclose($conexao); return false; }

    $resposta = enviaComandoSmtp($conexao, 'DATA');
    if (substr($resposta, 0, 3) !== '354') { fclose($conexao); return false; }

    $cabecalhos = [
        'From: ' . $nome_app . ' <' . $remetente . '>',
        'Reply-To: ' . $remetente,
        'To: <' . $para . '>',
        'Subject: ' . $assunto,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion(),
        'Message-ID: <' . uniqid('', true) . '@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>',
    ];
    $mensagem = implode("\r\n", $cabecalhos) . "\r\n\r\n" . str_replace("\n.", "\n..", $corpo_html);

    $resposta = enviaComandoSmtp($conexao, $mensagem . "\r\n.");
    enviaComandoSmtp($conexao, 'QUIT');
    fclose($conexao);

    return substr($resposta, 0, 3) === '250';
}

function enviaEmailMail($para, $assunto, $corpo_html, $remetente, $nome_app) {
    $headers = implode("\r\n", [
        'From: ' . $nome_app . ' <' . $remetente . '>',
        'Reply-To: ' . $remetente,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion(),
        'Message-ID: <' . uniqid('', true) . '@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>',
    ]);

    return mail($para, $assunto, $corpo_html, $headers);
}

function enviaEmail($para, $assunto, $corpo_html) {
    $nome_app = nomeApp();
    $remetente = buscaConfiguracao('email_remetente') ?: 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    if (SMTP_USER && SMTP_PASS) {
        return enviaEmailSmtp($para, $assunto, $corpo_html, $remetente, $nome_app);
    }

    return enviaEmailMail($para, $assunto, $corpo_html, $remetente, $nome_app);
}

function linkClienteBase() {
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocolo . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/cliente';
}

function enviaEmailBoasVindas($usuario) {
    $link = linkClienteBase() . '/home';
    $assunto = sprintf(traduz('email_boas_vindas_assunto'), nomeApp());
    $corpo = sprintf(traduz('email_boas_vindas_corpo'), htmlspecialchars($usuario['nome']), $link, nomeApp());
    return enviaEmail($usuario['email'], $assunto, $corpo);
}

function enviaEmailAssinaturaAtiva($usuario, $plano) {
    $link = linkClienteBase() . '/conta';
    $assunto = sprintf(traduz('email_assinatura_ativa_assunto'), nomeApp());
    $corpo = sprintf(traduz('email_assinatura_ativa_corpo'), htmlspecialchars($usuario['nome']), htmlspecialchars($plano['nome']), $link, nomeApp());
    return enviaEmail($usuario['email'], $assunto, $corpo);
}

// avisos de ciclo de vida da assinatura (cancelamento, fim de teste, renovacao, autenticacao do cartao),
// no idioma da conta: textos em idiomas/*.php como email_<evento>_assunto|texto|botao
// com marcadores {nome}-style trocados por $dados (valor, data, plano...)
function enviaEmailAssinaturaEvento($usuario, $evento, $dados = []) {
    $idioma = idiomaDoUsuario($usuario);
    $link = linkClienteBase() . '/conta';
    // link vindo da Stripe (ex.: fatura hospedada para autenticar o cartao); so https
    if (!empty($dados['link']) && strpos($dados['link'], 'https://') === 0) {
        $link = $dados['link'];
    }
    unset($dados['link']);
    $troca = ['{link}' => htmlspecialchars($link), '{app}' => htmlspecialchars(nomeApp())];
    foreach ($dados as $chave => $valor) {
        $troca['{' . $chave . '}'] = htmlspecialchars((string) $valor);
    }

    $assunto = str_replace(["\r", "\n"], ' ', strtr(traduzPara("email_{$evento}_assunto", $idioma), $troca));
    $saudacao = strtr(traduzPara('email_saudacao', $idioma), ['{nome}' => htmlspecialchars($usuario['nome'])]);
    $texto = strtr(traduzPara("email_{$evento}_texto", $idioma), $troca);
    $botao = traduzPara("email_{$evento}_botao", $idioma);

    $corpo = '<div style="font-family:sans-serif;max-width:480px;margin:0 auto;padding:24px;"><h2>' . $saudacao . '</h2>'
        . $texto
        . '<p><a href="' . $troca['{link}'] . '" style="display:inline-block;padding:12px 24px;background:#3b82f6;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;">' . $botao . '</a></p>'
        . '<hr style="border:none;border-top:1px solid #eee;margin:24px 0;"><p style="font-size:12px;color:#999;">' . $troca['{app}'] . '</p></div>';

    return enviaEmail($usuario['email'], $assunto, $corpo);
}

function enviaEmailPlanoAlterado($usuario, $plano) {
    $link = linkClienteBase() . '/conta';
    $assunto = sprintf(traduz('email_plano_alterado_assunto'), nomeApp());
    $corpo = sprintf(traduz('email_plano_alterado_corpo'), htmlspecialchars($usuario['nome']), htmlspecialchars($plano['nome']), $link, nomeApp());
    return enviaEmail($usuario['email'], $assunto, $corpo);
}
