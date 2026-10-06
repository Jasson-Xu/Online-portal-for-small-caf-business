<?php
declare(strict_types=1);
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['timezone']);
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
session_name('cafe_session');
session_set_cookie_params(['httponly' => true, 'secure' => $config['session_secure'], 'samesite' => 'Lax', 'path' => '/']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
header('Cache-Control: no-store');
require __DIR__ . '/domain.php';

function db(): PDO {
    static $pdo;
    global $config;
    if (!$pdo) {
        $pdo = new PDO("mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4", $config['db_user'], $config['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}
function query(string $sql, array $params = []): PDOStatement { $stmt = db()->prepare($sql); $stmt->execute($params); return $stmt; }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(int $cents): string { return '$' . number_format($cents / 100, 2); }
function url(string $page, array $params = []): string { return 'index.php?' . http_build_query(['page' => $page] + $params); }
function redirect(string $page, array $params = []): never { header('Location: ' . url($page, $params), true, 303); exit; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">'; }
function current_user(): ?array { return empty($_SESSION['user_id']) ? null : (query('SELECT id,name,email,role FROM users WHERE id=?', [$_SESSION['user_id']])->fetch() ?: null); }
function require_user(bool $staff = false): array {
    $user = current_user();
    if (!$user) { $_SESSION['flash'] = 'Please sign in to continue.'; redirect('login'); }
    if ($staff && $user['role'] !== 'staff') { http_response_code(403); throw new RuntimeException('This page is for café staff.'); }
    return $user;
}
function text_input(string $key, int $max, bool $required = false): string {
    $value = $_POST[$key] ?? '';
    if (!is_string($value) || !preg_match('//u', $value) || strlen(trim($value)) > $max || ($required && trim($value) === '')) throw new DomainException('Please check the ' . str_replace('_', ' ', $key) . ' field.');
    return trim($value);
}
function cart(): array { return $_SESSION['cart'] ?? []; }
function cart_count(): int { return array_sum(array_column(cart(), 'quantity')); }
function cart_details(): array {
    $result = [];
    foreach (cart() as $key => $line) {
        $item = query('SELECT * FROM menu_items WHERE id=?', [$line['item_id']])->fetch();
        if ($item) $result[] = $line + ['key' => $key, 'item' => $item, 'subtotal' => (int)$item['price_cents'] * $line['quantity']];
    }
    return $result;
}
function checkout_key(): string { return $_SESSION['checkout_key'] ??= bin2hex(random_bytes(32)); }
function order_for_user(string $reference, array $user): array {
    $order = query('SELECT o.*,p.status AS payment_status,p.reference AS payment_reference FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.reference=? AND o.user_id=?', [$reference, $user['id']])->fetch();
    if (!$order) { http_response_code(404); throw new RuntimeException('Order not found in your account.'); }
    return $order;
}
