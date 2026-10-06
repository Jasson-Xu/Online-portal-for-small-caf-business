-- Import third in phpMyAdmin. Reimporting keeps existing orders and menu edits.
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
