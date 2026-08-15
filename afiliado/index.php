<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';

iniciaSessao();

if (afiliadoLogadoId()) {
    header('Location: painel');
} else {
    header('Location: login');
}
exit;
