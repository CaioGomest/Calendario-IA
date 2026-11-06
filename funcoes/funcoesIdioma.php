<?php

require_once __DIR__ . '/funcoesConfiguracao.php';

function idiomaAtual() {
    static $idioma = null;
    if ($idioma === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . '/../config/config.php';

        // o painel admin sempre usa o idioma padrao escolhido em Configuracao: sessao e cookie
        // guardam a escolha de cliente/visitante e deixavam o admin em outro idioma
        $na_area_admin = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false;
        $idioma = $na_area_admin ? null : ($_SESSION['idioma'] ?? ($_COOKIE['idioma'] ?? null));
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

// idioma da conta; quem ainda nao escolheu usa o padrao configurado pelo admin
function idiomaDoUsuario($usuario) {
    require_once __DIR__ . '/../config/config.php';
    return !empty($usuario['idioma']) ? $usuario['idioma'] : (buscaConfiguracao('idioma_padrao') ?? IDIOMA_PADRAO);
}

// texto em um idioma especifico (e-mails disparados pelo webhook, sem sessao do usuario);
// idioma fora da lista de arquivos existentes cai no padrao, nunca monta caminho com entrada crua
function traduzPara($chave, $idioma) {
    static $cache = [];
    require_once __DIR__ . '/../config/config.php';
    if (!in_array($idioma, listaIdiomasDisponiveis(), true)) {
        $idioma = buscaConfiguracao('idioma_padrao') ?? IDIOMA_PADRAO;
        if (!in_array($idioma, listaIdiomasDisponiveis(), true)) {
            $idioma = IDIOMA_PADRAO;
        }
    }
    if (!isset($cache[$idioma])) {
        $cache[$idioma] = require __DIR__ . '/../idiomas/' . $idioma . '.php';
    }
    $valor = $cache[$idioma][$chave] ?? $chave;
    return str_replace('%APP%', nomeApp(), $valor);
}

function mesAbreviado($mes_numero) {
    $lista = explode(',', traduz('meses_abrev'));
    return $lista[$mes_numero - 1] ?? '';
}
