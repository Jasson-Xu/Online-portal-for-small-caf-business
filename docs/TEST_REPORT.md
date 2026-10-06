# Test report

Execution date: 6 October 2026. Environment: Windows, PHP 8.4.0, MySQL 9.1.0, local HTTP server bound to 127.0.0.1:8080. A separate project database on port 3307 was used; the existing WAMP databases were not modified. Synthetic accounts and orders were used throughout.

## Executed verification

| Suite | Result | Coverage |
|---|---|---|
| PHP syntax | All PHP application and setup files passed | Parse errors |
| Domain rules | 23 passed | Quantity bounds, phone formats, slot lead time/horizon and terminal states |
| HTTP integration | 48 passed | Registration, login, roles, CSRF, menu, group bag, validation, checkout, replay, receipts, privacy, staff lifecycle, refunds, catalogue updates and throttling |
| Database integrity | 8 passed | Password hashes, foreign relationships, price snapshots, payment totals, refund states, booked capacity, audit events and absence of card columns |
| Browser workflow in Chrome | 23 passed | Real form submissions, group ordering, checkout, staff sign-in, no JavaScript errors, 320/390 pixel page widths |
| Browser workflow in Edge | 23 passed | Same browser workflow and responsive checks in a second installed browser |
| Failure recovery | 4 passed | Full collection slot, payment-storage rollback, preserved cart and recovery |
| XAMPP installation | Passed | PHP 8.2 and MariaDB 10.4, fresh dedicated database user, repeat setup, Apache junction, home/menu/CSS via `/cafe/` |
| Manual XAMPP SQL import | Passed | Three ordered SQL files imported through XAMPP MariaDB; 12 menu items and bcrypt staff password verified; existing order retained; manual Apache layout served `public/` and denied source files |

The browser checks detect horizontal overflow and exercise a real order. Screenshots were also inspected for visual layout. They are not a claim of formal accessibility certification. Firefox, Safari, physical phones, a public HTTPS deployment, Docker startup and MySQL 8.4 were not executed in this environment. The installation options must be rehearsed on the actual marker's machine.

The XAMPP setup was also exercised on the installed Windows XAMPP stack. A clean setup created a dedicated application account; a repeat run retained existing records. Apache served the linked public folder at `/cafe/` with HTTP 200 for the home page, menu and stylesheet. The local demo staff password was reset to the requested value on setup; HTTP sign-in reached the Staff desk, and the previous password was rejected. A custom XAMPP location or administrator password was not exercised.

The manual import SQL was run twice in order against the existing XAMPP database: all 12 sample items were present, the demo staff hash verified against the documented password, and the existing order count remained one. A temporary project-root link under `htdocs` checked the documented manual layout: `public/` returned HTTP 200 while `app/actions.php`, `config/config.php`, and `README.md` returned HTTP 403. The temporary link was removed after the check. A clean phpMyAdmin browser import on another computer has not been rehearsed.

## Representative acceptance cases

| Case | Expected outcome | Actual evidence |
|---|---|---|
| Add two flat whites for Rudesh | Correct group label and $9.60 total | Integration pass; persisted receipt |
| Submit a fake client price or changed expected total | Server uses catalogue and rejects mismatch | Integration pass |
| Select declined demo payment | No order/payment created; bag retained | Integration pass |
| Replay successful checkout | Original receipt returned, no duplicate order | Integration pass |
| Open another customer's reference | 404 with no customer phone | Integration pass |
| Access staff as customer | 403, no mutation | Integration pass |
| Skip from received to collected | Transition rejected | Integration pass |
| Cancel a preparing/received order | Simulated refund, released capacity | Integration and database pass |
| Mark a menu item unavailable | Sold-out state and server rejection | Integration pass |
| Update catalogue price after purchase | Historical receipt unchanged | Integration pass |

## Observed defects corrected

The first visual inspection found that the decorative hero stamp was too close to the curved illustration edge; it was repositioned and the browser screenshots regenerated. A test-query string accidentally interpolated the Argon2 prefix; the dollar signs were escaped and database verification repeated. Neither issue changed stored customer orders.

## Reproduction and limitations

Commands are in README.md. Run all mutation suites only against a disposable database. Browser and HTTP suites create real records within that database, so repeated runs can fill a collection slot. Recreate the disposable database or select unused slots if necessary. The failure suite temporarily installs a payment-insert trigger and changes one slot capacity; it removes/restores them in finally blocks. With binary logging enabled, set TEST_DB_ADMIN_USER and TEST_DB_ADMIN_PASSWORD to a test-database administrator for this CLI fixture only; the application account does not receive administrative privileges. If the test process is forcibly terminated, explicitly inspect and remove the `cafe_test_payment_failure` trigger before further use.

The one-process PHP development server serialises requests. Locking is implemented in the database, but this report does not claim a multi-worker load or concurrent-race test. Production performance, penetration testing, external user feedback and tutor acceptance remain outside the evidence collected here.
