# Non-Functional Requirements — SmartCommute

The qualities and constraints the system shall meet, alongside the behaviour in
[Functional Requirements](Functional_Requirements.md). Every figure below comes from the
[Use Case Narratives](UCN/README.md) or the project configuration (`composer.json`, `package.json`, `config/*`,
`routes/web.php`, `app/Console/Kernel.php`); nothing is a measured benchmark.

## How to read this document

- **ID** — `NFR-<category>-<nn>`.
- **Measure** — the value the system uses today, or what a reviewer can check. Where the UCNs give no number, the
  requirement is stated qualitatively rather than inventing one.
- **Status** — what the system does **today**:
  **Met** = implemented as written · **Partial** = implemented with a documented limitation (said in the row) ·
  **Gap** = not implemented, kept as a requirement so the team can decide.
- **Source** — UCN id or file where the behaviour is documented.
- Section 12 lists quality attributes **nobody has specified yet** (availability target, backup, accessibility…).

| Category | Met | Partial | Gap |
|---|:-:|:-:|:-:|
| 1 Performance & responsiveness | 5 | 2 | 0 |
| 2 Security | 9 | 6 | 1 |
| 3 Privacy | 2 | 3 | 2 |
| 4 Reliability & data integrity | 7 | 4 | 0 |
| 5 Usability | 6 | 0 | 1 |
| 6 Compatibility & interoperability | 4 | 0 | 0 |
| 7 Maintainability & testability | 4 | 0 | 0 |
| 8 Auditability | 6 | 0 | 0 |
| 9 Localisation | 3 | 0 | 0 |
| 10 Scalability & capacity | 0 | 2 | 2 |
| 11 Deployment & operations | 2 | 2 | 0 |
| **Total (73)** | **48** | **19** | **6** |

---

## 1. Performance & responsiveness (NFR-PERF)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-PERF-01 | Driver positions shall reach rider maps without the rider reloading. | Push over the `vehicle-locations` channel, with an initial fetch and a polling fallback; a status toggle shows on the marker "within a few seconds". | Met | E003, E021 |
| NFR-PERF-02 | A driver's device shall not flood the server with positions. | One broadcast per 2 seconds, throttled in the browser. | Partial — client-side only; no server limit (see NFR-SEC-03) | E021 |
| NFR-PERF-03 | The live map shall show only current vehicles. | A vehicle is "active" for 5 minutes after its last position; a scheduled job (`vehicle:offline-check`, every minute) clears the flag. | Met | E003, E021 |
| NFR-PERF-04 | Long lists shall not render all rows at once. | 10 rows per page for drivers, vehicles, commuters, tasks, violation codes/log, staff, receipts, top-ups, violations. | Met | README, E012–E028 |
| NFR-PERF-05 | The payment-confirmation screen shall not hammer the gateway. | Gateway lookup throttled to once per 4 seconds while the screen auto-refreshes. | Met | E006 |
| NFR-PERF-06 | A report that cannot be built in time shall fail cleanly, not hang. | Aggregation error is caught and the Admin is asked to shorten the range (no numeric time limit is defined). | Partial — every error, even a bug, shows as "taking too long" | E020 |
| NFR-PERF-07 | Map and routing calls shall be limited to the service area and a bounded result set. | Geocoding restricted to the service-area box, ≤ 6 matches, from 2 characters; ETA badges ≤ 5 km, nearest indicator ≤ 10 km. | Met | E003, E004 |

