# System design 2026

## Purpose and scope

This document defines the design of the Folks & Co. café ordering portal for ICT312 Assignment 2 in 2026. It implements the small-café click-and-collect proposal: a customer can prepare an individual or labelled group order before arrival, while staff can view and progress orders at the counter. The result is a working educational prototype, not a deployed service for Folks Gallery. Payments are deliberately simulated, and no card data or real money is processed.

The business problem is a limited-staff café during busy periods. Moving menu browsing, order review and collection-time choice into a web portal can reduce the work required at the counter. Group labels and per-item notes make large orders easier to read. The design does not claim measured reductions in waiting time or sales growth; those outcomes would need a real pilot.

## Users and requirements

The system has three roles: visitor, registered customer and staff. Visitors may browse the menu and build a session bag. Customers may submit and track their own orders. Staff may view the shared queue, update statuses and maintain the existing catalogue. There is no public administrator registration.

| ID | Requirement | Acceptance condition |
|---|---|---|
| F1 | Browse and search the menu | Items show names, categories, prices, availability and allergens; category and search filters work. |
| F2 | Build an individual or group bag | A visitor can add, edit and remove lines, quantities, recipient labels and preparation notes. |
| F3 | Register and sign in | Customer registration creates a customer role only; staff use a separately provisioned account. |
| F4 | Place a click-and-collect order | A signed-in customer selects a valid slot, confirms details and completes an approved simulated payment. |
| F5 | Confirm and track an order | A reference, receipt and status history are visible only to the owning customer. |
| F6 | Operate the café queue | Staff can review orders, progress valid states, cancel eligible orders and edit seeded menu items. |

| ID | Quality requirement | Design response |
|---|---|---|
| Q1 | Protect customer data | Role checks, ownership checks, hashed passwords, CSRF tokens, prepared SQL and limited stored fields. |
| Q2 | Preserve order integrity | Server prices, database transactions, foreign keys, locked slot capacity and a unique checkout key. |
| Q3 | Work on local XAMPP | HTML5, CSS3, JavaScript, PHP and MySQL-compatible MariaDB; ordered SQL imports and a Word installation guide. |
| Q4 | Support phones and keyboards | Responsive layout, meaningful labels, focus styles, skip link and semantic controls. |
| Q5 | Explain the payment boundary | Checkout and receipt identify the transaction as simulated; no payment-card fields exist. |

## System structure

The browser requests pages from `public/index.php`. The PHP application reads and writes MySQL through PDO. HTML is rendered on the server, with JavaScript used for interface enhancements. This keeps prices, role decisions and state changes under server control. The web server should expose only `public/`; when the whole project is copied into XAMPP `htdocs`, the included Apache rules deny browser access to the source folders.

| Layer | Files | Responsibility |
|---|---|---|
| Presentation | `public/index.php`, `public/assets/`, `app/views.php`, `app/staff_views.php` | Pages, forms, responsive styling and staff views. |
| Application | `app/actions.php`, `app/staff_actions.php`, `app/domain.php` | Validation, checkout, status rules and catalogue changes. |
| Shared services | `app/bootstrap.php`, `config/config.php` | Sessions, security headers, CSRF, escaping and PDO connection. |
| Data | `database/schema.sql`, `database/seed.sql`, `database/xampp/` | Relational tables, menu seed and manual XAMPP import. |

The main customer journey is Home → Menu → Bag → Sign in → Checkout → Confirmation → My orders → Track order. A customer may browse and fill the bag before signing in. Staff use Sign in → Staff desk → Review order → Change status; Manage menu is a separate staff page. The queue is refreshed manually rather than pushed live.

## Data design

Eight InnoDB tables separate identity, catalogue, capacity, orders and audit data. Integer cents avoid floating-point totals. The order stores its own customer name, phone, collection time, total and checkout key. Order lines snapshot product names and unit prices so later menu edits do not change a historical receipt.

| Table | Main contents and relationship |
|---|---|
| `users` | Customer or staff identity, unique email, password hash and role. |
| `menu_items` | Product details, category, allergens, price in cents and availability. |
| `pickup_slots` | One row per collection time, with capacity and booked count. |
| `orders` | Customer-owned order, unique reference and checkout key, slot and status. |
| `order_items` | Lines linked to an order and menu item, with price snapshots and group labels. |
| `payments` | One simulated payment record per order; no card number or CVV. |
| `order_events` | Status history with the acting user and timestamp. |
| `login_attempts` | Sign-in attempt count per hashed email for throttling. |

