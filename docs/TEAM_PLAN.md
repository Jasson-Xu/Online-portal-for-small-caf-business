# Team allocation and implementation plan

This is a proposed allocation for Jasson, Mandip Rijal and Rudesh. It is not evidence of completed contributions or past weekly meetings. Jasson is the proposed team leader. The proposal identifies its author as Zhiyuan XU, ID 988638; the user supplied the team name Jasson. Confirm the preferred submission name and all teammate IDs before submission.

## Three balanced work packages

| Member | Main responsibility | Effort points | Review partner |
|---|---|---:|---|
| Jasson | Customer experience and coordination: requirements (5), design and navigation (6), menu and search (8), cart and group labels (8), responsive accessibility (7), integration coordination and demonstration (6) | 40 | Mandip Rijal |
| Mandip Rijal | Accounts and transaction processing: schema (7), authentication and sessions (8), validation (5), collection slots (6), atomic checkout and simulation (9), installation/security review (5) | 40 | Rudesh |
| Rudesh | Café operations and assurance: staff queue (8), status transitions and refunds (7), catalogue management (7), tests (8), manuals and operational plans (6), release verification (4) | 40 | Jasson |

Effort points are estimates of relative complexity, not hours already worked. All three review requirements, test one another's work and rehearse the presentation. Leadership is included in Jasson's allocation, not added on top of an equal development load.

## Academic Weeks 3 through 12

| Week | Jasson | Mandip Rijal | Rudesh | Acceptance evidence to collect |
|---|---|---|---|---|
| 3 | Agree problem and user journey; coordinate scope | Identify stored data and privacy needs | Identify staff workflow and risks | Agreed requirements and genuine meeting notes |
| 4 | Sketch navigation and mobile layouts | Design entities and constraints | Draft test cases and staff screens | Design review and task estimates |
| 5 | Build shared layout and home | Create schema and seed catalogue | Create staff queue layout | Clean install and initial page review |
| 6 | Implement menu, filters and search | Register/login and session protection | Staff access checks and test fixtures | Customer and role tests |
| 7 | Build cart editing and group labels | Validate prices and order input | Queue retrieval and catalogue editing | Cart and catalogue tests |
| 8 | Build checkout interface | Implement slots, transactions and simulation | Status transition and cancellation flow | Successful and failed checkout demonstrations |
| 9 | Build confirmation and tracking | Test duplicate submissions and data integrity | Test status history and simulated refunds | Full customer-to-staff walkthrough |
| 10 | Review mobile layout and labels | Review access, CSRF and SQL handling | Execute regression and failure tests | Reproducible test report and defects |
| 11 | Rehearse customer demonstration | Validate fresh-machine install and backup | Complete manuals and release checks | Installation rehearsal and reviewed documentation |
| 12 | Coordinate final demonstration and package | Review database and explain transactions | Present staff workflow and test evidence | Final ZIP, Word guides and each person's own reflection |

## Requested calendar buckets

The requested checkpoints begin August 20 and continue weekly through October 1, with October 6 as the final review. The year was not confirmed. The brief filename contains 2025, but the local implementation session occurred on October 6, 2026. This table deliberately leaves the year unspecified. These dates are a plan, not historical activity or commit dates. Eight calendar buckets cannot honestly be relabelled as ten complete teaching weeks.

| Planning bucket | Date range | Proposed milestone | Jasson | Mandip Rijal | Rudesh |
|---|---|---|---|---|---|
| 1 | Aug 20–26 | Scope and architecture | Journeys and layout | Data model | Staff requirements and tests |
| 2 | Aug 27–Sep 2 | Foundation | Home and navigation | Schema and setup | Queue structure |
| 3 | Sep 3–9 | Catalogue and access | Menu/filter/search | Registration and sessions | Role checks |
| 4 | Sep 10–16 | Group ordering | Bag and recipient labels | Server validation | Menu management |
| 5 | Sep 17–23 | Checkout | Collection/payment UI | Atomic order placement | Preparation workflow |
| 6 | Sep 24–30 | Integrated service | Receipts and tracking | Integrity and replay checks | Cancellation and refunds |
| 7 | Oct 1–5 | Review and handover | Responsive review | Security/install review | Regression and manuals |
| 8 | Oct 6 | Final review | Presentation coordination | Database verification | Package checks |

The last bucket is one day, so it is reserved for review rather than an equal development workload. Keep actual commit timestamps. Use meaningful commits as work is completed; do not invent authorship, tutor meetings or contribution evidence.

## Weekly record template

For each real meeting record the date, participants, completed issue/commit links, one demonstrated feature, failed tests, agreed fixes, next tasks and actual tutor feedback. Each person should acknowledge their own contribution. A plan alone does not satisfy evidence of weekly discussion.
