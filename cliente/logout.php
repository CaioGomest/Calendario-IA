<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';

fazLogoutCliente();
header('Location: login.php');
exit;
