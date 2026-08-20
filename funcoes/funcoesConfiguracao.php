<?php

require_once __DIR__ . '/../config/conexao.php';

function garanteTabelaConfiguracoes() {
    static $criada = false;
    if ($criada) return;
    $pdo = conexao();
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        chave VARCHAR(100) PRIMARY KEY,
        valor VARCHAR(255) NOT NULL,
        atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $criada = true;
}

function listaIdiomasDisponiveis() {
    $dir = __DIR__ . '/../idiomas/';
    $idiomas = [];
    foreach (glob($dir . '*.php') as $arquivo) {
        $idiomas[] = basename($arquivo, '.php');
    }
    return $idiomas;
}

function listaCodigosPaisTelefone() {
    return [
        '55' => '🇧🇷 +55 Brasil',
        '52' => '🇲🇽 +52 México',
        '1' => '🇺🇸 +1 EUA / Canadá',
        '54' => '🇦🇷 +54 Argentina',
        '57' => '🇨🇴 +57 Colômbia',
        '56' => '🇨🇱 +56 Chile',
        '51' => '🇵🇪 +51 Peru',
        '598' => '🇺🇾 +598 Uruguai',
        '595' => '🇵🇾 +595 Paraguai',
        '351' => '🇵🇹 +351 Portugal',
        '34' => '🇪🇸 +34 Espanha',
    ];
}

function listaFusosHorariosComuns() {
    return [
        'America/Mexico_City',
        'America/Cancun',
        'America/Tijuana',
        'America/Hermosillo',
        'America/Chihuahua',
        'America/Monterrey',
        'America/Merida',
        'America/Sao_Paulo',
        'America/Fortaleza',
        'America/Manaus',
        'America/Belem',
        'America/Cuiaba',
        'America/New_York',
        'America/Chicago',
        'America/Denver',
        'America/Los_Angeles',
        'America/Bogota',
        'America/Lima',
        'America/Santiago',
        'America/Buenos_Aires',
        'Europe/Madrid',
        'Europe/Lisbon',
        'Europe/London',
        'UTC',
    ];
}

function buscaConfiguracao($chave) {
    garanteTabelaConfiguracoes();
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT valor FROM configuracoes WHERE chave = ?');
    $stmt->execute([$chave]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['valor'] : null;
}

function moedaSistema() {
    static $moeda = null;
    if ($moeda === null) {
        $moeda = buscaConfiguracao('moeda') ?? 'BRL';
    }
    return $moeda;
}

function simboloMoeda($codigo = null) {
    $mapa = ['BRL' => 'R$', 'USD' => 'US$', 'MXN' => 'MX$'];
    return $mapa[$codigo ?? moedaSistema()] ?? $codigo;
}

function nomeApp() {
    static $nome = null;
    if ($nome === null) {
        $nome = buscaConfiguracao('nome_app') ?? 'CalendarioIA';
    }
    return $nome;
}

function limiteDiarioMensagens() {
    static $limite = null;
    if ($limite === null) {
        $limite = (int) (buscaConfiguracao('limite_diario_mensagens') ?? 40);
    }
    return $limite;
}

function versaoAsset($caminho_relativo) {
    $caminho_absoluto = __DIR__ . '/../' . $caminho_relativo;
    return file_exists($caminho_absoluto) ? filemtime($caminho_absoluto) : time();
}

function linkWhatsappBot() {
    $numero = preg_replace('/\D+/', '', buscaConfiguracao('whatsapp_bot_numero') ?? '');
    return $numero !== '' ? 'https://wa.me/' . $numero : null;
}

function salvaConfiguracao($chave, $valor) {
    garanteTabelaConfiguracoes();
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
    $stmt->execute([$chave, $valor]);
}
