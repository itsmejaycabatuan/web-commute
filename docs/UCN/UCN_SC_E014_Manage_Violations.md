# UCN_SC_E014 — Manage Violations

| Field                 | Value                                                                                                    |
| --------------------- | -------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E014                                                                                              |
| **Use Case Name**     | Manage Violations                                                                                        |
| **Primary Actor**     | Driver Manager (`role:driver_manager`)                                                                   |
| **Secondary Actor**   | System / Admin (read-only role boundaries)                                                               |
| **Goal**               | Allow the Driver Manager to maintain the violation code table and to log one or many violations against a driver. |
| **Trigger**           | Driver Manager selects **"Violation Codes"** or **"Violation Log"** on the sidebar (`GET /violation-codes`, `GET /violations-log`). |
| **Preconditions**     | 1. Driver Manager is authenticated and authorised to issue/manage violations.<br>2. Active violation codes are configured in the database. |
| **Supporting Actors** | User/Driver record, Violation code, Violation log                                                         |

> **Corrections vs. the original narrative**
> - **The identifier changed**: the original sheet was numbered `UCN_SC_E013`, which collided with *Manage Timekeeping*. Violations are now **UCN_SC_E014**.
> - The sidebar items are **"Violation Codes"** and **"Violation Log"** (singular), not *"Violation codes or Violation logs"*.
> - The codes table columns are **`Code`, `Violation Name`, `Actions`**; the log table columns are **`#`, `Driver`, `Violation`, `Date & Location`, `Fine`**.
> - **E2 (duplicate code) is now enforced on create**, not only on update — the original only caught it when editing.
> - **E5 (cannot delete an in-use code)** and **E6 (no future-dated violation)** were added after the original narrative was written.
> - **A7 (search the log)** is now a real server-side filter by driver name / licence number **and** date range; previously the page had no search at all.

---

## MAIN FLOW

| #   | Actor's Action                                                          | System Response                                                                                                            |
| --- | ------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver Manager opens the dashboard / map view.                          | System displays the dashboard with the driver-manager analytics.                                                            |
| 2   | Driver Manager clicks **"Violation Codes"** or **"Violation Log"**.    | System redirects to the matching management page (`driver-manager.violation-codes` / `driver-manager.violations-log`).      |
| 3   |                                                                            | System displays the table (codes, or the violation log with its **search bar**: driver name / licence, *from*, *to*, **Filter**, **Clear**). |

> Steps 4+ are the alternate flows (A1–A7); the main flow is "open a table and read it".

---

## Alternate Flow

**A1 – Create a Violation Code**
1. In the code list, the Driver Manager clicks **"Add Code"**.
2. System displays the code form: *Code*, *Violation Name*, *First*, *Second*, *Third*, *Fourth+* offense amounts and a *Revocation* flag.
3. Driver Manager fills the form and clicks **"Create Code"**.
4. System validates all fields **required** and `code` **unique** (E2), stores the row and confirms *"Violation code successfully added!"*

**A2 – Create Multiple Violation Codes (Bulk Add)**
1. In the code list, the Driver Manager clicks **"Bulk Add"**.
2. System displays the *Bulk Add Violation Codes* modal with repeatable rows.
3. Driver Manager fills one or more rows and clicks **"Create Code"**; rows without both a code and a name are skipped.
4. System validates and inserts every complete row, then confirms per-row.

**A3 – Edit a Violation Code**
1. In the code list, the Driver Manager clicks the edit icon on a row.
2. System displays the code form pre-filled with the stored values (the button reads **"Update Code"**).
3. Driver Manager changes the values and clicks **"Update Code"**.
4. System validates the fields (`code` **unique, ignoring the current row**) and confirms *"Violation code successfully updated!"*

**A4 – Delete a Violation Code**
1. In the code list, the Driver Manager clicks the delete icon.
2. System displays the *"Delete Code?"* confirmation.
3. Driver Manager clicks **"Delete"**.
4. System deletes the row and confirms *"Violation code successfully deleted!"* — unless E5 applies.

