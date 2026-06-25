<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';

iniciaSessao();

if (adminLogadoId()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
