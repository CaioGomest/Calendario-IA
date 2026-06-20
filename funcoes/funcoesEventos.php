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

function contaTotalMensagens() {
    $pdo = conexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM logs_mensagens')->fetchColumn();
}

function listaEventosRecentes($limite = 10) {
    $pdo = conexao();
    $stmt = $pdo->prepare('SELECT e.*, u.nome AS nome_usuario FROM eventos e JOIN usuarios u ON e.id_usuario = u.id_usuario ORDER BY e.criado_em DESC LIMIT ?');
    $stmt->bindValue(1, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
