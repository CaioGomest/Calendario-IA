<?php

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoesConfiguracao.php';
require_once __DIR__ . '/funcoesGoogle.php';
require_once __DIR__ . '/funcoesPlanos.php';

function fusoHorarioPadrao() {
    return buscaConfiguracao('fuso_horario_padrao') ?? 'America/Mexico_City';
}

function buscaUsuarioPorEmail($email) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? AND deletado = 0');
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function normalizaTelefone($codigo_pais, $numero_local) {
    $codigo_digitos = preg_replace('/\D+/', '', $codigo_pais);
    $numero_digitos = preg_replace('/\D+/', '', $numero_local);
    if ($codigo_digitos === '' || strlen($numero_digitos) < 8) {
        return null;
    }
    return '+' . $codigo_digitos . $numero_digitos;
}

function buscaUsuarioPorTelefone($telefone) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE telefone = ? AND deletado = 0');
    $stmt->execute([$telefone]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscaUsuarioPorId($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// a carencia (HORAS_CARENCIA_ASSINATURA) cobre a virada da renovacao: o periodo vence
// antes da Stripe terminar de processar o pagamento, e nesse intervalo o cliente
// nao pode cair em "sem plano" (a tela de pagamento criaria uma assinatura duplicada)
function planoUsuarioAtivo($usuario) {
    $plano = $usuario['plano'] ?? '';
    if ($plano === 'ativo') {
        return !assinaturaExpirada($usuario);
    }
    if ($plano === 'trial' && !empty($usuario['id_plano']) && !empty($usuario['plano_expira_em'])) {
        return !assinaturaExpirada($usuario);
    }
    return false;
}

// "pagamento pendente" so faz sentido enquanto a assinatura existe: depois do cancelamento nao ha mais
// cobranca a acertar, e a marca da cobranca que falhou fica so como historico
function pagamentoPendente($usuario) {
    return !empty($usuario["pagamento_falhou"]) && ($usuario["plano"] ?? "") !== "cancelado";
}

// cancelamento pedido pelo cliente que ainda nao venceu: plano segue ativo ate o fim do periodo pago
function cancelamentoAgendado($usuario) {
    return in_array($usuario['plano'] ?? '', ['ativo', 'trial'], true) && !empty($usuario['cancelado_em']);
}

// teste gratis so na primeira contratacao: nunca assinou, nunca cancelou, nunca pagou
function elegivelTesteGratis($usuario) {
    return ($usuario['plano'] ?? '') === 'trial'
        && empty($usuario['id_plano'])
        && empty($usuario['cancelado_em'])
        && contaPagamentosEfetuadosUsuario((int) $usuario['id_usuario']) === 0;
}

function exigeAssinaturaAtiva($usuario) {
    if (!planoUsuarioAtivo($usuario)) {
        header('Location: pago');
        exit;
    }
}

// devolve a proxima etapa nao concluida do onboarding (pago/google/whatsapp),
// ou null se a conta ja completou tudo -- usado so pra mostrar um aviso, nao
// bloqueia navegacao (diferente de exigeAssinaturaAtiva, que redireciona sempre)
function proximaEtapaOnboarding($usuario) {
    if (!planoUsuarioAtivo($usuario)) {
        return 'pago';
    }
    if (empty($usuario['token_acesso_google'])) {
        return 'google';
    }
    if (empty($usuario['telefone'])) {
        return 'whatsapp';
    }
    return null;
}

function insereUsuario($dados) {
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, fuso_horario) VALUES (?, ?, ?, ?)');
    try {
        $stmt->execute([
            $dados['nome'],
            $dados['email'],
            password_hash($dados['senha'], PASSWORD_DEFAULT),
            fusoHorarioPadrao(),
        ]);
    } catch (PDOException $e) {
        // corrida: dois cadastros com o mesmo e-mail quase ao mesmo tempo passam
        // pela checagem previa antes de qualquer um inserir -- o UNIQUE do banco
        // barra o segundo, mas sem isso vira erro fatal em vez da mensagem amigavel
        if ($e->getCode() === '23000') {
            return null;
        }
        throw $e;
    }
    return (int) $pdo->lastInsertId();
}

function atualizaPlanoUsuario($id_usuario, $plano, $plano_expira_em, $id_plano = null, $cancelamento_agendado = false) {
    $pdo = conexao();
    $cancelado_em = ($plano === 'cancelado' || $cancelamento_agendado) ? date('Y-m-d H:i:s') : null;
    if ($id_plano !== null) {
        $stmt = $pdo->prepare('UPDATE usuarios SET plano = ?, plano_expira_em = ?, cancelado_em = ?, id_plano = ? WHERE id_usuario = ?');
        $stmt->execute([$plano, $plano_expira_em, $cancelado_em, $id_plano, $id_usuario]);
    } else {
        $stmt = $pdo->prepare('UPDATE usuarios SET plano = ?, plano_expira_em = ?, cancelado_em = ? WHERE id_usuario = ?');
        $stmt->execute([$plano, $plano_expira_em, $cancelado_em, $id_usuario]);
    }
}

function atualizaTokensGoogle($id_usuario, $token_acesso, $token_refresh, $token_expira_em) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET token_acesso_google = ?, token_refresh_google = ?, token_google_expira_em = ? WHERE id_usuario = ?');
    $stmt->execute([cifraTokenGoogle($token_acesso), cifraTokenGoogle($token_refresh), $token_expira_em, $id_usuario]);
}