## 2. Security (NFR-SEC)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-SEC-01 | Passwords shall be stored only as salted hashes and be at least 8 characters. | bcrypt (`config/hashing.php`, default 10 rounds); minimum 8 on registration, reset, change and admin-set. | Partial — length only, no complexity or reuse rule | E002, E008, E011 |
| NFR-SEC-02 | A sign-in shall start a fresh session, and a password change/reset shall cut off other sessions. | Session id regenerated on login; other sessions ended on password change; remember token regenerated on reset. | Met | E001, E008, E011 |
| NFR-SEC-03 | Abuse-prone endpoints shall be rate limited. | Per minute: login 5, registration 6 (shared by commuter and driver), top-up 5, verification resend 6. | Partial — none on password-reset request, password change or location broadcast | E001, E002, E005, E008, E011, E021 |
| NFR-SEC-04 | State-changing requests shall be protected against cross-site forgery. | CSRF token on all web forms and the broadcast call; the only exemption is `webhooks/paymongo`. | Met | E006, E021, E027 |
| NFR-SEC-05 | Gateway callbacks shall be authenticated. | HMAC signature verified on the PayMongo webhook; invalid → HTTP 401, no payment touched. | Met | E006 |
| NFR-SEC-06 | Every page shall be restricted to the roles allowed to use it. | `auth`, `verified`, `role:*` and `account.active` middleware; wrong role → 403 or redirect. | Met | E024, E028, README |
| NFR-SEC-07 | A user shall only reach their own data. | Receipts filtered by owner (others → 404); broadcast accepted only for the driver's own vehicle; reset and verification links are signed/tokenised. | Met | E007, E021 |
| NFR-SEC-08 | Suspending an account shall take effect at once. | `account.active` re-checks the account on every authenticated request and signs the user out; broadcast re-checks suspension per call. | Met | E012, E018, E021, E028 |
| NFR-SEC-09 | The server shall never trust client-computed values. | Fare re-priced server-side; hours/overtime derived server-side; all inputs validated server-side (client checks are convenience). | Partial — trip **distance** still comes from the browser's routing call | E006, E013 |
| NFR-SEC-10 | Sign-in shall not reveal whether an email is registered. | One generic credentials-mismatch message. | Partial — the password-reset form does reveal it (and no throttle on that form) | E001, E011 |
| NFR-SEC-11 | Uploads shall be type- and size-restricted. | Licence image: JPG/JPEG/PNG, ≤ 2 MB; fare guide: PDF only. | Met | E012, E019, E022 |
| NFR-SEC-12 | Payment configuration shall fail safe. | Gateway disabled by default; refuses to arm on key/host mismatch, a live key outside `production`, or an empty key — and degrades to the declared-payment flow. Secrets are read from the environment, not source. | Met | E006, `docs/PAYMONGO_SETUP.md` |
| NFR-SEC-13 | Developer-only features shall not exist in production. | Dev markers are compiled out outside the `local` environment. | Partial — their routes are not role-gated | E021 |
| NFR-SEC-14 | Destructive actions shall be hard to trigger by accident. | Confirmation dialog on every delete / suspend; an Admin cannot delete or suspend themself. | Met | E012, E018, E028 |
| NFR-SEC-15 | API tokens shall be revocable with the session. | — | Gap — Sanctum personal access tokens are not revoked by "log out other devices" | E008 |
| NFR-SEC-16 | Data an export writes shall not run as a spreadsheet formula. | The Activity Log CSV prefixes any cell starting `=`, `+`, `-`, `@` with `'`. | Partial — the Reports CSV export (E020) writes emails and names as-is | E029, E020 |

## 3. Privacy (NFR-PRIV)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-PRIV-01 | A driver's exact position shall never be shown to other users. | Published coordinates are randomised within a **200 m** radius; the payload carries `privacy_radius`; popups say "approximate". | Met | E003, E021 |
| NFR-PRIV-02 | The system shall collect no more personal data than needed. | Commuters give only email + password; wallet and receipts hold no card data (gateway collects it). | Met | E002, E006 |
| NFR-PRIV-03 | Sensitive documents shall not be publicly readable. | Driver licence images are saved on the `public` disk (`storage/app/public/licenses`). | Gap — anyone who learns the path can fetch a licence image | E012, E022 |
| NFR-PRIV-04 | Deleting an account shall remove or anonymise the person's data without losing records the business must keep. | `User::eraseAccount()`: an account with no payments or top-ups is deleted; one with them is **anonymised** — email → `deleted-<id>@deleted.invalid`, password, roles, tokens, sessions, preferences, reset links, map markers, driver profile and licence image removed, suspended — and its wallet, payments and top-ups kept. Deletion is refused while the wallet holds money. | Partial — the original email stays in audit entries until they expire (365 days); a plain delete still leaves orphaned role / token rows (no personal data); no refund path for a wallet balance | E027, [ERD](Entity_Relationship_Diagram.md) |
| NFR-PRIV-05 | Users shall be able to read the terms and privacy policy they accept. | Links exist on the registration form. | Gap — both are placeholders (`href="#"`) | E002 |
| NFR-PRIV-06 | Users shall be told what is shared with outside services. | Pick-up / destination text and coordinates go to the geocoding and routing providers from the browser. | Partial — not disclosed anywhere in the product | E004 |
| NFR-PRIV-07 | Personal data held in the audit trail shall be limited, access-restricted and time-boxed. | Entries hold IP address, user agent and email (never passwords or tokens); readable only by the Admin; deleted after `ACTIVITY_LOG_RETENTION_DAYS` (365). | Partial — users are not told it is kept: the privacy policy is a placeholder (NFR-PRIV-05) | E029, [AUDIT_TRAIL](AUDIT_TRAIL.md) |

