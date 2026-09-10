<?php

require_once __DIR__ . '/funcoesConfiguracao.php';

function idiomaAtual() {
    static $idioma = null;
    if ($idioma === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . '/../config/config.php';

        $idioma = $_SESSION['idioma'] ?? ($_COOKIE['idioma'] ?? null);
        if (!$idioma) {
            $idioma = buscaConfiguracao('idioma_padrao') ?? IDIOMA_PADRAO;
        }
        if (!file_exists(__DIR__ . "/../idiomas/{$idioma}.php")) {
            $idioma = IDIOMA_PADRAO;
        }
    }
    return $idioma;
}

function traduz($chave) {
    static $textos = null;
    if ($textos === null) {
        $textos = require __DIR__ . '/../idiomas/' . idiomaAtual() . '.php';
    }
    $valor = $textos[$chave] ?? $chave;
    if (strpos($valor, '%APP%') !== false) {
        $valor = str_replace('%APP%', nomeApp(), $valor);
    }
    return $valor;
}

function mesAbreviado($mes_numero) {
    $lista = explode(',', traduz('meses_abrev'));
    return $lista[$mes_numero - 1] ?? '';
}
