<?php

require_once __DIR__ . '/funcoesConfiguracao.php';

function enviaEmail($para, $assunto, $corpo_html) {
    $nome_app = nomeApp();
    $remetente = buscaConfiguracao('email_remetente') ?: 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

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
