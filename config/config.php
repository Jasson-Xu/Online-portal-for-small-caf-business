<?php
declare(strict_types=1);
$config = [
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_port' => getenv('DB_PORT') ?: '3306',
    'db_name' => getenv('DB_NAME') ?: 'cafe_portal',
    'db_user' => getenv('DB_USER') ?: 'cafe_app',
    'db_password' => getenv('DB_PASSWORD') ?: '',
    'timezone' => 'Australia/Sydney',
    'session_secure' => getenv('SESSION_SECURE') === '1',
    'demo_staff_local_only' => false,
];
if (is_file(__DIR__ . '/local.php')) {
    $config = array_replace($config, require __DIR__ . '/local.php');
}
return $config;
