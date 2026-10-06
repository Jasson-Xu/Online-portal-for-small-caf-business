# Folks & Co. café ordering portal

A complete PHP and MySQL click-and-collect student project for ICT312 Assignment 2 in 2026, based on Proposal-1. The English interface covers individual and group orders, simulated payments, collection times, private order tracking and a staff desk. Folks & Co. is demonstration branding; it is not an official Folks Gallery service.

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

## Design, delivery and management

- **Design:** The portal has visitor, customer and staff views. PHP renders the pages, and eight MySQL tables hold accounts, menu items, orders, collection slots, simulated payments and status history. Only `public/` is served to browsers.
- **Implementation:** Server-side validation, prepared SQL, hashed passwords, CSRF tokens and role checks protect the ordering flow. Checkout uses one database transaction and a unique key to prevent partial or duplicate orders.
- **Testing:** The 2026 test record contains 23 domain checks, 48 HTTP checks, eight database checks, 23 browser checks in each of Chrome and Edge, and four failure-recovery checks. XAMPP SQL import and staff sign-in were also checked.
- **Risk management:** The team reviews database failure, incorrect orders, unauthorized access, allergen information and installation problems at each release review. The relevant component owner records the issue and response.
- **Service management:** For a local demonstration, check Apache, MySQL, the menu and Staff desk before use; keep a database export outside `public/`. Record incidents and verify ordering again after recovery. The proposed restore target is one hour.
- **Change management:** Record the reason and impact of a change, review code and database effects, run relevant tests, then commit and update the guides. Back up before schema changes and prepare a tested rollback.

The [design](docs/DESIGN.md), [implementation and management](docs/IMPLEMENTATION.md), and [test report](docs/TEST_REPORT.md) give the full Part B details.

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

See the [test report](docs/TEST_REPORT.md) and checked-in results under `docs/evidence/` for the executed cases and their limits.

## Project documentation for 2026

- [System design](docs/DESIGN.md) and [Word version](docs/word/System-Design-2026.docx)
- [Implementation, installation, user and management guide](docs/IMPLEMENTATION.md) and [Word version](docs/word/Implementation-and-Management-2026.docx)
- [Test plan and results](docs/TEST_REPORT.md) and [Word version](docs/word/Test-Plan-and-Results-2026.docx)
- [Three-person work allocation](docs/TEAM_PLAN.md) and [2026 weekly checkpoints](docs/WEEKLY_CHECKPOINTS.md)

For a source-code submission, `scripts/package-team.ps1` can create the three assigned-component packages in `dist/`; extract all three into one folder to restore the full project. The files in `docs/word/` are the Part B Word documentation. Jasson coordinates customer-facing work, Mandip Rijal covers accounts and checkout, and Rudesh covers staff operations and testing.
