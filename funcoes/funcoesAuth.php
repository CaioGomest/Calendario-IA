<?php

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoesConfiguracao.php';

// funcoesAuth.php e requerido por praticamente toda pagina do sistema (admin,
// cliente, afiliado, index, api/internal), entao e o lugar central pra headers de
// seguranca que valem pro site inteiro -- sem precisar repetir em cada arquivo.
if (!headers_sent()) {
    header_remove('X-Powered-By');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function validaSecretInterno() {
    $secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    return $secret !== '' && $secret === INTERNAL_SECRET;
}

function verificaRecaptcha($token, $acao_esperada, $limite = 0.5) {
    if (MODO_DEV || !RECAPTCHA_SECRET_KEY) return true;
    if (!$token) return false;

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => RECAPTCHA_SECRET_KEY, 'response' => $token]),
        CURLOPT_TIMEOUT => 10,
    ]);
    $dados = json_decode(curl_exec($ch), true) ?: [];
    curl_close($ch);

    return !empty($dados['success'])
        && ($dados['action'] ?? '') === $acao_esperada
        && ($dados['score'] ?? 0) >= $limite;
}

function iniciaSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function encerraCookieSessao() {
    $parametros = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
}

function loginBloqueado($identificador, $tipo) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT bloqueado_ate FROM login_tentativas WHERE identificador = ? AND tipo = ?');
    $stmt->execute([$identificador, $tipo]);
    $bloqueado_ate = $stmt->fetchColumn();
    return $bloqueado_ate && strtotime($bloqueado_ate) > time();
}

function registraTentativaFalha($identificador, $tipo, $limite = 5, $bloqueio_minutos = 15) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT tentativas, bloqueado_ate FROM login_tentativas WHERE identificador = ? AND tipo = ?');
    $stmt->execute([$identificador, $tipo]);
    $linha = $stmt->fetch(PDO::FETCH_ASSOC);

    $bloqueio_expirou = $linha && $linha['bloqueado_ate'] && strtotime($linha['bloqueado_ate']) <= time();
    $tentativas = ($linha && !$bloqueio_expirou) ? (int) $linha['tentativas'] + 1 : 1;
    $bloqueado_ate = $tentativas >= $limite ? date('Y-m-d H:i:s', time() + $bloqueio_minutos * 60) : null;

    $stmt = $pdo->prepare(
        'INSERT INTO login_tentativas (identificador, tipo, tentativas, bloqueado_ate) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE tentativas = VALUES(tentativas), bloqueado_ate = VALUES(bloqueado_ate)'
    );
    $stmt->execute([$identificador, $tipo, $tentativas, $bloqueado_ate]);
}

function limpaTentativasFalha($identificador, $tipo) {
    $pdo = conexao();
    $stmt = $pdo->prepare('DELETE FROM login_tentativas WHERE identificador = ? AND tipo = ?');
    $stmt->execute([$identificador, $tipo]);
}

function fazLoginCliente($usuario) {
    iniciaSessao();
    session_regenerate_id(true);
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nome'] = $usuario['nome'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['idioma'] = $usuario['idioma'] ?? null ?: (buscaConfiguracao('idioma_padrao') ?? IDIOMA_PADRAO);
}

function fazLogoutCliente() {
    iniciaSessao();
    $_SESSION = [];
    encerraCookieSessao();
    session_destroy();
}

function usuarioLogadoId() {
    iniciaSessao();
    return $_SESSION['id_usuario'] ?? null;
}

function exigeLoginCliente() {
    if (!usuarioLogadoId()) {
        header('Location: login');
        exit;
    }
}

function fazLoginAdmin($admin) {
    iniciaSessao();
    session_regenerate_id(true);
    $_SESSION['id_admin'] = $admin['id_admin'];
    $_SESSION['nome_admin'] = $admin['nome'];
    $_SESSION['email_admin'] = $admin['email'];
}

function fazLogoutAdmin() {
    iniciaSessao();
    $_SESSION = [];
    encerraCookieSessao();
    session_destroy();
}

function adminLogadoId() {
    iniciaSessao();
    return $_SESSION['id_admin'] ?? null;
}

function exigeLoginAdmin() {
    if (adminLogadoId() === null) {
        header('Location: login');
        exit;
    }
}

function fazLoginAfiliado($afiliado) {
    iniciaSessao();
    session_regenerate_id(true);
    $_SESSION['id_afiliado'] = $afiliado['id_afiliado'];
    $_SESSION['nome_afiliado'] = $afiliado['nome'];
    $_SESSION['email_afiliado'] = $afiliado['email'];
}

function fazLogoutAfiliado() {
    iniciaSessao();
    $_SESSION = [];
    encerraCookieSessao();
    session_destroy();
}

function afiliadoLogadoId() {
    iniciaSessao();
    return $_SESSION['id_afiliado'] ?? null;
}

function exigeLoginAfiliado() {
    $id_afiliado = afiliadoLogadoId();
    if ($id_afiliado === null) {
        header('Location: login');
        exit;
    }

    // reconfere "ativo" a cada requisicao (nao so no login) -- sem isso, um
    // afiliado desativado pelo admin (ex: por fraude) continua com acesso total,
    // saque incluso, ate a sessao expirar sozinha
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT ativo FROM afiliados WHERE id_afiliado = ?');
    $stmt->execute([$id_afiliado]);
    $ativo = $stmt->fetchColumn();
    if ($ativo === false || !(int) $ativo) {
        fazLogoutAfiliado();
        header('Location: login');
        exit;
    }
}

function buscaAdminPorEmail($email) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM administradores WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscaAdminPorId($id_admin) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM administradores WHERE id_admin = ?');
    $stmt->execute([$id_admin]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