**A5 – Log a Violation**
1. In the log, the Driver Manager clicks **"New Entry"**.
2. System displays the violation form: *Driver*, *Violation Code*, *Offense Instance*, *Fine*, *Place of Violation*, *Date*, *Time*, *Remarks*.
3. Driver Manager fills the form and clicks **"Save Entry"**.
4. System validates `user_id`, `vc_id`, `violation_instance` **required, integer, min 1**, `violation_fine` **numeric, min 0**, `place_of_violation`, `date_of_violation` **date, not in the future** (E6), `time_of_violation`, `remarks`, stores the row and confirms *"Violation logged!"*

**A6 – Log Multiple Violations (Bulk Add)**
1. In the log, the Driver Manager clicks **"Bulk Add"**.
2. System displays the *Bulk Add Violations* modal with repeatable rows sharing one driver.
3. Driver Manager fills the rows and clicks **"Save Entry"**.
4. System validates every row (including the future-date rule) and confirms *"N violations logged successfully."*

**A7 – Search the Log**
1. Driver Manager types a driver name or licence number into the search box and/or picks a *from* / *to* date, then clicks **"Filter"**.
2. System narrows the log server-side to matching rows (driver name or licence `LIKE`, and `date_of_violation` within the range) and updates the entry counter; **Clear** restores the full log.
3. The *Driver* picker used by the entry modals is **not** filtered — it always lists every driver.

---

## Postconditions

1. A violation code has been added, modified or removed, and/or one or many violations have been logged.
2. The `violation_codes` and `violation_logs` records in the database have been updated.
3. The system notifies the Driver Manager with a success message.
4. A logged violation is permanently attached to the driver's profile (UCN_SC_E012 A4 shows the licence and expiry alongside it).

---

## Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Duplicate Violation Code**
The code already exists. System rejects the request on *code* with *"The code has already been taken."* — on create **and** on update (the update ignores the row being edited).

**E3 – Database Error**
The insert/update/delete fails at the database level. System cancels the operation and notifies the Driver Manager.

**E4 – No Logs Found**
The log is empty (or the active filter matches nothing). System renders an empty state — *"No violations recorded yet"* / *"No violations recorded"* — instead of an empty table; it does not push the actor to add an entry.

**E5 – Cannot Delete In-Use Code**
At least one violation log references the code. System refuses the deletion with *"This violation code is in use by existing violation logs and cannot be deleted."*

**E6 – Future Incident Date**
`date_of_violation` is in the future. System rejects the request with *"The date of violation must be a date before or equal to today."* (the date inputs are also capped at today in the browser). Nothing is stored — not in the single-entry flow and not in the bulk flow.

---

## Known Gaps / Notes

- **No unique index on `violation_codes.code`** — uniqueness is a validation rule only, so a concurrent double submit could still slip a duplicate through.
- **The log is not paginated**: `violationsLog()` loads every row before filtering and rendering.
- **The search is not combined with pagination or sorting**; there is no ordering option, only *latest first*.
- **Rejected applications cannot be re-approved** — the reject action is one-way in the UI (`is_rejected` has no counterpart action on the drivers page).
- The *offense instance* is free choice (1–4 in the bulk form, `min:1` in the single form) and is not derived from how many times that code was previously logged against the driver.
- Additional penalties are stored as free text; nothing derives them from the offense level.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `driver-manager.violation-codes`, `violation-codes.store`, `violation-codes.update`, `violation-codes.destroy`, `driver-manager.violations-log`, `violations-log.store`, `violations-log.store-bulk` (all inside `role:driver_manager`) |
| Controller | `app/Http/Controllers/DriverManagerController.php::violationCodes()`, `storeViolationCode()`, `updateViolationCode()`, `destroyViolationCode()`, `violationsLog()`, `storeViolationLog()`, `storeViolationLogBulk()`, `getPenaltyColor()` |
| Models | `app/Models/ViolationCode.php`, `app/Models/ViolationLog.php` (`user()`, `violationCode()`) |
| Views | `resources/views/driver-manager/violation-codes.blade.php`, `resources/views/driver-manager/violations-log.blade.php` |
| Menu | `config/menu.php` — *Violation Codes*, *Violation Log* (driver_manager) |
| Middleware | `auth`, `verified`, `role:driver_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_duplicate_violation_code_is_rejected_on_create`, `test_an_in_use_violation_code_cannot_be_deleted`, `test_a_future_violation_date_is_rejected`, `test_the_violation_log_can_be_searched_by_driver_name`) |
| Related | UCN_SC_E012 (Manage Drivers), UCN_SC_E013 (Manage Timekeeping) |