function atualizaTelefoneUsuario($id_usuario, $telefone) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET telefone = ? WHERE id_usuario = ?');
    $stmt->execute([$telefone, $id_usuario]);
}

function atualizaModoSilencio($id_usuario, $modo_silencio) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET modo_silencio = ? WHERE id_usuario = ?');
    $stmt->execute([$modo_silencio ? 1 : 0, $id_usuario]);
}

function atualizaIdiomaUsuario($id_usuario, $idioma) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET idioma = ? WHERE id_usuario = ?');
    $stmt->execute([$idioma, $id_usuario]);
}

function atualizaFusoHorarioUsuario($id_usuario, $fuso) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET fuso_horario = ? WHERE id_usuario = ?');
    $stmt->execute([$fuso, $id_usuario]);
}

function atualizaAntecedenciaLembrete($id_usuario, $antecedencia_min) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET antecedencia_lembrete_min = ? WHERE id_usuario = ?');
    $stmt->execute([$antecedencia_min, $id_usuario]);
}

function _condicoesUsuarios($filtro) {
    $params = [];
    $condicoes = [!empty($filtro['deletado']) ? 'u.deletado = 1' : 'u.deletado = 0'];

    if (!empty($filtro['plano'])) {
        $condicoes[] = 'u.plano = ?';
        $params[] = $filtro['plano'];
    }
    if (isset($filtro['ativo'])) {
        $condicoes[] = 'u.ativo = ?';
        $params[] = (int) $filtro['ativo'];
    }
    if (!empty($filtro['busca'])) {
        $condicoes[] = '(u.nome LIKE ? OR u.email LIKE ? OR u.telefone LIKE ?)';
        $termo = '%' . $filtro['busca'] . '%';
        $params[] = $termo;
        $params[] = $termo;
        $params[] = $termo;
    }

    return [$condicoes, $params];
}

