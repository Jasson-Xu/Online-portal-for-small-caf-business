# Implementation and management guide 2026

## Purpose and release scope

This guide explains how the Folks & Co. café portal was implemented, installed, used and operated for ICT312 Assignment 2 in 2026. It also defines practical risk, service and change-management procedures for the local demonstration. The system is a working click-and-collect prototype with simulated payments. It is not connected to a real café's point-of-sale system or payment provider.

The delivered stack is HTML5, CSS3, JavaScript, PHP and MySQL-compatible MariaDB. The main entry point is `public/index.php`; PHP renders pages and validates every state-changing request. The local XAMPP installation uses one complete phpMyAdmin SQL file or `setup-xampp.cmd`. Docker is an optional alternative. Git records source changes, and the Word files under `docs/word/` form the Part B documentation set.

## Implementation structure

| Component | Implementation responsibility |
|---|---|
| `public/index.php` and `public/assets/` | Route pages, deliver HTML, CSS, JavaScript and locally stored artwork. |
| `app/views.php` and `app/staff_views.php` | Render customer and staff pages from validated data. |
| `app/actions.php` | Registration, sign-in, bag editing and transactional checkout. |
| `app/staff_actions.php` | Staff-only order state changes and catalogue edits. |
| `app/domain.php` | Quantity, phone, pickup-time and status-transition rules. |
| `app/bootstrap.php` | PDO connection, sessions, headers, escaping, CSRF and access helpers. |
| `database/` | Eight InnoDB tables, sample menu and ordered XAMPP import files. |

The pages are server-rendered, so ordinary links and forms remain usable without a JavaScript build system. JavaScript improves confirmation and duplicate-click behaviour; it is not trusted for price, role or order validation. The database schema uses foreign keys, unique keys and a slot capacity check. Product prices are integer cents, and order lines keep the product name and price at the time of purchase.

### Checkout implementation

The server checks the account, CSRF token, bag, contact details, slot and simulated-payment choice. It then starts one InnoDB transaction, locks the slot and menu rows, rejects a full slot or unavailable item, recalculates the total, and writes the order, lines, payment record and first status event. It increments the slot booking count before commit. Any exception rolls back all writes. A unique checkout key means a repeated successful request leads to the same receipt instead of creating another order.

The allowed states are `received → preparing → ready → collected`. Staff may cancel only a received or preparing order. Cancellation records the actor, marks the simulated payment refunded and releases the slot count in one transaction. Staff catalogue edits change the live menu, while historical order-line snapshots remain unchanged.

### Security and data handling

Customer registration assigns only the customer role. Staff accounts are created through setup or the staff CLI. Passwords are hashed and checked by PHP; no plaintext password is stored in the database. Login rotates the session ID and limits attempts per email over a 15-minute window. Cookies are HttpOnly and SameSite=Lax. Every POST requires a CSRF token. PDO prepared statements handle values, user text is HTML-escaped, customer order reads check ownership, and staff actions check the role on the server.

The demo staff credentials are `staff@example.test` / `1234567890`. In the XAMPP configuration, this account accepts sign-in only from the same computer. A real deployment would need HTTPS, a unique staff password, an operational privacy policy, retention rules and further security testing. The prototype asks for name, email, phone and order details only to support ordering and handover. The payment interface neither asks for nor stores card numbers or CVV values.

## Installation manual for XAMPP

These steps apply to a default Windows XAMPP installation with PHP 8.2 or newer. Use a fresh project copy and avoid overwriting an existing `htdocs\cafe` site.

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Copy the whole project to `D:\XAMPP\htdocs\cafe` on this computer (or `C:\xampp\htdocs\cafe` if XAMPP is installed there). Check that `public\index.php` exists inside the copied folder and keep the `app`, `config`, `database` and `public` folders together.
3. Open `http://localhost/phpmyadmin/`. In its Import tab, choose `database/xampp/cafe-portal-complete.sql` and click Import. This single file creates the database, eight tables, 12 products, three test accounts and three example orders. Re-importing does not delete orders.
4. Open `http://localhost/cafe/public/`. Check the menu and sign in using the test accounts listed in README. Check that browser requests to `app/` and `config/` are denied by the project-root `.htaccess`.

