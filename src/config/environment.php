<?php
/*
|--------------------------------------------------------------------------
| UNIFIED ENVIRONMENT CONFIGURATION
|--------------------------------------------------------------------------
*/

define('DATABASE_HOST', getenv('DATABASE_HOST') ?: 'localhost');
define('DATABASE_PORT', filter_var(getenv('DATABASE_PORT'), FILTER_VALIDATE_INT) ?: 3306);
define('DATABASE_NAME', getenv('DATABASE_NAME') ?: 'qrcode');
define('DATABASE_USER', getenv('DATABASE_USER') ?: 'root');
define('DATABASE_PASSWORD', getenv('DATABASE_PASSWORD') ?: 'root');
define('DATABASE_PREFIX', getenv('DATABASE_PREFIX') !== false ? getenv('DATABASE_PREFIX') : 'qr_');
define('DATABASE_CHARSET', getenv('DATABASE_CHARSET') ?: 'utf8');

define('TYPE', getenv('TYPE') ?: 'local');
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost');
define('QRCODE_GENERATOR', getenv('QRCODE_GENERATOR') ?: 'external-api.qrserver.com'); // opties: external-api.qrserver.com of internal-chillerlan.qrcode

define('ALLOW_SELF_REGISTRATION', filter_var(getenv('ALLOW_SELF_REGISTRATION'), FILTER_VALIDATE_BOOLEAN));

define('MAIL_HOST', getenv('MAIL_HOST') ?: '');
define('MAIL_PORT', filter_var(getenv('MAIL_PORT'), FILTER_VALIDATE_INT) ?: 587);
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') !== false ? getenv('MAIL_ENCRYPTION') : 'tls'); // opties: tls, ssl, '' (geen)
define('MAIL_SMTP_AUTH', getenv('MAIL_SMTP_AUTH') !== false ? filter_var(getenv('MAIL_SMTP_AUTH'), FILTER_VALIDATE_BOOLEAN) : true);
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@example.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'QRForge');
