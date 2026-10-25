# Entity-Relationship Diagrams — SmartCommute

Two views of the same database, both generated from the **live database schema** (53 migrations; later `ALTER`s applied; `routes` and `fleets`, which migrations drop, are not shown):

| Diagram | Use it for | Files |
|---|---|---|
| **Summary ERD** — 16 main entities, key columns only | Reports, slides, onboarding | [PNG](diagrams/erd_summary.png) · [SVG](diagrams/erd_summary.svg) |
| **Full ERD** — all 35 tables, every column | Developers, data dictionary | [PNG](diagrams/erd_full.png) · [SVG](diagrams/erd_full.svg) |

Notation is crow's foot: `||` exactly one · `|o` zero or one · `>o` zero or many.
A **dashed** line means the tables are linked by an id column but the database has **no foreign key** for it.
Both diagrams are produced by [`diagrams/build_erd.py`](diagrams/build_erd.py) (needs Graphviz). After a migration, update the
`SCHEMA` / `REL` data at the top of that script and re-run `python3 docs/diagrams/build_erd.py`; the `SCHEMA` and `REL` blocks can be
regenerated from `information_schema` (columns, `key_column_usage`, single-column unique indexes) so the picture cannot drift from the database.

> **Verified against the live database on 2026-10-25** (after the vehicle-attribution, driver-vacancy, scan-session and timestamp migrations): the **35 tables**
> (`migrations` excluded), every column with its nullability and primary key, and all **42 foreign keys** match `information_schema` exactly:
> **27 `ON DELETE CASCADE`, 12 `SET NULL` and 3 `RESTRICT`** (payments and top-ups).
> Since the 2026-10-21 version the schema gained eight tables (`email_verification_codes`, `qr_codes`, `qr_scans`, `driver_vehicle`, `vehicle_duty_days`,
> `driver_vacancies`, `vehicle_scan_sessions`, `vehicle_maintenance_logs`) and `vehicles.driver_id` was removed (replaced by `driver_vehicle`).

---

## 1. Summary ERD

![SmartCommute summary ERD](diagrams/erd_summary.png)

### What the entities are

| Entity | Table | Holds | Related UCNs |
|---|---|---|---|
| User | `users` | Every account — commuter, driver, staff, admin — with email, hashed password, verification and suspension state, and `anonymised_at` (set when the account was erased but kept because payments reference it) | E001, E002, E008, E018, E026, E027 |
| Role | `roles` | The five roles (via `model_has_roles`; Spatie permission package) | E027 |
| Wallet | `wallets` | A commuter's stored balance | E005, E006 |
| Top-up History | `topup_histories` | One row per wallet top-up, with a status | E005 |
| Payment | `payments` | One row per fare payment: gateway ids, lifecycle status, and — when the commuter's QR scan was located — the `vehicle_id` and `driver_id` the fare belongs to | E006, E007 |
| Fare / Fare Rate | `fares`, `fare_rates` | A published fare guide and its distance tiers (regular and discount price) | E004, E019 |
| Driver | `drivers` | Driver profile attached to a user: licence, approval, suspension, availability, and the inactivity-warning columns (`inactivity_warned_at`, `reactivated_at`) | E012, E022 |
| Time Keeping | `time_keepings` | One row per driver per day: in, out, hours, overtime, leave, the vehicle worked, and the auto-close markers (`auto_closed_at`, `reviewed_at`) | E009, E010, E013 |
| Violation Code | `violation_codes` | Offence catalogue with fines for 1st–4th+ offence (seeded with the PUJ schedule `UV01`–`UV14`) | E014, E024 |
| Violation Log | `violation_logs` | A violation recorded against a driver's user account, with its payment status | E014 |
| Driver–Vehicle | `driver_vehicle` | Which driver holds which vehicle. `driver_id` is **unique**: a driver holds one vehicle; a vehicle has at most two drivers (an application rule) | E012, E015 |
| Vehicle | `vehicles` | A fleet vehicle (no longer carries a driver column) | E015 |
| Maintenance Task | `maintenance_tasks` | A kind of service and its km / month interval | E017 |
| Preventive Maintenance | `preventive_maintenances` | Last service of one task on one vehicle — the junction that makes Vehicle ↔ Task many-to-many | E016 |
| Vehicle Location | `vehicle_locations` | A vehicle's latest broadcast position | E021, E003 |

