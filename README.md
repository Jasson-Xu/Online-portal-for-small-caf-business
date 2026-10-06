# Folks & Co. café ordering portal

A complete PHP and MySQL click-and-collect student project for ICT312 Assignment 2, based on Proposal-1. The English interface covers individual and group orders, simulated payments, collection times, private order tracking and a staff desk. Folks & Co. is demonstration branding; it is not an official Folks Gallery service.

## Requirements

- Windows XAMPP with PHP 8.2 or newer and PDO MySQL enabled. This project was checked with XAMPP PHP 8.2 and MariaDB 10.4.
- A modern browser. Node.js 20+ is needed only for optional integration tests.
- No Composer, npm build, internet assets or framework is required to run the app.

## Start locally with XAMPP: phpMyAdmin import

1. Copy the **whole project folder** into XAMPP's `htdocs` folder and name it `cafe` (for example, `C:\xampp\htdocs\cafe`). Keep `app`, `config`, `database`, `public`, and the other folders together. If `htdocs\cafe` is already a link to this project's `public/` from the earlier setup, keep that link and skip the copy.
2. In the **XAMPP Control Panel**, start **Apache** and **MySQL**. Open [phpMyAdmin](http://localhost/phpmyadmin/), choose **Import**, and import these files **one at a time, in order**: [`01-create-database.sql`](database/xampp/01-create-database.sql), [`02-create-tables.sql`](database/xampp/02-create-tables.sql), [`03-demo-data.sql`](database/xampp/03-demo-data.sql). The files create `cafe_portal`, its tables, the sample menu, and the demo staff account. They do not delete existing orders if imported again.
3. If `config/local.php` does not already exist, copy `config/local.xampp.example.php` to `config/local.php`. A default local XAMPP installation uses MySQL user `root` with a blank password. If you set a MySQL root password or changed port 3306, edit those two values in `config/local.php`. Keep an existing working `config/local.php` from automatic setup.
4. Open [http://localhost/cafe/public/](http://localhost/cafe/public/). Register a customer account or sign in as staff with `staff@example.test` / `1234567890`. The project-root `.htaccess` blocks browser access to the source folders; `public/` is the website. If you are using the earlier `setup-xampp.cmd` link, the URL remains [http://localhost/cafe/](http://localhost/cafe/), and its existing `config/local.php` may be kept.

This manual path uses phpMyAdmin and the three SQL files, without running a PHP installer. The known-password demo staff account can sign in only from the same computer. Keep `config/local.php` private and never commit it. If `staff@example.test` already belongs to a customer, the SQL leaves that customer unchanged; use a fresh database or change that customer email before importing the demo data.

### Automatic setup alternative

Leave the project in its own folder, start Apache and MySQL, then double-click `setup-xampp.cmd`. It creates a database-scoped account, imports the tables and menu, sets the same demo staff credentials, writes `config/local.php`, and links only `public/` into `htdocs\cafe`. Open [http://localhost/cafe/](http://localhost/cafe/). The command detects XAMPP in `D:\XAMPP` or `C:\xampp`; set `XAMPP_ROOT` for another installation path. If XAMPP's MySQL administrator has a password, set `XAMPP_DB_ADMIN_PASSWORD` before running it. An existing `htdocs\cafe` that points elsewhere is left untouched. Repeat runs retain orders and reset the demo staff password.

If Apache uses a non-default port, add that port after `localhost`. Stop the site by stopping Apache in the XAMPP Control Panel. `scripts/install.php` and `scripts/create-staff.php` remain available for Docker or other installations. XAMPP is a local development environment, not a production deployment.

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

See [test evidence](docs/TEST_REPORT.md) for the executed results and limits. The original integration suite ran on Windows with PHP 8.4 and MySQL 9.1; the XAMPP setup is checked separately with PHP 8.2 and MariaDB 10.4.

## Handover and team plan

- [Implementation and operating guide](docs/IMPLEMENTATION.md)
- [Three-person allocation and weekly plan](docs/TEAM_PLAN.md)
- [Weekly checkpoints from August 20](docs/WEEKLY_CHECKPOINTS.md)
- Run `powershell -File scripts/package-team.ps1` to produce three assigned-component ZIPs in `dist/`. Extract all three into the same folder for a complete working project. ZIP labels are proposed responsibilities and do not establish individual authorship.
- Word deliverables and the individual reflection were deferred at the user's request.
- Jasson: customer interface, accessibility and coordination. Mandip Rijal: accounts, data and checkout. Rudesh: staff operations, verification and handover. Each proposed allocation is 40 effort points; actual contributions must be recorded by the people involved.

The plan is not a historical activity log. Git commits retain their actual creation times and configured author. The requested August 20–October 6 checkpoints differ from academic Weeks 3–12; these are documented separately pending the course calendar. Personal reflections and tutor feedback must be supplied from real experience.
