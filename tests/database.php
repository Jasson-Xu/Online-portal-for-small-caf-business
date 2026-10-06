<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4",$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$checks = [
 'No plaintext password records' => "SELECT COUNT(*) FROM users WHERE password_hash NOT LIKE '\$2y\$%' AND password_hash NOT LIKE '\$argon2%'",
 'All order lines reference existing orders and products' => 'SELECT COUNT(*) FROM order_items i LEFT JOIN orders o ON o.id=i.order_id LEFT JOIN menu_items m ON m.id=i.menu_item_id WHERE o.id IS NULL OR m.id IS NULL',
 'Receipt totals match line snapshots' => 'SELECT COUNT(*) FROM orders o WHERE o.total_cents <> (SELECT SUM(i.unit_price_cents*i.quantity) FROM order_items i WHERE i.order_id=o.id)',
 'Exactly one matching payment per order' => 'SELECT COUNT(*) FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE p.id IS NULL OR p.amount_cents<>o.total_cents',
 'Cancelled orders have simulated refunds' => "SELECT COUNT(*) FROM orders o JOIN payments p ON p.order_id=o.id WHERE (o.status='cancelled' AND p.status<>'simulated_refunded') OR (o.status<>'cancelled' AND p.status<>'simulated_paid')",
 'Booked slots match non-cancelled orders' => "SELECT COUNT(*) FROM pickup_slots s WHERE s.booked<>(SELECT COUNT(*) FROM orders o WHERE o.pickup_at=s.slot_at AND o.status<>'cancelled')",
 'Every order has its initial audit event' => "SELECT COUNT(*) FROM orders o WHERE NOT EXISTS (SELECT 1 FROM order_events e WHERE e.order_id=o.id AND e.status='received')",
 'No card number or security code columns' => "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND (column_name LIKE '%card%' OR column_name LIKE '%cvv%')",
];
foreach ($checks as $name=>$sql) { if ((int)$pdo->query($sql)->fetchColumn()!==0) { fwrite(STDERR,"FAIL: $name\n");exit(1); } echo "PASS: $name\n"; }
echo count($checks)." database integrity checks passed.\n";