### Relationships — every foreign key

| Parent | Child | Foreign key | Meaning | On parent delete |
|---|---|---|---|---|
| User | Wallet | `wallets.user_id` | owns (0..1) | cascade (only reached when there is no payment or top-up — see §3) |
| Wallet | Top-up History | `topup_histories.wallet_id` | receives | **restrict** |
| User | Top-up History | `topup_histories.user_id` | requests | **restrict** |
| User | Payment | `payments.paid_by` | pays | **restrict** |
| Vehicle | Payment | `payments.vehicle_id` (nullable) | the fare was paid on | set null |
| Driver | Payment | `payments.driver_id` (nullable) | the fare belongs to the driver on duty | set null |
| User | User preferences | `user_preferences.user_id` | prefers | cascade |
| User | Email verification code | `email_verification_codes.user_id` (unique) | holds a pending 6-digit code | cascade |
| User | Driver | `drivers.user_id` | is profile of (0..1) | cascade |
| Driver | Time Keeping | `time_keepings.driver_id` | records | cascade |
| Vehicle | Time Keeping | `time_keepings.vehicle_id` (nullable) | the vehicle a shift was worked on | set null |
| Driver | Driver–Vehicle | `driver_vehicle.driver_id` (unique) | holds | cascade |
| Vehicle | Driver–Vehicle | `driver_vehicle.vehicle_id` | is driven by | cascade |
| Vehicle | Vehicle Duty Day | `vehicle_duty_days.vehicle_id` | has a roster | cascade |
| Driver | Vehicle Duty Day | `vehicle_duty_days.driver_id` | drives on | cascade |
| Vehicle | Driver Vacancy | `driver_vacancies.vehicle_id` | lost a driver | cascade |
| Driver | Driver Vacancy | `driver_vacancies.former_driver_id` (nullable) | was the driver who left | set null |
| Driver | Driver Vacancy | `driver_vacancies.replacement_driver_id` (nullable) | was assigned instead | set null |
| User | Violation Log | `violation_logs.user_id` | is cited in | cascade |
| Violation Code | Violation Log | `violation_logs.vc_id` | classifies | cascade |
| Vehicle | Preventive Maintenance | `preventive_maintenances.vehicle_id` | is serviced by | cascade |
| Maintenance Task | Preventive Maintenance | `preventive_maintenances.task_id` | is performed in | cascade |
| Vehicle | Vehicle Maintenance Log | `vehicle_maintenance_logs.vehicle_id` | has history | cascade |
| Maintenance Task | Vehicle Maintenance Log | `vehicle_maintenance_logs.maintenance_task_id` | is performed in | cascade |
| Preventive Maintenance | Vehicle Maintenance Log | `vehicle_maintenance_logs.maintenance_id` (nullable) | was logged from | set null |
| User | QR Code | `qr_codes.created_by` (nullable) | generated | set null |
| User | QR Scan | `qr_scans.user_id` | scanned | cascade |
| QR Code | QR Scan | `qr_scans.qr_code_id` (nullable) | was scanned | set null |
| Vehicle | QR Scan | `qr_scans.vehicle_id` (nullable) | the PUJ the commuter was on | set null |
| Driver | QR Scan | `qr_scans.driver_id` (nullable) | the driver on duty there | set null |
| Payment | QR Scan | `qr_scans.payment_id` (nullable) | spent the scan | set null |
| User | Vehicle Scan Session | `vehicle_scan_sessions.user_id` | started a phone scan | cascade |
| Vehicle | Vehicle Scan Session | `vehicle_scan_sessions.vehicle_id` (nullable) | was created from it | set null |
| Fare | Fare Rate | `fare_rates.fare_id` | contains | cascade |
| User | Vehicle Location | `vehicle_locations.user_id` (nullable) | broadcasts | cascade |
| Vehicle | Vehicle Location | `vehicle_locations.vehicle_id` | reports — **no foreign key** (dashed) | none |
| Vehicle Location | Location History | `vehicle_location_histories.vehicle_location_id` | has a breadcrumb trail | cascade |
| User | Location History | `vehicle_location_histories.user_id` (nullable) | broadcast | cascade |
| User | Dev marker | `dev_markers.user_id` | placed | cascade |
| Role / Permission | pivots | `model_has_roles.role_id`, `model_has_permissions.permission_id`, `role_has_permissions.*` | Spatie permission tables | cascade |
| User ↔ Role | — | via `model_has_roles` | has (many-to-many) | pivot rows are not removed with the user (no foreign key) |

