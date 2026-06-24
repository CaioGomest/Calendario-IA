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

define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: '');

define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: '');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');
