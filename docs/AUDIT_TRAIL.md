# Audit trail (`activity_log`)

What the system records about who did what, how to read it, and how to add to it.
Code: [`app/Support/Audit.php`](../app/Support/Audit.php) · [`app/Models/Concerns/Audited.php`](../app/Models/Concerns/Audited.php) ·
config [`config/activitylog.php`](../config/activitylog.php) · tests [`tests/Feature/AuditTrailTest.php`](../tests/Feature/AuditTrailTest.php).
Requirements: NFR-AUD-01 … 06 in [Non_Functional_Requirements.md](Non_Functional_Requirements.md); FR-ADM-14 … 18 in [Functional_Requirements.md](Functional_Requirements.md); use case [UCN_SC_E029](UCN/UCN_SC_E029_View_Activity_Log.md).

## Why it was rebuilt

Before, 96 % of the table (9,256 of 9,642 rows on the dev database) was `Action performed: <method>` — every page view, every
map poll (7,739 rows), every location broadcast — written at the *start* of a request, before validation, with no record named and
no values. The real events were buried and the table grew with every open map. Now only things worth auditing are written, after
they happen, with the record and the change.

**Dev database, 2026-10-21:** `activitylog:purge-noise` removed the noise, leaving 386 rows — the genuine sign-in, sign-out and staff
entries from before the rebuild. They carry no record, values or IP and show as *Legacy* on the page; they age out under the
retention rule.

## What a row looks like

| Column | Holds |
|---|---|
| `log_name` | **Category** — `auth`, `account`, `driver`, `fleet`, `fare`, `violation`, `timekeeping`, `payment` (filter on this) |
| `event` | **Machine name** — see the catalogue below |
| `description` | A sentence a person can read, e.g. `Created vehicle ABC 123`, `Approved driver DRV-000012` |
| `causer_type` / `causer_id` | **Who** — the signed-in user (null for a guest or the payment gateway) |
| `subject_type` / `subject_id` | **Which record** the event is about (may be gone if it was deleted) |
| `properties.attributes` / `properties.old` | **New / old values** of the business fields that changed (only those) |
| `properties.context` | `ip`, `user_agent`, `actor_email`, `actor_role` — a snapshot, so it survives the actor's account being deleted |
| `properties.*` | Event-specific facts (`reason`, `role`, `source`, `changes`, …) |
| `created_at` | When |

Never stored: passwords or hashes, remember tokens, licence-image data, card or gateway secrets, the password a failed sign-in tried.

## Event catalogue

| Category | Events | Written by |
|---|---|---|
| `auth` | `login`, `login_failed` (email tried), `login_blocked` (suspended account), `logout`, `password_changed`, `password_reset`, `password_set_by_admin`, `sessions_revoked` | `Audit::log()` in the controllers |
| `account` | `registered`, `created` (by an admin; has role), `updated` (email / verification), `deleted` / `anonymised` (by an admin), `deleted_self` (own account; `outcome` = deleted or anonymised), `suspended`, `reactivated`, `role_changed` | `Audit::log()` + `User` model |
| `driver` | `registered` (application submitted), `created` (by an admin), `updated` (licence data; approval and rejection read `Approved driver …` / `Rejected driver …`), `deleted`, `suspended` (reason, vehicle released), `reactivated` | `Audit::log()` + `Driver` model |
| `fleet` | `created` / `updated` / `deleted` for vehicles, maintenance tasks and service records | `Vehicle`, `MaintenanceTask`, `PreventiveMaintenance` models |
| `violation` | `created` / `updated` / `deleted` for violation codes and violation logs | `ViolationCode`, `ViolationLog` models |
| `timekeeping` | `created` / `updated` for time entries (clock-in/out and manager entries — the causer tells them apart) | `TimeKeeping` model |
| `payment` | `created` / `updated` for payments (`Payment SC-… marked paid / failed / cancelled`), `created` for top-ups | `Payment`, `TopupHistory` models |
| `fare` | `created` (fare guide), `fare_guide_imported` (rows), `fare_rates_updated` (one entry, per-tier old → new) | `Fare` model + `FareController` |
| `system` | `report_exported` (which report, date range), `activity_log_exported` (rows, filters) | `ReportController`, `ActivityLogController` |

## Deliberately not recorded

Page views and listings · the map's `/track/vehicles/active` polling · location broadcasts · driver availability toggles and the
clock-in/out status flip · theme and font preferences · developer map markers · `vehicles.is_active` (a heartbeat flag).
Database and gateway failures go to `storage/logs/laravel.log` (`Log::error` with context), not here.

## Behaviours worth knowing

- **Automatic vs explicit.** Changes to a model that uses `Audited` are logged by Eloquent events. Writes that bypass Eloquent
  (`Model::where()->update()`, `DB::table()->insert()`) fire no events, so those places call `Audit::log()` by hand
  (fare rates, fare-guide import).
