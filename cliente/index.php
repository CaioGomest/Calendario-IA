<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';

iniciaSessao();

if (usuarioLogadoId()) {
    header('Location: home.php');
} else {
    header('Location: login.php');
}
exit;