No configuration file is needed for standard XAMPP: local requests automatically use MySQL `root` with a blank password on port `3306`. If XAMPP's MySQL has a custom root password or port, copy `config/local.xampp.example.php` to `config/local.php` and edit `db_password` or `db_port`. This private file is ignored by Git. A public deployment needs its own restricted database account and security configuration.

If the project was previously linked by `setup-xampp.cmd`, keep that link and its working `config/local.php`; the URL is `http://localhost/cafe/`. The command remains an alternative for a fresh installation: it creates a database-scoped account, imports the schema, menu and test accounts, writes local settings and links only `public/` into `htdocs`.

For a database connection error, check that MySQL is running, that the complete SQL import finished, and that any custom `local.php` password and port match XAMPP. For a 404 page, check the copied folder and URL. If the menu is empty, repeat the complete SQL import. Add Apache's port after `localhost` when it differs from port 80. Stop Apache and MySQL in the Control Panel when the local demonstration is finished.

## User manual

### Customer tasks

1. Open Our menu and browse categories or search by name. Product cards show price, availability and allergen information; customers with allergies should confirm suitability directly with café staff.
2. Select a quantity from 1 to 20 and add an item to the bag. For a group order, expand the item form and enter an optional recipient name and preparation note. Separate lines may identify different people ordering the same item.
3. Open Bag to review the order, change a quantity or remove a line. The server recalculates the total. Register or sign in before checkout.
4. Enter the collection name and phone number, choose an offered time and optionally add a group name and note. Collection slots are in Australia/Sydney time, from 07:00 to 15:45, at least 15 minutes ahead, today or within the following two days.
5. Acknowledge that payment is simulated and choose Approved to place the demonstration order. Declined shows a failure without charging money or deleting the bag. No card details are requested.
6. Save the confirmation reference. My orders lists the customer's recent orders; open one to review its status and receipt. The customer can refresh the tracking page while staff prepare the order.

### Staff tasks

1. Sign in with the staff account and open Staff desk. Review the order reference, collection time, contact name, group labels and preparation notes before starting work.
2. Use the status controls in order: Start preparing, Mark ready, then Mark collected at handover. The server rejects skipped or stale transitions. Refresh the queue to see new orders.
3. If an order is still received or preparing and cannot be fulfilled, cancel it. The system records a simulated refund and releases its reserved slot. No real payment is returned because no real charge occurred.
4. Open Manage menu to change an existing item's name, description, category, allergens, price or availability. Mark sold-out items unavailable before customers place new orders. Old receipts retain their original item names and prices.
5. Sign out on a shared computer. Do not use the fixed demo password outside a local demonstration.

## Risk management plan

The team assesses risks at each integration review and after any incident. Likelihood and impact are rated Low, Medium or High for the demonstration context. The named owner checks the preventive control, records any trigger and coordinates the response. A risk is closed only after its control has been tested or the exposure has been accepted for the prototype.

| Risk and rating | Prevention | Trigger and response | Owner |
|---|---|---|---|
| Database outage — Medium / High | Verify MySQL at startup; keep a current export outside `public/`. | Connection error: stop new orders, check MySQL and settings, restore from a tested backup if needed. | Mandip Rijal |
| Partial or duplicate order — Low / High | One transaction, foreign keys, unique checkout key and regression tests. | Mismatched order/payment count: stop checkout, inspect logs and database, fix before reopening. | Mandip Rijal |
| Full collection slot — Medium / Medium | Row locking and eight-order capacity. | Full-slot rejection: offer another slot and verify booked count. | Mandip Rijal |
| Unauthorized order access — Medium / High | Ownership and role checks, CSRF, password hashing and private config. | Suspicious access: stop the site, preserve logs, rotate credentials and investigate affected records. | All members |
| Allergen or note misunderstanding — Medium / High | Display allergens and advise direct staff confirmation. | Unclear or unsafe request: staff contact the customer before preparation. | Jasson |
| Installation failure — Medium / High | Ordered SQL files, XAMPP guide and smoke checks. | Site fails on a new machine: check Apache, PHP, DB config, import order and source access. | Rudesh |
| Scope growth or missed handover — Medium / Medium | Protect core journeys; defer real payments, delivery and inventory. | New feature request: assess effect on Week 12 work and record a change decision. | Jasson |