---

## 2. Full ERD

![SmartCommute full ERD](diagrams/erd_full.png)

*The PNG is large (≈5,200 px wide). For a document, use the SVG and zoom, or paste the PNG on a landscape page.*

### Table groups

| Group (colour) | Tables |
|---|---|
| Identity & access (blue) | `users`, `user_preferences`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `personal_access_tokens`, `sessions`, `password_resets`, `email_verification_codes` |
| Wallet, payments & ride QR (green) | `wallets`, `topup_histories`, `payments`, `qr_codes`, `qr_scans` |
| Fares (teal) | `fares`, `fare_rates` |
| Drivers & violations (purple) | `drivers`, `time_keepings`, `violation_codes`, `violation_logs` |
| Fleet & maintenance (olive) | `vehicles`, `driver_vehicle`, `vehicle_duty_days`, `driver_vacancies`, `maintenance_tasks`, `preventive_maintenances`, `vehicle_maintenance_logs`, `vehicle_scan_sessions` |
| Live tracking (orange) | `vehicle_locations`, `vehicle_location_histories`, `dev_markers` |
| Framework (dark grey) | `activity_log`, `failed_jobs` |

### Tables not in the summary

| Table | Purpose |
|---|---|
| `user_preferences` | Per-user theme and font size (1 : 0..1 with `users`). |
| `email_verification_codes` | The hashed 6-digit code that verifies an email address as an alternative to the link: `code_hash`, `attempts` (burned after 5 wrong guesses), `expires_at`; one per user (UCN_SC_E002). |
| `permissions`, `role_has_permissions`, `model_has_permissions` | Spatie permission tables. The seeder gives the admin role four `… routes` permissions, but no code checks a permission — access is decided by **role** only. |
| `model_has_roles` | Links a user (`model_type` + `model_id`, polymorphic) to a role. |
| `personal_access_tokens` | Laravel Sanctum API tokens (polymorphic `tokenable`). |
| `sessions` | Server-side sessions; `user_id` is indexed but has no foreign key. |
| `password_resets` | Reset tokens keyed by email, not by user id. |
| `qr_codes` | The ride QR codes the Admin generates: a 40-character `token`, `is_active`, `created_by`. Not tied to a vehicle (UCN_SC_E006). |
| `qr_scans` | A commuter's scan of a code: `scanned_at` (valid 15 minutes), `used_at` / `payment_id` once spent, and — when the checkout located the commuter — the matched `vehicle_id`, `driver_id`, `matched_distance_m` and `located_at`. **The commuter's coordinates are never stored.** |
| `vehicle_duty_days` | The duty roster of a shared vehicle: one row per `(vehicle_id, duty_date)` (unique together) naming the driver who may drive it that day (UCN_SC_E015 A6). |
| `driver_vacancies` | A seat left empty when a driver was suspended or their licence expired: `reason`, `target_drivers` (how many drivers the vehicle should have again), `opened_at`, `resolved_at`, `resolution` (`filled` / `not_needed`), `replacement_driver_id`, `last_notified_at` (UCN_SC_E012 A8). |
| `vehicle_maintenance_logs` | Every service ever logged (append-only history); `maintenance_id` is nullable and cleared, not cascaded, when its schedule row goes (UCN_SC_E016). |
| `vehicle_scan_sessions` | The hand-off between the Add Vehicle form and a phone: a 40-character `token`, `status`, `expires_at`, and JSON `files` (paths on the **private** disk) and `result` (what was read). Both are emptied when the vehicle is saved, the form is closed or the session expires (UCN_SC_E015 A7). |
| `vehicle_location_histories` | Breadcrumb row for each accepted position broadcast, with `distance_from_last_pos` (kilometres since the previous ping; 0 for the first ping, after a 10-minute gap or an implausible jump) (UCN_SC_E021). |
| `dev_markers` | Developer-only synthetic map markers (E021 A4, `local` environment). |
| `activity_log` | The audit trail ([AUDIT_TRAIL.md](AUDIT_TRAIL.md)); `causer_id` / `subject_id` are polymorphic, no foreign key. Indexed on `log_name`, causer, subject and — since `2026_10_21_100000` — `created_at`. Read by the admin Activity Log page (UCN_SC_E028). |
| `failed_jobs` | Laravel queue failures. |

