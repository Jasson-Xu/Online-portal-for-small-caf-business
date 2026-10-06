# Folks & Co. café ordering portal

A complete PHP and MySQL click-and-collect student project for ICT312 Assignment 2, based on Proposal-1. The English interface covers individual and group orders, simulated payments, collection times, private order tracking and a staff desk. Folks & Co. is demonstration branding; it is not an official Folks Gallery service.

## Requirements

- PHP 8.2 or newer, with PDO MySQL and sessions enabled.
- MySQL 8.0 or newer, using InnoDB and utf8mb4.
- A modern browser. Node.js 20+ is needed only for integration tests.
- No Composer, npm build, internet assets or framework is required to run the app.

## Local installation with WAMP or XAMPP

1. Start MySQL. Create a dedicated database and local database account using an administrator connection:

   ```sql
   CREATE DATABASE cafe_portal CHARACTER SET utf8mb4;
   CREATE USER 'cafe_app'@'127.0.0.1' IDENTIFIED BY 'choose-a-local-password';
   GRANT ALL PRIVILEGES ON cafe_portal.* TO 'cafe_app'@'127.0.0.1';
   ```

2. Copy `config/local.example.php` to `config/local.php`. Enter the MySQL host, port, database name, username and password. Use `127.0.0.1` consistently. Never commit the local configuration.
3. From the project root, run:

   ```text
   php scripts/install.php
   ```

4. Create a staff account. In PowerShell:

   ```powershell
   $env:CAFE_STAFF_PASSWORD = 'choose-a-unique-staff-password'
   php scripts/create-staff.php staff@example.test "Cafe staff"
   Remove-Item Env:\CAFE_STAFF_PASSWORD
   ```

   On macOS/Linux: `CAFE_STAFF_PASSWORD='choose-a-unique-staff-password' php scripts/create-staff.php staff@example.test 'Cafe staff'`.

5. Start the development server from the project root:

   ```text
   php -S 127.0.0.1:8080 -t public
   ```

6. Open [the local portal](http://127.0.0.1:8080). Register a customer account or sign in with the staff account you created.

If PHP is not on PATH, use the full path to your WAMP/XAMPP `php.exe`. Serve **only the `public` directory**. Do not expose the project root through Apache. For a permanent Apache installation, set the virtual host DocumentRoot to the project's `public` directory and allow access to that directory. The development server is for local assessment, not public hosting.

## Docker alternative

Copy `.env.example` to `.env` and replace both passwords. Run `docker compose up --build -d`, then `docker compose exec web php scripts/install.php` and create staff with `docker compose exec -e CAFE_STAFF_PASSWORD=your-password web php scripts/create-staff.php staff@example.test "Cafe staff"`. Open [localhost:8080](http://127.0.0.1:8080). MySQL is not published on a host port. Run `docker compose down` to stop; the database volume remains. Do not use `down -v` unless you deliberately want to delete demonstration data.

## Features and boundaries

- Public menu, category filters, search, availability and allergen information.
- Registration, hashed passwords, role checks, database-backed sign-in throttling and CSRF protection.
- Session bag, quantity editing/removal, per-item recipient labels and preparation notes; up to 30 lines and 80 items per order.
- Customer-only checkout with collection slots for today and the following two days, 15-minute lead time, eight orders per slot, and daily collection from 07:00 through 15:45 Canberra/Sydney time.
- Explicit approved/declined simulated payment; no real card fields, money movement or email service.
- Transactional order creation, locked capacity checks, server-authoritative prices, price-change confirmation and idempotent checkout.
- Customer-owned order receipts and tracking; staff queue with controlled state transitions and simulated refunds on cancellation.
- Staff editing of seeded menu items, descriptions, allergens, price, category and availability. Adding/deleting products is outside this prototype's scope.
- CSS illustrations are local and original to this project. No external fonts or image services are needed.

## Verification

Use a disposable database for integration/browser tests. They create synthetic customers and orders and temporarily edit a seeded menu item.

```text
php tests/domain.php
php tests/database.php
```

With the web server running and `TEST_STAFF_PASSWORD` set for a test staff account:

```text
node tests/integration.mjs
```

Optional browser checks require Playwright and installed Chrome: set `PLAYWRIGHT_MODULE` to its package path or install it in your test environment, then run `node tests/browser.cjs`. `BROWSER_CHANNEL=msedge` selects Edge. Test reports and screenshots are written under ignored `artifacts/`.

Failure recovery checks: `node tests/failure-cases.mjs`. Set `TEST_PHP` to the PHP executable and optionally `TEST_PHP_INI` to the test configuration. If MySQL binary logging requires administrative trigger privileges, set `TEST_DB_ADMIN_USER` and `TEST_DB_ADMIN_PASSWORD` for the CLI fixture only. Run this suite only on a disposable database; it temporarily injects a payment-storage failure.

See [test evidence](docs/TEST_REPORT.md) for the executed results and limits. Docker and MySQL 8.4 are supplied installation options; local execution was verified on Windows with PHP 8.4 and MySQL 9.1.

## Handover and team plan

- [Implementation and operating guide](docs/IMPLEMENTATION.md)
- [Three-person allocation and weekly plan](docs/TEAM_PLAN.md)
- [Weekly checkpoints from August 20](docs/WEEKLY_CHECKPOINTS.md)
- Run `powershell -File scripts/package-team.ps1` to produce three assigned-component ZIPs in `dist/`. Extract all three into the same folder for a complete working project. ZIP labels are proposed responsibilities and do not establish individual authorship.
- The source ZIP is in `dist/`. Word deliverables and the individual reflection were deferred at the user's request.
- Jasson: customer interface, accessibility and coordination. Mandip Rijal: accounts, data and checkout. Rudesh: staff operations, verification and handover. Each proposed allocation is 40 effort points; actual contributions must be recorded by the people involved.

The plan is not a historical activity log. Git commits retain their actual creation times and configured author. The supplied August 17–October 6 window contains eight weekly buckets, whereas academic Weeks 3–12 contain ten weeks; these are documented separately pending the course calendar. Personal reflections and tutor feedback must be supplied from real experience.
