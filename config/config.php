<?php
declare(strict_types=1);
$localConfig = __DIR__ . '/local.php';
$databaseEnvSet = false;
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $name) {
    if (getenv($name) !== false) {
        $databaseEnvSet = true;
        break;
    }
}
$localBrowser = PHP_SAPI === 'cli' || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$defaultXampp = PHP_OS_FAMILY === 'Windows' && $localBrowser && !$databaseEnvSet && !is_file($localConfig);
$config = [
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_port' => getenv('DB_PORT') ?: '3306',
    'db_name' => getenv('DB_NAME') ?: 'cafe_portal',
    'db_user' => getenv('DB_USER') ?: ($defaultXampp ? 'root' : 'cafe_app'),
    'db_password' => getenv('DB_PASSWORD') ?: '',
    'timezone' => 'Australia/Sydney',
    'session_secure' => getenv('SESSION_SECURE') === '1',
    'demo_staff_local_only' => $defaultXampp,
];
if (is_file($localConfig)) {
    $config = array_replace($config, require $localConfig);
}
return $config;
