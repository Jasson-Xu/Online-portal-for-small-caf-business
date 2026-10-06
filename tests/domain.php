<?php
declare(strict_types=1);
require __DIR__ . '/../app/domain.php';
date_default_timezone_set('Australia/Sydney');
$passed = 0;
function check(bool $ok, string $name): void { global $passed; if (!$ok) { fwrite(STDERR,"FAIL: $name\n"); exit(1); } $passed++; echo "PASS: $name\n"; }
foreach ([1,'20',5] as $value) check(valid_quantity($value)==(int)$value,'valid quantity '.(string)$value);
foreach ([0,-1,21,'1.5','hello',null,[]] as $value) { try { valid_quantity($value); check(false,'reject quantity'); } catch (DomainException) { check(true,'reject invalid quantity'); } }
check(valid_phone('0412 345 678'),'Australian mobile');
check(valid_phone('+61 412 345 678'),'international phone');
check(!valid_phone('12345'),'short phone rejected');
check(!valid_phone('call me please'),'letters rejected');
check(!valid_phone('1234567890123456'),'long phone rejected');
$now = new DateTimeImmutable('2026-10-06 07:01:00');
$slots = pickup_options($now);
check(array_key_first($slots)==='2026-10-06 07:30:00','15 minute lead time rounds forward');
check(!isset($slots['2026-10-06 16:00:00']),'last collection before closing');
check(isset($slots['2026-10-08 15:45:00']),'third day available');
check(!isset($slots['2026-10-09 07:00:00']),'no fourth day');
check(array_key_first(pickup_options(new DateTimeImmutable('2026-10-06 16:00:00')))==='2026-10-07 07:00:00','after hours moves to tomorrow');
check(next_statuses('received')===['preparing','cancelled'],'initial transitions');
check(next_statuses('ready')===['collected'],'ready only collected');
check(next_statuses('collected')===[] && next_statuses('cancelled')===[],'terminal states locked');
echo "$passed domain checks passed.\n";
