<?php
require_once __DIR__ . '/funcoes/funcoesAuth.php';

iniciaSessao();

if (usuarioLogadoId()) {
    header('Location: cliente/home.php');
} else {
    header('Location: landpage.php');
}
exit;