function listaUsuarios($filtro = [], $pagina = 1, $por_pagina = 30) {
    $pdo = conexao();
    $sql = 'SELECT u.*, p.nome AS nome_plano FROM usuarios u LEFT JOIN planos p ON p.id_plano = u.id_plano';

    [$condicoes, $params] = _condicoesUsuarios($filtro);
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    $sql .= ' ORDER BY u.criado_em DESC';

    $pagina = max(1, (int) $pagina);
    $por_pagina = max(1, (int) $por_pagina);
    $sql .= ' LIMIT ' . $por_pagina . ' OFFSET ' . (($pagina - 1) * $por_pagina);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contaUsuarios($filtro = []) {
    $pdo = conexao();
    [$condicoes, $params] = _condicoesUsuarios($filtro);
    $sql = 'SELECT COUNT(*) FROM usuarios u WHERE ' . implode(' AND ', $condicoes);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function deletaUsuario($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET deletado = 1 WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
    return $stmt->rowCount() > 0;
}

function reativaUsuario($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET deletado = 0 WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
    return $stmt->rowCount() > 0;
}

function buscaUsuarioPorEmailIncluindoApagados($email) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function normalizaTelefoneLivre($telefone) {
    $digitos = preg_replace('/\D+/', '', $telefone ?? '');
    return $digitos !== '' ? '+' . $digitos : '';
}

function assinaturaExpirada($usuario, $horas_carencia = HORAS_CARENCIA_ASSINATURA) {
    if (empty($usuario['plano_expira_em'])) return false;
    $limite = (new DateTime())->modify("-{$horas_carencia} hours");
    return new DateTime($usuario['plano_expira_em']) < $limite;
}

function contaUsuariosPorPlano($horas_carencia = HORAS_CARENCIA_ASSINATURA) {
    $pdo = conexao();
    $limite = (new DateTime())->modify("-{$horas_carencia} hours")->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare(
        "SELECT
            CASE
                WHEN plano = 'ativo' AND (plano_expira_em IS NULL OR plano_expira_em >= ?) THEN 'ativo'
                WHEN plano = 'ativo' THEN 'expirado'
                ELSE plano
            END AS plano_efetivo,
            COUNT(*) AS total
         FROM usuarios WHERE deletado = 0 GROUP BY plano_efetivo"
    );
    $stmt->execute([$limite]);
    $resultado = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resultado[$row['plano_efetivo']] = (int) $row['total'];
    }
    return $resultado;
}

function contaTotalUsuarios() {
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE deletado = 0')->fetchColumn();
}

function contaUsuariosAtivos() {
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE ativo = 1 AND deletado = 0')->fetchColumn();
}

function atualizaAtivoUsuario($id_usuario, $ativo) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET ativo = ? WHERE id_usuario = ?');
    $stmt->execute([$ativo ? 1 : 0, $id_usuario]);
}

function atualizaUsuario($id_usuario, $dados) {
    $pdo = conexao();
    $campos = [];
    $params = [];

    foreach (['nome', 'email', 'telefone', 'plano', 'fuso_horario'] as $campo) {
        if (array_key_exists($campo, $dados)) {
            $campos[] = "$campo = ?";
            $params[] = $dados[$campo];
        }
    }

    if (isset($dados['plano_expira_em'])) {
        $campos[] = 'plano_expira_em = ?';
        $params[] = $dados['plano_expira_em'] ?: null;
    }

    if (isset($dados['id_plano'])) {
        $campos[] = 'id_plano = ?';
        $params[] = $dados['id_plano'] ?: null;
    }

    if (empty($campos)) return;

    $params[] = $id_usuario;
    $sql = 'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id_usuario = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function contaUsuariosNovosEsteMes() {
    $pdo = conexao();
    return (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE deletado = 0 AND criado_em >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
}

function contaUsuariosNovosEstaSemana() {
    $pdo = conexao();
    return (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE deletado = 0 AND criado_em >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)")->fetchColumn();
}

function contaUsuariosPorDia($dias = 30) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT DATE(criado_em) AS dia, COUNT(*) AS total
         FROM usuarios WHERE deletado = 0 AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(criado_em) ORDER BY dia ASC"
    );
    $stmt->execute([$dias]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contaUsuariosPorPlanoDetalhado() {
    $pdo = conexao();
    $stmt = $pdo->query(
        "SELECT plano, ativo, COUNT(*) AS total FROM usuarios WHERE deletado = 0 GROUP BY plano, ativo"
    );
    $resultado = ['trial' => 0, 'ativo' => 0, 'cancelado' => 0, 'inativos' => 0];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!(int)$row['ativo']) {
            $resultado['inativos'] += (int)$row['total'];
        } else {
            $resultado[$row['plano']] = (int)$row['total'];
        }
    }
    return $resultado;
}