The fixed demo staff password is a deliberate local-testing exposure. It is restricted to local sign-in and must be replaced before any network-facing deployment. The risk plan does not claim that a production security review or real-world performance trial has occurred.

## Service management plan

The service covered by this plan is the local demonstration and its stored test orders. During a demonstration, the operator checks that Apache and MySQL are running, opens the home page and menu, and verifies that the Staff desk is accessible. Staff refresh the queue during service, check collection times and reconcile ready or uncollected orders before closing. The operator stops local services when they are not needed.

The proposed recovery objective for the demonstration is to restore a working local copy within one hour of a failure, using a recent database export and the Git-tracked source. This is a planning target, not a measured service-level agreement. Before a demonstration and before a schema change, export the database with phpMyAdmin or `mysqldump --single-transaction --no-tablespaces`. Store the export outside `public/` and test a restore into a separate database. Retention and deletion periods for any real café must be set by the operator before live use.

| Priority | Example | First response and owner |
|---|---|---|
| Critical | Private data exposed or staff access bypassed | Stop site access, preserve evidence, rotate credentials; team leader coordinates. |
| High | Checkout cannot create orders or database unavailable | Pause ordering, inspect Apache/PHP/MySQL logs and connection settings; Mandip leads. |
| Medium | One product or slot behaves incorrectly | Mark item unavailable or offer another slot, record the issue; relevant component owner leads. |
| Low | Cosmetic layout or wording defect | Record for the next reviewed release; Jasson coordinates. |

For every incident, record detection time, affected workflow and order references, owner, actions, recovery time and prevention decision. If checkout returns an error, the customer should check My orders before retrying to avoid confusion about a submitted order. After recovery, perform a smoke test: menu loads, a test customer can reach checkout, and staff can see the resulting order. Do not expose stack traces, private logs or database credentials through the browser.

## Change management plan

Every proposed change receives a short record with its purpose, requester, owner, affected pages or tables, acceptance criteria and rollback approach. The team leader reviews the impact on data, privacy, security, schedule and documentation. A routine catalogue edit can be performed through Manage menu; a code or schema change needs review and tests before release.

| Change class | Examples | Minimum control |
|---|---|---|
| Routine content | Price, description, allergen or availability edit | Staff checks the result in the menu and records the reason. |
| Normal code change | Page, validation or workflow change | Branch or separate commit, teammate review, relevant tests and smoke check. |
| Database change | Table or constraint update | Back up first, test migration and restore in a separate database, then schedule installation. |
| Emergency correction | Ordering or privacy defect during demonstration | Limit the immediate change, verify the failing path and full checkout, document the decision afterward. |

The release sequence is: log the request; assess effects; agree the acceptance test; implement and review; run relevant automated and browser checks; update the Word and Markdown guides; commit with a meaningful message; and install on XAMPP. If a code release fails, revert the reviewed commit and rerun the smoke test. Reverting Git does not undo database changes, so a schema rollback requires its tested reverse migration or a deliberate restore decision. Keep source authorship and commit times as recorded by version control.

## Handover checks

The Part B handover consists of all source folders, the complete XAMPP SQL file, the three Word documents, and the test evidence under `docs/evidence/`. On the marker's computer, verify the install steps, menu, registration, simulated checkout, order tracking, staff queue and status update. The individual reflection and any tutor discussion record are separate assessment items.

## References

- ICT312 Assignment 2 brief and Proposal-1 supplied with the project.
- [System design](DESIGN.md), [test plan and results](TEST_REPORT.md), and [2026 team plan](TEAM_PLAN.md).
- [PHP password hashing](https://www.php.net/manual/en/function.password-hash.php), [PDO prepared statements](https://www.php.net/manual/en/pdo.prepared-statements.php), and [MySQL locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html).
