<?php
// CLI-only failure injection for a DISPOSABLE test database.
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || getenv('CAFE_FAULT_TEST') !== '1') exit(1);
$c=require __DIR__.'/../config/config.php';
// Optional administrator credentials are restricted to this CLI test fixture.
$c['db_user']=getenv('TEST_DB_ADMIN_USER') ?: $c['db_user'];
$c['db_password']=getenv('TEST_DB_ADMIN_PASSWORD') ?: $c['db_password'];
$db=new PDO("mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4",$c['db_user'],$c['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$action=$argv[1]??'';
if ($action==='failure-on') $db->exec("CREATE TRIGGER cafe_test_payment_failure BEFORE INSERT ON payments FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Injected test payment storage failure'");
elseif ($action==='failure-off') $db->exec('DROP TRIGGER IF EXISTS cafe_test_payment_failure');
elseif ($action==='full-slot') { $s=$db->prepare('INSERT INTO pickup_slots(slot_at,capacity,booked) VALUES (?,0,0) ON DUPLICATE KEY UPDATE capacity=booked');$s->execute([$argv[2]]); }
elseif ($action==='restore-slot') { $s=$db->prepare('UPDATE pickup_slots SET capacity=8 WHERE slot_at=?');$s->execute([$argv[2]]); }
elseif ($action==='snapshot') echo json_encode($db->query('SELECT (SELECT COUNT(*) FROM orders) AS orders_count,(SELECT COUNT(*) FROM order_items) AS items_count,(SELECT COUNT(*) FROM payments) AS payments_count,(SELECT COUNT(*) FROM order_events) AS events_count,(SELECT COALESCE(SUM(booked),0) FROM pickup_slots) AS booked')->fetch(PDO::FETCH_ASSOC));
else exit(1);
