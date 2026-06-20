<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';

fazLogoutAdmin();
header('Location: login.php');
exit;