## 4. Reliability & data integrity (NFR-REL)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-REL-01 | A payment shall never be charged twice. | Unique transaction id; replays rejected; receipt shown by redirect from the stored record; settlement `pending → paid` happens once. | Met | E006 |
| NFR-REL-02 | A duplicate top-up shall not be credited twice. | Same user + amount + method within 2 minutes is refused. | Partial — a heuristic, not an idempotency key; blocks legitimate repeats | E005 |
| NFR-REL-03 | Multi-record writes shall be all-or-nothing. | Driver registration (user + driver + role) in one DB transaction with rollback and image clean-up; fare upload and rate save each in one transaction. | Met | E019, E022 |
| NFR-REL-04 | An outside failure shall not leave half-done state. | Gateway error → no payment row, no checkout session, funds kept; mail failure → no success message; routing/map failure → banner, no stale fare. | Met | E004, E006, E011 |
| NFR-REL-05 | Location broadcasts shall be safe to retry. | Same `(vehicle, timestamp)` is a no-op; a failed write is retried once after 5 s so the last position still publishes. | Partial — the 5 s delay needs an asynchronous queue driver (the default `QUEUE_CONNECTION=sync` ignores it); the browser does not retry | E021, `.env.example` |
| NFR-REL-06 | Stuck payments shall resolve themselves. | `pending` closed as `cancelled` after 12 hours, hourly and on page open. | Met | E006 |
| NFR-REL-07 | Records other data depends on shall not be deletable. | Blocked: driver with a vehicle, vehicle with a driver or service history, violation code in use, commuter with a balance, **payments and top-ups (an account that has them is anonymised; the foreign keys `RESTRICT`)**. | Met | E012, E014, E015, E018, E027 |
| NFR-REL-08 | Time-based housekeeping shall run unattended. | Scheduled: `auth:clear-resets` every 15 min, `vehicle:offline-check` every minute, `payments:expire-pending` hourly, `activitylog:clean` daily. | Met | `app/Console/Kernel.php` |
| NFR-REL-09 | The product shall keep working when an optional service is off. | Gateway off → declared payments; location denied → map without centring/ETA; map style down → banner; offline → polling pauses and resumes. | Met | E003, E006 |
| NFR-REL-10 | Related writes that must agree shall be atomic. | — | Partial — clock-in/out and the driver-status update are two writes; a top-up credits the wallet before writing its history row | E005, E009, E010 |
| NFR-REL-11 | Unexpected errors shall show a controlled message. | Most flows catch database errors and show a toast. | Partial — account deletion and a few others fall through to the default 500 page | E027 |

## 5. Usability (NFR-USE)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-USE-01 | Outcomes shall be reported consistently. | Success / error / warning shown as toasts; on a form error the modal stays open with the message under the failing input; on success it closes, the page refreshes and a toast confirms. | Met | README conventions |
| NFR-USE-02 | Map problems shall be explained in one place. | A single status banner with a fixed priority; clears itself. | Met | E003, E004 |
| NFR-USE-03 | The UI shall work on phones as well as desktops. | Separate mobile and desktop markup for lists; mobile-layout tests. | Met | `tests/Feature/MobileLayoutTest.php` |
| NFR-USE-04 | Forms shall help users get input right before submit. | Live checks for email, password length/match, Philippine mobile format (`+63 9XX XXX XXXX`), image type/size; password eye-toggle, strength meter and match indicator. | Met | E002, E011, E012, E018 |
| NFR-USE-05 | Empty and filtered-out lists shall say so. | Dedicated empty states with a *Clear filters* action. | Met | E007, E014 |
| NFR-USE-06 | Search and filter state shall survive paging. | Filters travel in the query string. | Met | E007, E014, E028 |
| NFR-USE-07 | The product shall be usable with assistive technology. | — | Gap — no accessibility requirement or audit exists (see §12) | — |

