<?php

require_once __DIR__ . '/funcoesConfiguracao.php';

function traduz($chave) {
    static $textos = null;
    if ($textos === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . '/../config/config.php';

        $idioma = $_SESSION['idioma'] ?? null;
        if (!$idioma) {
            $idioma = buscaConfiguracao('idioma_padrao') ?? IDIOMA_PADRAO;
        }

        $arquivo = __DIR__ . "/../idiomas/{$idioma}.php";
        if (!file_exists($arquivo)) {
            $idioma = IDIOMA_PADRAO;
            $arquivo = __DIR__ . "/../idiomas/{$idioma}.php";
        }

        $textos = require $arquivo;
    }
    $valor = $textos[$chave] ?? $chave;
    if (strpos($valor, '%APP%') !== false) {
        $valor = str_replace('%APP%', nomeApp(), $valor);
    }
    return $valor;
}
