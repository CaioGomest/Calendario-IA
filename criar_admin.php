<?php
require_once __DIR__ . '/config/conexao.php';

$nome = 'Admin';
$email = 'admin@calendarioia.com';
$senha = 'admin123';

$pdo = conexao();

$stmt = $pdo->prepare('SELECT id_admin FROM administradores WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo "Admin já existe com este e-mail.";
    exit;
}

$stmt = $pdo->prepare('INSERT INTO administradores (nome, email, senha_hash) VALUES (?, ?, ?)');
$stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);

echo "Admin criado com sucesso! E-mail: $email / Senha: $senha";
echo "<br><br><b>APAGUE ESTE ARQUIVO DEPOIS.</b>";
