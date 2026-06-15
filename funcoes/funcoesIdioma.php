<?php

function traduz($chave) {
    static $textos = null;
    if ($textos === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . '/../config/config.php';
        $idioma = $_SESSION['idioma'] ?? IDIOMA_PADRAO;
        $textos = require __DIR__ . "/../idiomas/{$idioma}.php";
    }
    return $textos[$chave] ?? $chave;
}
