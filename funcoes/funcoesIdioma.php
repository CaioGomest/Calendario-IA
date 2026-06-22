<?php

function traduz($chave) {
    static $textos = null;
    if ($textos === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . '/../config/config.php';

        $idioma = $_SESSION['idioma'] ?? null;
        if (!$idioma) {
            require_once __DIR__ . '/funcoesConfiguracao.php';
            $idioma = buscaConfiguracao('idioma_padrao') ?? IDIOMA_PADRAO;
        }

        $arquivo = __DIR__ . "/../idiomas/{$idioma}.php";
        if (!file_exists($arquivo)) {
            $idioma = IDIOMA_PADRAO;
            $arquivo = __DIR__ . "/../idiomas/{$idioma}.php";
        }

        $textos = require $arquivo;
    }
    return $textos[$chave] ?? $chave;
}
