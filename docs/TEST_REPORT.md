# Test plan and results 2026

## Purpose and acceptance basis

This document records the test approach and results for the Folks & Co. café portal in ICT312 Assignment 2, 2026. The acceptance goal is a working local system that supports the proposal's customer and staff journeys, protects account and order data, and can be installed on XAMPP. The tests demonstrate prototype behaviour; they are not evidence of a production payment service, a live café trial or formal security certification.

The release is acceptable for the local demonstration when a fresh installation opens the menu, a customer can register and place a simulated order, the receipt and tracking page show that order, staff can progress it through valid states, and the documented data and access checks pass. A critical privacy or order-integrity failure blocks handover until corrected and retested.

## Test strategy and environment

Tests are divided into domain rules, HTTP integration, database integrity, browser behaviour, failure recovery and XAMPP installation. Domain tests check pure rules; integration tests exercise real requests and database writes; browser tests check the customer and staff journeys at desktop and narrow mobile widths. Failure tests deliberately break payment storage in a disposable database to verify rollback. Manual installation checks cover SQL import, Apache access rules and staff sign-in.

The main automated test evidence was recorded on 6 October 2026 using Windows, PHP 8.4, MySQL 9.1 and a local PHP server at `127.0.0.1:8080` with a separate test database. XAMPP checks used PHP 8.2 and MariaDB 10.4. Synthetic accounts and orders were used. The checked-in JSON files under `docs/evidence/` record the integration, browser and failure suites; they contain test identifiers rather than real customer data.

| Level | Test data and method | Exit condition |
|---|---|---|
| Domain | Boundary values for quantities, phone numbers, pickup slots and order states. | Every expected acceptance and rejection passes. |
| HTTP integration | Disposable accounts, bag and checkout requests, ownership and staff actions. | Customer-to-staff journey and negative paths pass. |
| Database integrity | Queries over order, payment, slot, event and password records. | No broken relationship or mismatched total is found. |
| Browser | Chrome and Edge, desktop and 320/390-pixel layouts. | Main forms work and no horizontal overflow or JavaScript error appears. |
| Failure recovery | Full slot and injected payment-write failure in a disposable database. | No partial order and retry succeeds after recovery. |
| Installation | XAMPP automatic setup and three manual SQL imports. | Menu and staff login work; source folders are blocked by Apache. |

## Acceptance cases and traceability

The following cases connect the design requirements to observable outcomes. Each case was exercised in the named suite unless noted as a manual installation check.

| Case | Design requirement | Expected outcome | Evidence |
|---|---|---|---|
| T01 Menu and search | F1 | Seeded products, categories, prices and search results display. | HTTP and browser passes. |
| T02 Group bag | F2 | Two flat whites for one recipient total $9.60; edits and removal recalculate. | HTTP and browser passes. |
| T03 Registration and roles | F3, Q1 | Customer registration succeeds without staff privileges; staff pages reject customers. | HTTP pass. |
| T04 Input validation | F2–F4 | Invalid email, phone, quantity, product and slot are rejected without a new order. | Domain and HTTP passes. |
| T05 Approved simulation | F4, Q2, Q5 | One order, payment record and first event are stored; no card fields are requested. | HTTP and database passes. |
| T06 Declined simulation | F4, Q5 | No order or payment is created and the bag remains. | HTTP pass. |
| T07 Duplicate submission | F4, Q2 | Replaying a successful checkout returns the original receipt. | HTTP pass. |
| T08 Customer privacy | F5, Q1 | Another account's reference returns 404 without revealing a phone number. | HTTP pass. |
| T09 Staff lifecycle | F6 | Valid state sequence works; a skipped transition is rejected. | HTTP and browser passes. |
| T10 Cancellation | F6, Q2 | An eligible order is cancelled, refunded in simulation and removed from slot count. | HTTP and database passes. |
| T11 Catalogue history | F1, F6 | Marking an item unavailable blocks new orders; old receipt prices remain. | HTTP pass. |
| T12 Recovery | Q2 | Full slot or payment-write error leaves no partial order; retry succeeds. | Failure suite, four passes. |
| T13 XAMPP handover | Q3 | Ordered SQL import creates 12 items and staff login; source URLs return 403. | Manual XAMPP check. |
| T14 Responsive interface | Q4 | Main pages fit 320- and 390-pixel viewports in two browsers. | Browser passes. |

