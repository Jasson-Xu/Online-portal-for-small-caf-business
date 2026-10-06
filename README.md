# Folks & Co. café ordering portal

A complete PHP and MySQL click-and-collect student project for ICT312 Assignment 2 in 2026, based on Proposal-1. The English interface covers individual and group orders, simulated payments, collection times, private order tracking and a staff desk. Folks & Co. is demonstration branding; it is not an official Folks Gallery service.

## Requirements

- Windows XAMPP with PHP 8.2 or newer and PDO MySQL enabled. This project was checked with XAMPP PHP 8.2 and MariaDB 10.4.
- A modern browser. Node.js 20+ is needed only for optional integration tests.
- No Composer, npm build, internet assets or framework is required to run the app.

## Start with XAMPP on Windows

1. In the **XAMPP Control Panel**, start **Apache** and **MySQL**.
2. Put the **whole project** in XAMPP's `htdocs` folder as `cafe`. On this computer, use `D:\XAMPP\htdocs\cafe` and check that `D:\XAMPP\htdocs\cafe\public\index.php` exists. If XAMPP is installed on `C:`, use `C:\xampp\htdocs\cafe` instead. Keep the `app`, `config`, `database` and `public` folders together.
3. Open [phpMyAdmin](http://localhost/phpmyadmin/), select **Import**, choose [`cafe-portal-complete.sql`](database/xampp/cafe-portal-complete.sql), then click **Import** at the bottom. This one file creates the database, tables, menu and test data.
4. Open [http://localhost/cafe/public/](http://localhost/cafe/public/) and sign in with one of the accounts below.

| Test account | Email | Password | Data to check |
|---|---|---|---|
| Staff | `staff@example.test` | `1234567890` | Order queue and menu management |
| Customer one | `test.customer1@example.test` | `1234567890` | Received group order `TEST-RECEIVED-001` and cancelled order `TEST-CANCELLED-001` |
| Customer two | `test.customer2@example.test` | `1234567890` | Ready order `TEST-READY-001` |

Use customer one to check **My orders** and the group label, customer two to check that only their own order appears, and staff to move the received order through preparation, ready and collected. The cancelled example shows a simulated refund. These are fixed local demonstration accounts; the automated registration tests create additional temporary accounts as they run. The known-password staff account can sign in only from the same computer.

**For a standard XAMPP installation, that is all.** The app automatically uses local MySQL user `root`, a blank password and port `3306`; you do not need to create or edit `config/local.php`. Reimporting the one SQL file keeps existing orders and resets the two test customer passwords to the values above. The numbered SQL files remain available if you want to import the components separately.

If your MySQL root account has a password or MySQL uses another port, copy [`config/local.xampp.example.php`](config/local.xampp.example.php) in the same folder and rename the copy to `local.php`. Open that new file in a text editor and change only `db_password` and/or `db_port` to match XAMPP. This file is private local settings and is ignored by Git. If it already exists and the site works, leave it alone. If you used `setup-xampp.cmd` previously, it may have created an `htdocs\cafe` link; keep that link and open [http://localhost/cafe/](http://localhost/cafe/) instead of copying the project.

If the page cannot connect to the database, check that MySQL is running and that the SQL import showed success. If the page returns 404, check the folder path and URL. If Apache uses a custom port, add it after `localhost` (for example, `localhost:8080`). The project-root `.htaccess` prevents browser access to source folders.

### Automatic setup alternative

Leave the project in its own folder, start Apache and MySQL, then double-click `setup-xampp.cmd`. It creates a database-scoped account, imports the tables, menu and test data, sets the demo staff credentials, writes `config/local.php`, and links only `public/` into `htdocs\cafe`. Open [http://localhost/cafe/](http://localhost/cafe/). The command detects XAMPP in `D:\XAMPP` or `C:\xampp`; set `XAMPP_ROOT` for another installation path. If XAMPP's MySQL administrator has a password, set `XAMPP_DB_ADMIN_PASSWORD` before running it. An existing `htdocs\cafe` that points elsewhere is left untouched. Repeat runs retain orders and reset the test passwords.

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

The imported accounts and orders above support manual test cases immediately. For the quick read-only checks, run these commands from the project folder:

```text
php tests/domain.php
php tests/database.php
```

The remaining automated tests create synthetic customers and orders and temporarily edit a menu item; use a disposable database for them. With the web server running and `TEST_STAFF_PASSWORD` set for a test staff account:

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
