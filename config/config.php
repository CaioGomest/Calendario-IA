<?php

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'calendarioia');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('INTERNAL_SECRET', getenv('INTERNAL_SECRET') ?: '');

// em 'dev', cadastro/pagamento e conexões Google/WhatsApp são simulados, sem validação real
define('APP_ENV', getenv('APP_ENV') ?: 'dev');
define('MODO_DEV', APP_ENV === 'dev');

define('IDIOMA_PADRAO', getenv('IDIOMA_PADRAO') ?: 'pt-BR');
