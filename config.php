<?php
declare(strict_types=1);
/*
 * Copy this project to your PHP host. Set ADMIN_SETUP_TOKEN to a long random
 * secret in your hosting environment before visiting install.php.
 * Never leave install.php enabled after setup.
 */
define('APP_NAME', 'ANSHXOSINT MASTER');
define('DB_PATH', getenv('DB_PATH') ?: (__DIR__ . '/storage/app.sqlite'));
define('ADMIN_SETUP_TOKEN', getenv('ADMIN_SETUP_TOKEN') ?: 'CHANGE_THIS_SETUP_TOKEN_NOW');
define('API_KEY_PREFIX', 'ansh_live_');
date_default_timezone_set('UTC');