---

## 3. Things the diagrams show that the UCNs do not say

1. **`routes` and `fleets` were removed.** They were created early, never used (no model, controller, view, seeder or test touched them;
   `fleets` also duplicated `vehicles`) and are dropped by `2026_10_21_090000_drop_unused_routes_and_fleets_tables` — applied on the
   dev database on 2026-10-21. Any other environment still has them until `php artisan migrate` is run there.
   By contrast **`vehicle_locations` is essential**: it is the only source of the live map and ETA (E003), the table the driver
   broadcast writes (E021), and the parent of `vehicle_location_histories`. Its history is tied to it by a cascading key, so **deleting a position row
   deletes its breadcrumbs**: the automatic shift close therefore ages the rows past the 5-minute live window instead of deleting them.
2. **`vehicle_locations.vehicle_id` is a plain string with no foreign key** to `vehicles.id`, and `vehicle_locations` has
   no unique key per vehicle. The link exists only in code (E021), so a deleted vehicle can leave position rows behind.
3. **Deleting a user can no longer destroy the ledger.** Every other foreign key to `users` still cascades (wallet, preferences, location rows, driver profile and from it
   timekeeping, violation logs, QR scans, scan sessions…), but `payments.paid_by`, `topup_histories.user_id` and `topup_histories.wallet_id` are `RESTRICT`: a delete that would remove a payment or
   top-up is refused by the database. The application never relies on that — `User::eraseAccount()` deletes an account that has none, and **anonymises** one that has
   (email → `deleted-<id>@deleted.invalid`, password, roles, tokens, sessions, preferences and driver profile removed, `anonymised_at` set, suspended), keeping the user row, wallet, payments and top-ups.
   Rows with no foreign key — role assignments, sessions, API tokens, password-reset rows and `activity_log` entries — are not removed by either path except as `anonymise()` does explicitly.
   (Before 2026-10-21 everything cascaded, so a commuter deleting their account erased their payment history.)
4. **Deleting a vehicle or a driver keeps the money and the history.** A vehicle or driver that a payment, QR scan, time entry or vacancy points at is removed from
   those rows with `SET NULL` rather than deleting them; only their assignment (`driver_vehicle`), roster (`vehicle_duty_days`) and, for a driver, time-keeping rows cascade.
   The application still refuses to delete a driver who holds a vehicle (E012 E8) and a vehicle that has a driver or service history (E015).
5. **`violation_logs.user_id` points at `users`, not `drivers`.** A violation belongs to the driver's *user account*;
   it is not linked to the driver profile directly.
6. **One vehicle per driver is a database rule; two drivers per vehicle is an application rule.** `driver_vehicle.driver_id` is unique. Nothing in the
   schema caps a vehicle at two drivers (`Vehicle::MAX_DRIVERS`, enforced in code), and `vehicle_duty_days` does not check that its driver is one of the vehicle's two.
7. **`time_in` / `time_out` are strings** (`"08:00"` or `"08:00 AM"`), not times (noted in E009 / E013).
8. **`payments.paid_at` is nullable** since the gateway lifecycle migration (a pending payment has none).
9. **`violation_codes.code` and `maintenance_tasks.tasks_performed` are not unique** at the database level (E014, E017).
10. **MariaDB's silent `ON UPDATE CURRENT_TIMESTAMP`.** Until `2026_10_28_100000_stop_timestamps_updating_themselves`, the first NOT NULL `timestamp` column of
    `qr_scans` (`scanned_at`), `driver_vacancies` (`opened_at`) and `vehicle_scan_sessions` (`expires_at`) was reset to "now" by any unrelated update. They are now plain
    `TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP`. (`vehicle_locations.last_update` still has the automatic update; the live map relies on it.)
11. **Photos of registration papers are not in the database.** `vehicle_scan_sessions.files` holds only paths on the private disk (`storage/app/vehicle-scans/{id}/`);
    the files and the read results are deleted when the vehicle is saved, the form is closed or the session expires, and `vehicle-scans:purge` removes anything left.