## 6. Compatibility & interoperability (NFR-COMP)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-COMP-01 | The product shall run in a current web browser with no install. | Needs JavaScript, the Geolocation API (for centring/ETA/driver broadcast) and WebGL (MapLibre GL). | Met | E003, E021 |
| NFR-COMP-02 | The server shall run on the documented stack. | PHP ≥ 8.0 (development on 8.2), Laravel 9, MySQL, Sanctum, Spatie permission + activity log, Pusher server SDK. | Met | `composer.json` |
| NFR-COMP-03 | External services shall be used through their public interfaces only. | OpenFreeMap tiles, Photon geocoding, OSRM routing, PayMongo hosted checkout + signed webhooks, Pusher/Echo, SMTP. | Met | E003, E004, E006, E011 |
| NFR-COMP-04 | Bundled front-end libraries shall be pinned. | `maplibre-gl` ^5, `laravel-echo` ^2, `pusher-js` ^8, `axios`. | Met | `package.json` |

## 7. Maintainability & testability (NFR-MNT)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-MNT-01 | Behaviour shall be covered by automated tests. | 29 PHPUnit feature-test files under `tests/Feature` (374 tests), including per-UCN regression suites, a 29-test PayMongo suite and the audit-trail suites (`AuditTrailTest`, `ActivityLogPageTest`) and `AccountErasureTest`. | Met | `tests/Feature` |
| NFR-MNT-02 | Requirements shall trace to code. | Each UCN has an *Implementation References* table (routes, controllers, services, views, models, tests). | Met | UCN sheets |
| NFR-MNT-03 | Environment-specific behaviour shall be configuration, not code. | Env-driven: payment gateway flags and keys, mail, session, queue and broadcast drivers, bcrypt rounds. | Met | `.env.example`, `config/` |
| NFR-MNT-04 | Code style shall be enforceable. | Laravel Pint is a dev dependency. | Met | `composer.json` |

## 8. Auditability (NFR-AUD)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-AUD-01 | Security-relevant actions shall be logged so that *who did what to which record, when and from where* can be answered. | Audit trail (`activity_log`) via `App\Support\Audit` and the `Audited` model trait. **Who**: the signed-in user, plus a snapshot of their email and role. **What / which record**: event name, a readable description, the subject record, and old → new values for changed business fields. **From where**: IP address and user agent on every entry. Categories (`log_name`): auth, account, driver, fleet, fare, violation, timekeeping, payment, system. Covers sign-in, failed and blocked sign-in (email tried), sign-out, password changes/resets, suspend / reactivate, driver approve / reject, account create / delete, vehicle, violation, maintenance, timekeeping, fare and payment changes. | Met | E001, E011, E012, E018, E028, `tests/Feature/AuditTrailTest.php` |
| NFR-AUD-02 | Financial and service history shall be preserved. | Payments are immutable once paid; suspended commuters keep wallet and receipts; vehicles with service history are marked *disposed*, never erased; **deleting an account anonymises it when payments or top-ups exist**, and the database refuses (`RESTRICT`) to delete a user or wallet that payments or top-ups reference. | Met | E006, E015, E018, E027, `AccountErasureTest` |
| NFR-AUD-03 | Report exports shall be attributable. | Every CSV export of a system report (E020) and of the Activity Log writes a `report_exported` / `activity_log_exported` entry: who, what, the date range or filters, the row count. | Met | E020, E029 |
| NFR-AUD-04 | Account self-deletion shall leave a trace. | A `deleted_self` entry (email, role, `outcome` deleted / anonymised, the acting user) is written before the sign-out, and the model's own delete event is suppressed so there is exactly one entry. | Met | E027, `AuditTrailTest`, `AccountErasureTest` |
| NFR-AUD-05 | An Admin shall be able to review the audit trail without database access. | Admin-only, read-only *Activity Log* page: newest first, 10 per page, search, category / event / date filters, expandable before → after detail, CSV export. | Met | E029, `tests/Feature/ActivityLogPageTest.php` |
| NFR-AUD-06 | The audit trail shall hold only what an audit needs and shall not grow without limit. | Page views, map polling, location broadcasts, availability toggles and diagnostics are **not** written (they go to `storage/logs`). Passwords, tokens and licence-image data are never stored. Entries older than `ACTIVITY_LOG_RETENTION_DAYS` (default 365) are deleted daily by `activitylog:clean`. `activitylog:purge-noise` clears the legacy "Action performed" rows. | Met | `config/activitylog.php`, `Kernel.php` |

