<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funcoesConfiguracao.php';

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
