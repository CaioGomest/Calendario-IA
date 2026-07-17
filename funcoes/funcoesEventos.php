<?php

require_once __DIR__ . '/../config/conexao.php';

function listaProximosEventos($id_usuario, $limite = 3) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM eventos WHERE id_usuario = ? AND data_inicio >= NOW() ORDER BY data_inicio ASC LIMIT ?');
    $stmt->bindValue(1, $id_usuario, PDO::PARAM_INT);
    $stmt->bindValue(2, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contaEventosSemana($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM eventos WHERE id_usuario = ? AND data_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)');
    $stmt->execute([$id_usuario]);
    return (int) $stmt->fetchColumn();
}

function contaTotalEventos() {
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM eventos')->fetchColumn();
}

function contaEventosHoje() {
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM eventos WHERE DATE(data_inicio) = CURDATE()')->fetchColumn();
}

function insereLogMensagem($dados) {
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO logs_mensagens (id_usuario, direcao, conteudo) VALUES (?, ?, ?)');
    $stmt->execute([
        $dados['id_usuario'],
        $dados['direcao'],
        $dados['conteudo'],
    ]);
    return (int) $pdo->lastInsertId();
}

function contaTotalMensagens() {
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM logs_mensagens')->fetchColumn();
}

function insereEvento($dados) {
    $pdo = conexao();
    $stmt = $pdo->prepare('INSERT INTO eventos (id_usuario, titulo, descricao, data_inicio, data_fim, id_google_event, lembrete) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $dados['id_usuario'],
        $dados['titulo'],
        $dados['descricao'] ?? null,
        $dados['data_inicio'],
        $dados['data_fim'] ?? null,
        $dados['id_google_event'] ?? null,
        isset($dados['lembrete']) ? (int) $dados['lembrete'] : 1,
    ]);
    return (int) $pdo->lastInsertId();
}

function listaEventosPendentesLembrete() {
    $pdo = conexao();
    $stmt = $pdo->query(
        "SELECT e.*, u.telefone, u.antecedencia_lembrete_min, u.modo_silencio, u.fuso_horario
         FROM eventos e
         JOIN usuarios u ON e.id_usuario = u.id_usuario
         WHERE e.lembrete = 1
           AND e.lembrete_enviado = 0
           AND e.data_inicio >= NOW()
           AND DATE_SUB(e.data_inicio, INTERVAL u.antecedencia_lembrete_min MINUTE) <= NOW()
           AND u.deletado = 0
           AND u.ativo = 1
         ORDER BY e.data_inicio ASC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function marcaLembreteEnviado($id_evento) {
    $pdo = conexao();
    $stmt = $pdo->prepare('UPDATE eventos SET lembrete_enviado = 1 WHERE id_evento = ?');
    $stmt->execute([$id_evento]);
}

function contaEventosPorDia($dias = 30) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT DATE(criado_em) AS dia, COUNT(*) AS total
         FROM eventos WHERE criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(criado_em) ORDER BY dia ASC"
    );
    $stmt->execute([$dias]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contaMensagensPorDia($dias = 30) {
    $pdo = conexao();
    $stmt = $pdo->prepare(
        "SELECT DATE(criado_em) AS dia, COUNT(*) AS total
         FROM logs_mensagens WHERE criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(criado_em) ORDER BY dia ASC"
    );
    $stmt->execute([$dias]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listaEventosRecentes($limite = 10) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT e.*, u.nome AS nome_usuario FROM eventos e JOIN usuarios u ON e.id_usuario = u.id_usuario ORDER BY e.criado_em DESC LIMIT ?');
    $stmt->bindValue(1, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listaEventosUsuario($id_usuario, $filtro = []) {
    $pdo = conexao();
    $sql = 'SELECT * FROM eventos WHERE id_usuario = ?';
    $params = [$id_usuario];

    if (!empty($filtro['data_inicio'])) {
        $sql .= ' AND data_inicio >= ?';
        $params[] = $filtro['data_inicio'];
    }
    if (!empty($filtro['data_fim'])) {
        $sql .= ' AND data_inicio <= ?';
        $params[] = $filtro['data_fim'];
    }

    $sql .= ' ORDER BY data_inicio ASC';

    if (!empty($filtro['limite'])) {
        $sql .= ' LIMIT ?';
        $stmt = $pdo->prepare($sql);
        foreach ($params as $i => $val) {
            $stmt->bindValue($i + 1, $val);
        }
        $stmt->bindValue(count($params) + 1, (int) $filtro['limite'], PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function atualizaEvento($id_evento, $dados) {
    $pdo = conexao();
    $campos = [];
    $params = [];

    $permitidos = ['titulo', 'descricao', 'data_inicio', 'data_fim', 'id_google_event', 'lembrete'];
    foreach ($permitidos as $campo) {
        if (array_key_exists($campo, $dados)) {
            $campos[] = "$campo = ?";
            $params[] = $dados[$campo];
        }
    }

    if (empty($campos)) {
        return false;
    }

    $params[] = $id_evento;
    $stmt = $pdo->prepare('UPDATE eventos SET ' . implode(', ', $campos) . ' WHERE id_evento = ?');
    $stmt->execute($params);
    return $stmt->rowCount() > 0;
}

function deletaEvento($id_evento) {
    $pdo = conexao();
    $stmt = $pdo->prepare('DELETE FROM eventos WHERE id_evento = ?');
    $stmt->execute([$id_evento]);
    return $stmt->rowCount() > 0;
}

function buscaEventoPorId($id_evento) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM eventos WHERE id_evento = ?');
    $stmt->execute([$id_evento]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscaSessaoConversa($id_usuario) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT * FROM sessoes_conversa WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function atualizaSessaoConversa($id_usuario, $contexto) {
    $pdo = conexao();
    $sessao = buscaSessaoConversa($id_usuario);

    if ($sessao) {
        $stmt = $pdo->prepare('UPDATE sessoes_conversa SET contexto = ? WHERE id_usuario = ?');
        $stmt->execute([$contexto, $id_usuario]);
        return (int) $sessao['id_sessao'];
    }

    $stmt = $pdo->prepare('INSERT INTO sessoes_conversa (id_usuario, contexto) VALUES (?, ?)');
    $stmt->execute([$id_usuario, $contexto]);
    return (int) $pdo->lastInsertId();
}