function atualizaStripeUsuario($id_usuario, $stripe_customer_id, $stripe_subscription_id) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET stripe_customer_id = ?, stripe_subscription_id = ? WHERE id_usuario = ?');
    $stmt->execute([$stripe_customer_id, $stripe_subscription_id, $id_usuario]);
}

function existeAssinaturaStripeAtiva() {
    $pdo = conexao();
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE stripe_subscription_id IS NOT NULL AND deletado = 0");
    return (int) $stmt->fetchColumn() > 0;
}

function atualizaPagamentoFalhouUsuario($id_usuario, $pagamento_falhou) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET pagamento_falhou = ? WHERE id_usuario = ?');
    $stmt->execute([$pagamento_falhou ? 1 : 0, $id_usuario]);
}

function buscaUsuarioPorStripeCustomer($stripe_customer_id) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE stripe_customer_id = ? AND deletado = 0');
    $stmt->execute([$stripe_customer_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function contaCancelamentosPorDia($dias = 30) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT DATE(criado_em) AS dia, COUNT(*) AS total
         FROM usuarios WHERE plano = 'cancelado' AND deletado = 0
         AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(criado_em) ORDER BY dia ASC"
    );
    $stmt->execute([$dias]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contaCancelados() {
    $pdo = conexao();
    return (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE plano = 'cancelado' AND deletado = 0")->fetchColumn();
}

function listaNovosUsuariosRecentes($limite = 10) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT nome, email, criado_em FROM usuarios WHERE deletado = 0 ORDER BY criado_em DESC LIMIT ?');
    $stmt->bindValue(1, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listaCancelamentosRecentes($limite = 10) {
    $pdo = conexao();
    $stmt = $pdo->prepare("SELECT nome, email, cancelado_em FROM usuarios WHERE plano = 'cancelado' AND cancelado_em IS NOT NULL AND deletado = 0 ORDER BY cancelado_em DESC LIMIT ?");
    $stmt->bindValue(1, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contaCanceladosEsteMes() {
    $pdo = conexao();
    return (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE plano = 'cancelado' AND deletado = 0 AND cancelado_em >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
}

function criaTokenRecuperacao($id_usuario) {
    $pdo = conexao();
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare('UPDATE usuarios SET token_recuperacao = ?, token_recuperacao_expira = ? WHERE id_usuario = ?');
    $stmt->execute([$token, $expira, $id_usuario]);
    return $token;
}

function buscaUsuarioPorTokenRecuperacao($token) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE token_recuperacao = ? AND token_recuperacao_expira > NOW() AND deletado = 0');
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function deletaTokenRecuperacao($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET token_recuperacao = NULL, token_recuperacao_expira = NULL WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
}

function atualizaSenhaUsuario($id_usuario, $nova_senha) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id_usuario = ?');
    $stmt->execute([password_hash($nova_senha, PASSWORD_DEFAULT), $id_usuario]);
}

function insereUsuarioGoogle($nome, $email) {
    $pdo = conexao();
    $senha_random = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, fuso_horario) VALUES (?, ?, ?, ?)');
    $stmt->execute([$nome, $email, $senha_random, fusoHorarioPadrao()]);
    return (int) $pdo->lastInsertId();
}

function insereUsuarioAdmin($dados) {
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, telefone, plano, fuso_horario) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $dados['nome'],
        $dados['email'],
        password_hash($dados['senha'], PASSWORD_DEFAULT),
        $dados['telefone'] ?: null,
        $dados['plano'] ?? 'trial',
        fusoHorarioPadrao(),
    ]);
    return (int) $pdo->lastInsertId();
}