## 9. Localisation (NFR-LOC)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-LOC-01 | All times shall be Philippine time. | Application timezone `Asia/Manila`; shifts stamped in that zone. | Met | `config/app.php`, E009, E010 |
| NFR-LOC-02 | Money shall be Philippine pesos. | `₱` with two decimals; fares rounded up to whole pesos; top-ups ₱10–₱100,000. | Met | E004, E005 |
| NFR-LOC-03 | Contact numbers shall be Philippine mobile numbers. | `9XX XXX XXXX`, optionally `0` or `+63`. | Met | E012, E022 |

## 10. Scalability & capacity (NFR-SCL)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-SCL-01 | Large tables shall not be loaded wholesale. | Timekeeping loads the latest 1,000 rows and the driver list loads every driver, then both filter/page in the browser. | Partial — fine at current size, will not scale | E012, E013 |
| NFR-SCL-02 | Growing history tables shall have a retention rule. | — | Gap — `vehicle_location_histories` grows without limit (every accepted broadcast adds a row) | E021 |
| NFR-SCL-03 | Server-side limits shall protect shared capacity. | — | Gap — no server rate limit on the broadcast endpoint; reports run live queries with no caching | E020, E021 |
| NFR-SCL-04 | The audit page shall stay responsive as the trail grows. | 10 entries per page; indexes on `log_name`, causer, subject and `created_at`; retention caps the table; the CSV export is capped at 10,000 rows. | Partial — free-text search (description, actor email) is a `LIKE` scan, fine at the retention size but not indexed | E029 |

## 11. Deployment & operations (NFR-OPS)

| ID | Requirement | Measure | Status | Source |
|---|---|---|:-:|---|
| NFR-OPS-01 | Production shall run the scheduler. | The four scheduled commands (NFR-REL-08) need a cron entry for `schedule:run`. Pending payments are also closed on page open, so payments degrade gracefully without it. | Met | `Kernel.php`, E006 |
| NFR-OPS-02 | Production shall use real push, mail and queue drivers. | Example env defaults to `BROADCAST_DRIVER=log`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=file`; live tracking needs Pusher credentials and password-reset/verification need a working `MAIL_MAILER`. | Partial — defaults suit development only | `.env.example`, E011, E021 |
| NFR-OPS-03 | The fare-guide import shall have its runtime available. | PDF extraction shells out to Python in the project virtualenv; a missing interpreter fails the upload with no fallback. | Partial — synchronous, single point of failure | E019 |
| NFR-OPS-04 | Payment gateway keys shall match the environment. | Sandbox keys outside `production`; live keys only in `production` (enforced in code). | Met | E006, `docs/PAYMONGO_SETUP.md` |

---

## 12. Not yet specified

The UCNs and configuration say nothing about these. They are listed so the team can decide whether to set a target —
no value has been invented here.

| Area | Question to settle |
|---|---|
| **Availability** | Uptime target and maintenance window? What happens to live tracking if Pusher is down for an hour? |
| **Response time** | Target page-load and API times (e.g. map first paint, checkout, report generation) and how they are measured? |
| **Capacity** | Expected number of vehicles, concurrent riders and drivers, payments per day? (Drives the broadcast rate limit, history retention and pagination decisions in NFR-SCL.) |
| **Backup & recovery** | Database backup schedule, retention, restore test, and recovery-time / recovery-point objectives — especially for the payment ledger. |
| **Accessibility** | Target standard (e.g. WCAG 2.1 AA), colour-contrast and keyboard checks — marker colours alone (green/red) already carry meaning. |
| **Browser / device support** | Minimum browser versions and phone models; behaviour without WebGL. |
| **Data protection** | Which law applies (e.g. the Philippine Data Privacy Act), retention periods for licence images and location history, breach process. |
| **Session policy** | Idle timeout (currently the framework default of 120 minutes) and whether staff roles need a shorter one or two-factor sign-in. |
| **Monitoring** | Error tracking, log retention, alerting on failed webhooks / `PAYMONGO … FAILED` lines. |