## Executed results

| Suite | Result | Key coverage |
|---|---|---|
| PHP syntax | Passed | Application and setup files parsed without errors. |
| Domain rules | 23 passed | Quantity, phone, collection-time and status boundaries. |
| HTTP integration | 48 passed | Customer flow, access control, CSRF, checkout, replay, staff flow and menu edits. |
| Database integrity | 8 passed | Hashes, links, totals, payment states, capacity, events and absence of card columns. |
| Chrome browser | 23 passed | Forms, group checkout, staff sign-in, responsive widths and JavaScript errors. |
| Edge browser | 23 passed | Same workflow and responsive checks in a second installed browser. |
| Failure recovery | 4 passed | Full slot, rollback, bag retention and successful retry. |
| XAMPP automatic setup | Passed | New account, repeat run, linked public folder and local staff login. |
| Manual SQL import | Passed | Files imported twice; 12 items, valid staff hash and one existing order retained. |
| Apache source access | Passed | `public/` returned 200; `app/`, `config/` and README requests returned 403. |

The checked-in integration result is `docs/evidence/integration-results.json`; browser results are `browser-results-chrome.json` and `browser-results-msedge.json`; the failure record is `failure-results.json`. Each includes an execution time and its completed checks. Database and domain scripts print their result directly and can be rerun as described below.

## Defect recording and retest

During visual review, the home-page illustration stamp was moved away from a curved edge and browser screenshots were regenerated. A database test query initially interpreted a password-hash prefix incorrectly; its escaping was corrected and the database checks rerun. These were test or presentation defects and did not change stored customer orders. Each correction was checked again in the affected suite.

For a new defect, record the page or test name, steps to reproduce, expected and actual result, severity, owner, fix reference and retest outcome. Critical defects involving order integrity or private data block the demonstration. A failed automated case should be rerun after the fix and followed by the neighbouring customer or staff journey to check for regression.

## Reproduction procedure

Use a disposable database for suites that create accounts and orders. Set its connection details in an ignored `config/local.php` or the corresponding environment variables. Start a local PHP server or XAMPP Apache, then run the relevant commands from the project folder:

```text
php tests/domain.php
php tests/database.php
node tests/integration.mjs
node tests/failure-cases.mjs
node tests/browser.cjs
```

The HTTP suite needs `TEST_STAFF_PASSWORD` for a staff account in that disposable database. Browser checks need Playwright and Chrome or Edge; see README for the optional runtime settings. The failure suite temporarily installs a payment-failure database trigger and changes one test slot. It removes them after completion, but an interrupted run requires inspection before reuse. Never run mutation suites against real café orders.

To repeat the manual XAMPP check, import the three numbered files under `database/xampp/` in phpMyAdmin and open the menu and Staff desk. Standard XAMPP needs no `config/local.php`; create it from `config/local.xampp.example.php` only for a custom MySQL password or port. Request a source path such as `/cafe/app/actions.php` from a full-folder installation; it should return 403. Test both customer and staff sign-in after any change to account setup.

## Coverage limits

The two-browser checks cover installed Chrome and Edge and viewport widths of 320 and 390 pixels; they do not constitute a full accessibility audit. Firefox, Safari, physical phones, a public HTTPS server and Docker startup were not exercised in this environment. The local PHP development server serialises requests, so the tests do not prove behaviour under multi-worker concurrent load, even though the database uses row locking. No real payment, email delivery, live-café user study, penetration test or external tutor acceptance was performed. These items require separate work before a public launch.

## References

- [System design](DESIGN.md) for requirement IDs and intended behaviour.
- [Implementation and management guide](IMPLEMENTATION.md) for installation and operation.
- `docs/evidence/` and the executable tests under `tests/` for recorded checks.
