<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('Command line only.');
$config = require __DIR__ . '/../config/config.php';
$email = strtolower($argv[1] ?? '');
$name = $argv[2] ?? 'Café staff';
$password = getenv('CAFE_STAFF_PASSWORD') ?: '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email)>190 || strlen($name)>80 || strlen($password)<10 || strlen($password)>72) {
    fwrite(STDERR, "Usage: set CAFE_STAFF_PASSWORD (10-72 bytes), then php scripts/create-staff.php email@example.com \"Staff name\"\n"); exit(1);
}
try {
    $pdo = new PDO("mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4", $config['db_user'], $config['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'staff')");
    $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
    echo "Staff account created.\n";
} catch (Throwable $e) { fwrite(STDERR, "Unable to create account. Confirm database settings and use an email not already registered.\n"); exit(1); }
