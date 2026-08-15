<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';

fazLogoutAfiliado();
header('Location: login');
exit;
