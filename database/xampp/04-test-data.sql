-- Local demonstration test fixtures. Import after the schema and demo menu.
-- Reimporting resets only the two test customer passwords and keeps existing orders.
USE cafe_portal;

-- Both customer accounts use password 1234567890 (PHP bcrypt hash).
INSERT INTO users (name,email,password_hash,role) VALUES
 ('Test customer one','test.customer1@example.test','$2y$10$/ifmWlxjY7Q2nrTDgmr4Uu7YnQjTRHR.Fcw5rAsXr7rAf/kyFjLRO','customer'),
 ('Test customer two','test.customer2@example.test','$2y$10$/ifmWlxjY7Q2nrTDgmr4Uu7YnQjTRHR.Fcw5rAsXr7rAf/kyFjLRO','customer')
ON DUPLICATE KEY UPDATE name=VALUES(name),password_hash=VALUES(password_hash),role='customer';

-- Tomorrow's slots make the received and ready examples visible in the staff queue.
SET @test_received_slot = CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 08:00:00');
SET @test_ready_slot = CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 09:00:00');
SET @test_cancelled_slot = CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 10:00:00');
INSERT INTO pickup_slots (slot_at,capacity,booked) VALUES
 (@test_received_slot,8,0),(@test_ready_slot,8,0),(@test_cancelled_slot,8,0)
ON DUPLICATE KEY UPDATE slot_at=VALUES(slot_at);

INSERT IGNORE INTO orders (reference,user_id,checkout_key,customer_name,phone,pickup_at,group_name,notes,total_cents,status)
SELECT 'TEST-RECEIVED-001',u.id,SHA2('cafe-fixture-received-001',256),u.name,'0412345678',@test_received_slot,'Team breakfast','Please label both coffees.',960,'received'
FROM users u WHERE u.email='test.customer1@example.test';
INSERT IGNORE INTO orders (reference,user_id,checkout_key,customer_name,phone,pickup_at,group_name,notes,total_cents,status)
SELECT 'TEST-READY-001',u.id,SHA2('cafe-fixture-ready-001',256),u.name,'0412345679',@test_ready_slot,'','No chilli, please.',1450,'ready'
FROM users u WHERE u.email='test.customer2@example.test';
INSERT IGNORE INTO orders (reference,user_id,checkout_key,customer_name,phone,pickup_at,group_name,notes,total_cents,status)
SELECT 'TEST-CANCELLED-001',u.id,SHA2('cafe-fixture-cancelled-001',256),u.name,'0412345678',@test_cancelled_slot,'','',500,'cancelled'
FROM users u WHERE u.email='test.customer1@example.test';

INSERT INTO order_items (order_id,menu_item_id,item_name,unit_price_cents,quantity,recipient,instructions)
SELECT o.id,1,'Flat white',480,2,'Mandip','No sugar' FROM orders o
WHERE o.reference='TEST-RECEIVED-001' AND NOT EXISTS (SELECT 1 FROM order_items i WHERE i.order_id=o.id);
INSERT INTO order_items (order_id,menu_item_id,item_name,unit_price_cents,quantity,recipient,instructions)
SELECT o.id,9,'Avocado toast',1450,1,'','No chilli, please.' FROM orders o
WHERE o.reference='TEST-READY-001' AND NOT EXISTS (SELECT 1 FROM order_items i WHERE i.order_id=o.id);
INSERT INTO order_items (order_id,menu_item_id,item_name,unit_price_cents,quantity,recipient,instructions)
SELECT o.id,3,'Cappuccino',500,1,'','' FROM orders o
WHERE o.reference='TEST-CANCELLED-001' AND NOT EXISTS (SELECT 1 FROM order_items i WHERE i.order_id=o.id);

INSERT IGNORE INTO payments (order_id,amount_cents,method,status,reference)
SELECT o.id,o.total_cents,'simulation','simulated_paid',CONCAT('SIM-',o.reference)
FROM orders o WHERE o.reference IN ('TEST-RECEIVED-001','TEST-READY-001');
INSERT IGNORE INTO payments (order_id,amount_cents,method,status,reference)
SELECT o.id,o.total_cents,'simulation','simulated_refunded',CONCAT('SIM-',o.reference)
FROM orders o WHERE o.reference='TEST-CANCELLED-001';

INSERT INTO order_events (order_id,actor_id,status)
SELECT o.id,o.user_id,'received' FROM orders o
WHERE o.reference IN ('TEST-RECEIVED-001','TEST-READY-001','TEST-CANCELLED-001')
AND NOT EXISTS (SELECT 1 FROM order_events e WHERE e.order_id=o.id AND e.status='received');
INSERT INTO order_events (order_id,actor_id,status)
SELECT o.id,s.id,'preparing' FROM orders o JOIN users s ON s.email='staff@example.test' AND s.role='staff'
WHERE o.reference='TEST-READY-001'
AND NOT EXISTS (SELECT 1 FROM order_events e WHERE e.order_id=o.id AND e.status='preparing');
INSERT INTO order_events (order_id,actor_id,status)
SELECT o.id,s.id,'ready' FROM orders o JOIN users s ON s.email='staff@example.test' AND s.role='staff'
WHERE o.reference='TEST-READY-001'
AND NOT EXISTS (SELECT 1 FROM order_events e WHERE e.order_id=o.id AND e.status='ready');
INSERT INTO order_events (order_id,actor_id,status)
SELECT o.id,s.id,'cancelled' FROM orders o JOIN users s ON s.email='staff@example.test' AND s.role='staff'
WHERE o.reference='TEST-CANCELLED-001'
AND NOT EXISTS (SELECT 1 FROM order_events e WHERE e.order_id=o.id AND e.status='cancelled');

-- Include any other orders already occupying these slots when restoring counts.
UPDATE pickup_slots s SET booked=(SELECT COUNT(*) FROM orders o WHERE o.pickup_at=s.slot_at AND o.status<>'cancelled')
WHERE s.slot_at IN (@test_received_slot,@test_ready_slot,@test_cancelled_slot);
