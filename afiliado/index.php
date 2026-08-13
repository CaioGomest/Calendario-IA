<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';

iniciaSessao();

if (afiliadoLogadoId()) {
    header('Location: painel.php');
} else {
    header('Location: login.php');
}
exit;
