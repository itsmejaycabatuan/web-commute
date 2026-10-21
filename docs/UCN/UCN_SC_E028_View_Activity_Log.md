# UCN_SC_E028 — View Activity Log

| Field                 | Value                                                                                                   |
| --------------------- | --------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E028                                                                                             |
| **Use Case Name**     | View Activity Log                                                                                       |
| **Primary Actor**     | Admin (`role:admin`)                                                                                    |
| **Secondary Actor**   | System (audit trail, CSV writer)                                                                        |
| **Goal**              | Allow the Admin to review the audit trail — who did what, to which record, when and from where — and to export what they find. |
| **Trigger**           | Admin clicks the **"Activity Log"** item on the sidebar (`GET /activity-log`).                           |
| **Preconditions**     | 1. Admin is authenticated and registered to the system.<br>2. The audit trail has been written by the system (UCN actions that create, change, delete, sign in or export — see `docs/AUDIT_TRAIL.md`). |
| **Supporting Actors** | Activity log (`activity_log`), User record                                                              |

> **Scope.** The page is **read-only**: no entry can be edited, deleted or added from it. Only the Admin may open it; every other role is refused (403) and a guest is sent to the login page. **Viewing the page writes nothing** to the trail; exporting does.

---

## MAIN FLOW

| #   | Actor's Action                                   | System Response                                                                                                                                                                                                                             |
| --- | ------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Admin clicks **"Activity Log"** on the sidebar.  | System verifies the `admin` role and lists the entries **newest first, 10 per page**, with the number of entries, how many were written today and — when there are any — how many **failed sign-ins** happened in the last 24 hours (a link to those entries). |
| 2   |                                                  | Each row shows **When** (Philippine time), **Who** (the signed-in user's email and role, or *Not signed in / system*), **What happened** (a category badge, an event badge and a readable description), **Record** (e.g. *Vehicle #12*) and the **IP address**. |
| 3   | Admin clicks a row.                              | System expands it: **What changed** — a *Field / Before / After* table of the business fields that changed (a fare-rate edit shows a *Distance / Regular / Discount* table instead) plus any extra facts (e.g. the suspension reason) — and **Where it came from**: entry number, exact time, IP address, device (e.g. *Chrome on Windows*) and the browser string. |

---

## Alternate Flow

**A1 – Search**
1. Admin types into the search box and presses **Filter**.
2. System narrows the list to entries whose description, **acting user's email** or — for failed sign-ins — the **email that was tried** contains the text. `%` and `_` are matched literally.

**A2 – Filter by category and event**
1. Admin picks a **category** (*Sign-in & security, Accounts, Drivers, Fleet & maintenance, Violations, Timekeeping, Payments, Fares, System*) and/or an **event** (e.g. *Failed sign-in, Suspended, Role changed, Fare rates updated*).
2. System applies it at once; the filters survive paging and combine with each other and with A1 / A3.

**A3 – Filter by date range**
1. Admin sets a **from** and/or **to** date and presses **Filter**.
2. System keeps the entries written from the start of the first day to the end of the last day, inclusive.

**A4 – Clear the filters**
1. Admin presses **Clear**. System restores the full list.

**A5 – Export to CSV**
1. Admin presses **Export CSV**.
2. System writes a UTF-8 CSV of **everything the current filters match** (newest first, at most **10,000 rows**) named `smartcommute_activity_log_<YYYY-MM-DD>.csv`, with the columns *Entry, Time, Category, Event, Actor email, Actor role, Description, Record, IP address, Device, Changes, Details*. A cell that would start with `=`, `+`, `-` or `@` is prefixed with `'` so a spreadsheet cannot run it as a formula.
3. System records **the export itself** as an entry (`activity_log_exported`, with the row count and the filters used) — so an export is always attributable.

**A6 – Jump to failed sign-ins**
1. Admin clicks the *failed sign-ins in the last 24 h* link in the page header.
2. System applies *Event = Failed sign-in* from yesterday onward.

---

## Postconditions

1. The Admin has reviewed the entries that match the chosen filters; nothing was changed.
2. **Viewing** the page and the list wrote nothing to the trail.
3. After an export, one `activity_log_exported` entry exists naming the Admin, the number of rows and the filters.
4. Entries written by the previous, noisy logging ("Action performed: …") are **hidden** from the list and the export; they are removed for good with `php artisan activitylog:purge-noise`.

---

## Exceptions

**E1 – Not an Admin**
A Driver Manager, Maintenance Manager, Driver or Commuter requests the page or the export. System answers 403; a guest is redirected to the login page. Nothing is shown or exported.

**E2 – End Date Before Start Date**
The *to* date is earlier than the *from* date. System rejects the request with *"The end date must be on or after the start date."*, shows no result and keeps the form.

**E3 – Unknown Category or Event**
The URL carries a category or event the system does not write. System rejects it with the framework validation message and runs no query on it.

**E4 – No Entries Found**
Nothing matches the filters (or nothing has been recorded yet). System shows *"No entries match your filters"* / *"Nothing has been recorded yet"* instead of an empty table.

**E5 – Export Larger Than the Cap**
More than 10,000 entries match. System exports the newest 10,000; the Admin narrows the dates to reach older ones.

---

## Known Gaps / Notes

- **Entries are kept 365 days** (`ACTIVITY_LOG_RETENTION_DAYS`); the daily `activitylog:clean` job deletes older ones, so the page cannot show history beyond that. The scheduler must be running.
- **Entries written before the audit trail was rebuilt** have a *Legacy* category, no record, no field values and no IP address. They are listed (except the "Action performed" noise) but carry little detail.
- **The record link is a label, not a link.** An entry names its record (*Vehicle #12*), but the page does not open that record, and the record may since have been deleted — the description and `Details` still say what it was.
- **Event and category lists are fixed** (`app/Support/AuditCatalog.php`). A new event type still appears in the list with its raw name, but is not offered in the *Event* filter until it is added there.
- **Page size is 10**, in line with every other list; the export is the way to read many entries at once.
- **No live refresh** — the list is as of the page load.
- **Search scans the table** (no full-text index); it is quick at the retention size used here.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `activity-log.index`, `activity-log.export`, inside `role:admin` |
| Controller | `app/Http/Controllers/Admin/ActivityLogController.php` — `index()`, `export()` |
| Presentation | `app/Support/AuditPresenter.php` (row → readable data, masks secrets), `app/Support/AuditCatalog.php` (names and colours) |
| View | `resources/views/admin/activity-log/index.blade.php` |
| Menu | `config/menu.php` — *Activity Log* (admin) |
| Writing the trail | `app/Support/Audit.php`, `app/Models/Concerns/Audited.php`; design in `docs/AUDIT_TRAIL.md` |
| Schema | `activity_log`; `database/migrations/2026_10_21_100000_add_created_at_index_to_activity_log.php` |
| Tests | `tests/Feature/ActivityLogPageTest.php`, `tests/Feature/AuditTrailTest.php` |
| Related | UCN_SC_E001 (Login — sign-in events), UCN_SC_E018 / E027 (suspensions, staff), UCN_SC_E020 (report export, also audited) |
