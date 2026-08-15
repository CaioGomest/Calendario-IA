<?php
require_once __DIR__ . '/funcoes/funcoesAuth.php';

iniciaSessao();

if (usuarioLogadoId()) {
    header('Location: cliente/home');
} else {
    header('Location: landpage');
}
exit;
