# Folks & Co. café ordering portal

A complete PHP and MySQL click-and-collect student project for ICT312 Assignment 2, based on Proposal-1. The English interface covers individual and group orders, simulated payments, collection times, private order tracking and a staff desk. Folks & Co. is demonstration branding; it is not an official Folks Gallery service.

## Requirements

- Windows XAMPP with PHP 8.2 or newer and PDO MySQL enabled. This project was checked with XAMPP PHP 8.2 and MariaDB 10.4.
- A modern browser. Node.js 20+ is needed only for optional integration tests.
- No Composer, npm build, internet assets or framework is required to run the app.

## Start locally with XAMPP

1. In the **XAMPP Control Panel**, start **Apache** and **MySQL**. Leave the café project in its own folder; it does not need to be copied into `htdocs`.
2. From the project folder, double-click `setup-xampp.cmd`. Alternatively, in PowerShell run `powershell -ExecutionPolicy Bypass -File .\scripts\setup-xampp.ps1`. The setup detects XAMPP in `D:\XAMPP` or `C:\xampp`. If yours is elsewhere, set `XAMPP_ROOT` to its installation folder.
3. Open **[http://localhost/cafe/](http://localhost/cafe/)**. Register a customer account or sign in with the local demo staff account: `staff@example.test` / `1234567890`.

Setup creates the café database, an account limited to that database, the tables, sample menu and a staff account. It writes its database settings to ignored `config/local.php` and links **only `public/`** into XAMPP's `htdocs` folder. An existing `htdocs/cafe` that points elsewhere is left untouched. Existing orders are retained on repeat runs; the demo staff password is set to `1234567890` each time. The known-password demo staff account can sign in only from the same computer. If prior local database settings need replacing, setup backs them up under ignored `var/`. No extra Apache configuration or PHP development server is needed.

If XAMPP's MySQL administrator has a password, set `XAMPP_DB_ADMIN_PASSWORD` in PowerShell before running setup, then remove it from the environment afterwards. The default database port is 3306; set `XAMPP_DB_PORT` if you deliberately changed XAMPP's port. If Apache is on a non-default port, open `http://localhost:<port>/cafe/`. To stop the site, stop Apache in the XAMPP Control Panel. The project link and data stay in place for the next start.

`scripts/install.php` and `scripts/create-staff.php` remain available for manual or Docker installations. Never commit `config/local.php` or serve the project root directly from Apache. XAMPP is a local development environment, not a production deployment.

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
