<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';

iniciaSessao();

if (usuarioLogadoId()) {
    header('Location: home');
} else {
    header('Location: login');
}
exit;