- **No double entries.** Where one action would produce both a model event and an explicit entry (creating a user, deleting an
  account, deleting a driver), the model event is switched off (`$recordEvents` on `User` / `Driver`, or `disableLogging()` on the
  instance) and the explicit entry carries the extra context.
- **Self-deleting an account is logged before the sign-out**, so the entry still knows who did it, and keeps the email and the outcome. An account with
  payments or top-ups is **anonymised**, not deleted (`User::eraseAccount()`), so the ledger survives; those rows are no longer removed by any delete — the
  foreign keys are `RESTRICT` — see [Entity_Relationship_Diagram.md](Entity_Relationship_Diagram.md) §3 and UCN_SC_E027.
- **Auditing never breaks a request.** `Audit::log()` catches its own failures and writes `AUDIT LOG FAILED` to the Laravel log
  (tested). The cost is that a lost entry is only visible there.
- **Retention.** `activitylog:clean` runs daily and deletes entries older than `ACTIVITY_LOG_RETENTION_DAYS` (default 365). The
  scheduler must be running (`php artisan schedule:run` from cron).
- **Old rows.** Entries written before this change are not rewritten. Remove the legacy noise once, deliberately:
  `php artisan activitylog:purge-noise --dry-run`, then without `--dry-run`. It only matches `Action performed:%`. (Done on the dev
  database on 2026-10-21; other environments still need it.)

## Adding to it

A new model — add the trait and say what is safe to store:

```php
use App\Models\Concerns\Audited;
use App\Support\Audit;

class Thing extends Model
{
    use Audited;

    protected function auditCategory(): string  { return Audit::FLEET; }
    protected function auditAttributes(): array { return ['name', 'status']; }   // never secrets, blobs or heartbeats
    protected function auditLabel(): string     { return 'thing '.$this->name; }  // "Created thing Foo"
}
```

An event that is not a model change:

```php
Audit::log(Audit::ACCOUNT, 'suspended', 'Suspended commuter account '.$user->email, $user, ['reason' => $reason]);
```

Keep descriptions free of `:word` (Spatie treats it as a placeholder), keep event names lowercase with underscores, and add a test
to `AuditTrailTest` — it asserts the actor, the subject, the old/new values and that no secret appears in any entry.

## The admin page — Activity Log

Admin sidebar → **Activity Log** (`GET /activity-log`, UCN_SC_E029). Read-only, admin role only, 10 entries per page, newest first.

| Part | What it does |
|---|---|
| List | When · Who (email, role) · What happened (category + event badge + description) · Record · IP. Click a row for the **before → after** table, extra facts (e.g. suspension reason), device and browser string. Fare-rate edits expand to a per-distance table. |
| Header | Entries (matching), written today, and a link to the **failed sign-ins in the last 24 h** when there are any. |
| Filters | Text search (description, acting user's email, or the email a failed sign-in tried — `%` and `_` are literal) · category · event · from / to date. They combine and survive paging. |
| Export | **Export CSV** downloads what the filters match (newest 10,000), formula-safe, and writes an `activity_log_exported` entry. Report CSV exports write `report_exported`. |
| Hidden | Legacy `Action performed: …` rows are excluded from the list and export until `activitylog:purge-noise` deletes them. |
| Safe | Everything is HTML-escaped; keys that look like secrets (`password`, `token`, `secret`, `card`, `license_image_data`) are masked even if one were ever stored. Viewing writes nothing. |

Code: [`ActivityLogController`](../app/Http/Controllers/Admin/ActivityLogController.php) ·
[`AuditPresenter`](../app/Support/AuditPresenter.php) (row → readable data) · [`AuditCatalog`](../app/Support/AuditCatalog.php) (names and colours) ·
[view](../resources/views/admin/activity-log/index.blade.php) · tests [`ActivityLogPageTest`](../tests/Feature/ActivityLogPageTest.php).

When you add a **new event or category**, add its label to `AuditCatalog` — it still shows without one (raw name, neutral colour) but is not offered in the filters.
The `created_at` index (`2026_10_21_100000_add_created_at_index_to_activity_log`) keeps the date filter and the daily prune fast.

## Rolling out to another environment

The dev database already has all of this. For staging or production, in order:

1. Deploy the code. Add `ACTIVITY_LOG_RETENTION_DAYS=365` to `.env` (optional — 365 is the default).
2. `php artisan migrate` — drops the unused `routes` / `fleets` tables and adds the `created_at` index on `activity_log`.
3. `php artisan activitylog:purge-noise --dry-run`, then without `--dry-run` — clears the legacy "Action performed" rows.
4. Make sure the scheduler runs (`* * * * * cd <app> && php artisan schedule:run`). Without it `activitylog:clean` never prunes, and the payment-expiry,
   offline-vehicle and reset-token jobs stop too.
5. Sign in as an Admin and open **Activity Log**: a fresh sign-in should appear within seconds, with your IP.
