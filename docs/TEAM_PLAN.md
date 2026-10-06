# Team allocation and implementation plan 2026

The three-person team divides the 2026 café portal project by customer experience, transaction processing, and café operations. Jasson coordinates integration and the demonstration. Each member has a comparable development and review workload.

| Member | Main responsibility | Estimated effort points | Review partner |
|---|---|---:|---|
| Jasson | Requirements (5), page design and navigation (6), menu and search (8), cart and group labels (8), responsive accessibility (7), integration and demonstration (6) | 40 | Mandip Rijal |
| Mandip Rijal | Data model (7), authentication and sessions (8), input validation (5), collection slots (6), transactional checkout (9), installation and security review (5) | 40 | Rudesh |
| Rudesh | Staff queue (8), status changes and cancellation (7), catalogue management (7), test execution (8), manuals and operating plans (6), release verification (4) | 40 | Jasson |

Effort points express planned complexity rather than hours. All members review cross-cutting requirements, privacy safeguards, integration tests, and the final presentation.

## Academic weeks 3 to 12

| Week | Jasson | Mandip Rijal | Rudesh | Review output |
|---|---|---|---|---|
| 3 | Define the customer journey and scope | Identify required data and privacy needs | Define staff workflow and initial risks | Requirements and acceptance criteria |
| 4 | Sketch navigation and mobile layouts | Draft entities and constraints | Draft test cases and staff screens | Reviewed design |
| 5 | Build the shared layout and home page | Create tables and sample menu | Build the staff queue layout | Running site and database |
| 6 | Implement menu, filters and search | Implement registration, login and sessions | Check staff access and test fixtures | Catalogue and access tests |
| 7 | Implement cart editing and group labels | Validate quantities, prices and order input | Implement queue retrieval and menu editing | Cart and catalogue tests |
| 8 | Build checkout interface | Implement slots, transaction and payment simulation | Implement status transitions and cancellation | Successful and failed checkout paths |
| 9 | Build confirmation and tracking | Check duplicate submissions and data integrity | Check history and simulated refunds | End-to-end walkthrough |
| 10 | Review mobile layout and form labels | Review access, CSRF and SQL handling | Run regression and failure tests | Defect fixes and test record |
| 11 | Rehearse the customer demonstration | Verify fresh XAMPP installation and backup | Complete user and operating guides | Installation rehearsal |
| 12 | Coordinate final demonstration | Explain database and transaction controls | Present staff workflow and test results | Part B source and Word documents |

## Calendar checkpoints

The project reviews are scheduled from 20 August to 6 October 2026. These calendar reviews support the academic week plan above; they are not a substitute for recording each class discussion with the tutor.

| Review date 2026 | Milestone | Jasson | Mandip Rijal | Rudesh |
|---|---|---|---|---|
| 20 August | Scope and architecture | Customer journey | Data model | Staff requirements and risks |
| 27 August | Foundation | Home and navigation | Schema and installation | Queue structure |
| 3 September | Catalogue and access | Menu and search | Registration and sessions | Role checks |
| 10 September | Group ordering | Bag and recipient labels | Server validation | Menu management |
| 17 September | Checkout | Collection and payment interface | Atomic order placement | Preparation workflow |
| 24 September | Integrated service | Receipts and tracking | Integrity and replay checks | Cancellation and refund handling |
| 1 October | Readiness review | Responsive layout | Security and installation review | Regression and manuals |
| 6 October | Final review | Demonstration coordination | Database verification | Release and document checks |

For each class review, record the date, participants, demonstrated feature, tutor feedback, defects, decisions and next actions in the group's own progress record. The Part A progress document can be completed from those records.