Foreign keys bind orders to users and slots, lines to orders and menu items, and payments and events to orders. The database schema uses `utf8mb4` for café names and customer text. The application stores database timestamps in UTC and presents collection times in Australia/Sydney local time.

## Ordering and state rules

The bag permits up to 30 lines and 80 items, with a quantity from 1 to 20 per line. The checkout page offers collection times today and over the next two days, at least 15 minutes ahead, during 07:00–15:45 local collection hours. Each slot holds at most eight orders. These are prototype rules that a café operator could change after reviewing actual demand.

At submission, the server checks the signed-in user, CSRF token, bag, contact details, slot, payment acknowledgement and approved simulation outcome. A database transaction locks the selected slot and product rows, checks capacity and availability, recalculates prices, inserts the order, lines, payment and first event, increments the booked count and commits. Any failure rolls back the whole transaction. The unique checkout key redirects a repeated successful submission to the original receipt.

The status sequence is `received → preparing → ready → collected`. Staff may cancel only a received or preparing order. Cancellation writes an event, changes the simulated payment to refunded and releases the slot count in the same transaction. The receipt and order history remain available after cancellation.

## Security, privacy and ethical design

The portal stores name, email, phone, order contents and collection details because they support account access and order handover. It does not collect card numbers, CVV, marketing profiles or analytics identifiers. The privacy page explains why details are requested and that payment is simulated. Group recipient names and preparation notes should contain only information needed to prepare the order; staff should not request sensitive information in those fields.

Customer registration cannot set the staff role. Passwords use PHP password hashing and verification. Sessions rotate their ID after authentication; cookies are HttpOnly and SameSite=Lax. CSRF tokens protect POST forms, user text is HTML-escaped, and prepared PDO statements separate values from SQL. The server checks order ownership and staff roles on every protected operation. Sign-in is limited to ten attempts per email in 15 minutes across sessions. The fixed XAMPP demo staff account is restricted to requests from the same computer; a public deployment requires a unique staff credential and HTTPS.

The design follows the Australian Privacy Principles as a privacy reference, especially minimising collected information and protecting what is held. Whether a particular café is legally covered by those principles depends on its circumstances and requires an operator review. The prototype is not a legal compliance certificate. The [OAIC guidance on collection](https://www.oaic.gov.au/privacy/australian-privacy-principles/australian-privacy-principles-guidelines/chapter-3-app-3-collection-of-solicited-personal-information) and [security](https://www.oaic.gov.au/privacy/australian-privacy-principles/australian-privacy-principles-guidelines/chapter-11-app-11-security-of-personal-information) inform the data-minimisation and access-control choices.

## Development approach and platform choice

The proposal's staged approach is implemented as short increments with a demonstrable outcome at each review: catalogue, account access, bag, checkout, tracking and staff operations. This allows a three-person team to integrate and test each feature before Week 12. A pure waterfall sequence would make early documentation orderly but delay feedback on the customer journey; ad hoc coding would move quickly at first but make integration and acceptance harder to coordinate. The selected incremental approach keeps the assignment scope visible while allowing corrections after tests.

| Option | Assessment for this project |
|---|---|
| Server-rendered PHP with MySQL | Fits the required technology stack, XAMPP installation and small multi-page workflow; keeps authoritative rules on the server. Selected. |
| Single-page JavaScript application plus API | Could support richer live interactions, but requires a build pipeline and duplicates routing and state concerns for this scope. |
| Hosted ordering platform | Could deploy faster, but would not demonstrate the required HTML, CSS, JavaScript, PHP and MySQL implementation. |

Git and GitHub record source changes. XAMPP provides the local Apache, PHP and MariaDB environment used for handover; Docker is an optional alternative. HTML5 labels and semantics, CSS responsive layouts and small JavaScript enhancements were chosen so the core ordering workflow does not depend on a front-end framework. The implementation and test documents describe how these decisions were checked.

## References

- ICT312 Assignment 2 brief supplied with this project, assessment requirements and Part B deliverables.
- Proposal-1 supplied with this project, small-café problem, stakeholders and proposed features.
- Office of the Australian Information Commissioner, Australian Privacy Principles guidance on APP 3 and APP 11, linked above.
- [PHP password hashing](https://www.php.net/manual/en/function.password-hash.php) and [PDO prepared statements](https://www.php.net/manual/en/pdo.prepared-statements.php).
