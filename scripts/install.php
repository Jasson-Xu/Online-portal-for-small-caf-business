<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('Command line only.');
$config = require __DIR__ . '/../config/config.php';
try {
    $pdo = new PDO("mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4", $config['db_user'], $config['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    foreach (['schema','seed'] as $file) $pdo->exec(file_get_contents(__DIR__ . "/../database/$file.sql"));
    echo "Schema and sample menu installed. Existing orders and accounts retained.\n";
} catch (Throwable $e) { fwrite(STDERR, "Installation failed: {$e->getMessage()}\n"); exit(1); }
