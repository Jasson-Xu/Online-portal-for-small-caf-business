-- One-file XAMPP setup for the Folks & Co. 2026 local demo.
-- Import this file in phpMyAdmin at server level. It creates cafe_portal, 12 products,
-- one staff account, two test customer accounts and three example orders.
-- Reimporting does not delete existing orders.


-- Source: database/xampp/01-create-database.sql
CREATE DATABASE IF NOT EXISTS cafe_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- Source: database/xampp/02-create-tables.sql
USE cafe_portal;

CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(80) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('customer','staff') NOT NULL DEFAULT 'customer',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 category VARCHAR(40) NOT NULL,
 description VARCHAR(500) NOT NULL,
 allergens VARCHAR(190) NOT NULL DEFAULT '',
 price_cents INT UNSIGNED NOT NULL,
 available TINYINT(1) NOT NULL DEFAULT 1,
 artwork VARCHAR(30) NOT NULL DEFAULT 'coffee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pickup_slots (
 slot_at DATETIME PRIMARY KEY,
 capacity SMALLINT UNSIGNED NOT NULL DEFAULT 8,
 booked SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 CHECK (booked <= capacity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reference VARCHAR(24) NOT NULL UNIQUE,
 user_id BIGINT UNSIGNED NOT NULL,
 checkout_key CHAR(64) NOT NULL UNIQUE,
 customer_name VARCHAR(80) NOT NULL,
 phone VARCHAR(24) NOT NULL,
 pickup_at DATETIME NOT NULL,
 group_name VARCHAR(80) NOT NULL DEFAULT '',
 notes VARCHAR(500) NOT NULL DEFAULT '',
 total_cents INT UNSIGNED NOT NULL,
 status ENUM('received','preparing','ready','collected','cancelled') NOT NULL DEFAULT 'received',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id),
 FOREIGN KEY (pickup_at) REFERENCES pickup_slots(slot_at),
 INDEX idx_queue (status,pickup_at),
 INDEX idx_customer (user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 menu_item_id BIGINT UNSIGNED NOT NULL,
 item_name VARCHAR(100) NOT NULL,
 unit_price_cents INT UNSIGNED NOT NULL,
 quantity SMALLINT UNSIGNED NOT NULL,
 recipient VARCHAR(60) NOT NULL DEFAULT '',
 instructions VARCHAR(160) NOT NULL DEFAULT '',
 FOREIGN KEY (order_id) REFERENCES orders(id),
 FOREIGN KEY (menu_item_id) REFERENCES menu_items(id),
 CHECK (quantity BETWEEN 1 AND 20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL UNIQUE,
 amount_cents INT UNSIGNED NOT NULL,
 method VARCHAR(30) NOT NULL DEFAULT 'simulation',
 status ENUM('simulated_paid','simulated_refunded') NOT NULL DEFAULT 'simulated_paid',
 reference VARCHAR(40) NOT NULL UNIQUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 actor_id BIGINT UNSIGNED NOT NULL,
 status VARCHAR(24) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (order_id) REFERENCES orders(id),
 FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
 identity_hash CHAR(64) PRIMARY KEY,
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 window_started DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Source: database/xampp/03-demo-data.sql
USE cafe_portal;

INSERT INTO menu_items (id,name,category,description,allergens,price_cents,artwork) VALUES
 (1,'Flat white','Coffee','A double espresso, silky steamed milk and a little morning magic.','Milk',480,'coffee'),
 (2,'Long black','Coffee','Our house espresso over hot water. Bold, balanced and beautifully simple.','',450,'black'),
 (3,'Cappuccino','Coffee','Espresso beneath a cloud of milk foam, finished with cocoa.','Milk',500,'coffee'),
 (4,'Iced latte','Cold drinks','A double shot of espresso, cold milk and plenty of ice.','Milk',600,'iced'),
 (5,'Matcha latte','Coffee','Earthy green tea whisked smooth with steamed milk.','Milk',580,'matcha'),
 (6,'Peach iced tea','Cold drinks','Black tea with a bright peach finish. Refreshing from the first sip.','',550,'tea'),
 (7,'Almond croissant','Bakery','Flaky, golden pastry filled with almond cream and toasted almonds.','Wheat, milk, eggs, almonds',650,'pastry'),
 (8,'Blueberry muffin','Bakery','A soft, buttery muffin filled with blueberries and a crunchy top.','Wheat, milk, eggs',520,'muffin'),
 (9,'Avocado toast','Kitchen','Smashed avocado, lemon and seeds on toasted sourdough.','Wheat, sesame',1450,'toast'),
 (10,'Breakfast roll','Kitchen','Egg, bacon and tomato relish tucked into a warm brioche roll.','Wheat, milk, eggs',1250,'roll'),
 (11,'Tomato & mozzarella toastie','Kitchen','Roasted tomato, mozzarella and basil on grilled sourdough.','Wheat, milk',1100,'toast'),
 (12,'Chocolate brownie','Bakery','Rich dark chocolate with a fudgy centre. A little afternoon treat.','Wheat, milk, eggs',550,'brownie')
ON DUPLICATE KEY UPDATE id=VALUES(id);

-- Local demo staff: staff@example.test / 1234567890. This is a PHP password_hash bcrypt value.
INSERT IGNORE INTO users (name,email,password_hash,role) VALUES ('Cafe staff','staff@example.test','$2y$10$/ifmWlxjY7Q2nrTDgmr4Uu7YnQjTRHR.Fcw5rAsXr7rAf/kyFjLRO','staff');
UPDATE users SET password_hash='$2y$10$/ifmWlxjY7Q2nrTDgmr4Uu7YnQjTRHR.Fcw5rAsXr7rAf/kyFjLRO' WHERE email='staff@example.test' AND role='staff';


-- Source: database/xampp/04-test-data.sql
-- Local demonstration test fixtures.
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
