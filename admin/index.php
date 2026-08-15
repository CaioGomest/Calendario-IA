<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';

iniciaSessao();

if (adminLogadoId()) {
    header('Location: dashboard');
} else {
    header('Location: login');
}
exit;
