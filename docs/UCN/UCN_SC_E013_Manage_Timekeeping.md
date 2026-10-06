# UCN_SC_E013 — Manage Timekeeping

| Field                 | Value                                                                                                  |
| --------------------- | ------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN_SC_E013                                                                                            |
| **Use Case Name**     | Manage Timekeeping                                                                                     |
| **Primary Actor**     | Driver Manager (`role:driver_manager`)                                                                 |
| **Secondary Actor**   | System / Admin (read-only role boundaries)                                                              |
| **Goal**               | Allow the Driver Manager to review the driver timekeeping ledger and add a time entry (shift or leave).   |
| **Trigger**           | Driver Manager selects the **"Time Keeping"** item on the sidebar (`GET /time-keeping`).                  |
| **Preconditions**     | 1. Driver Manager is authenticated.<br>2. The actor holds the `driver_manager` role, i.e. has administrative privileges over driver records.<br>3. Driver and timekeeping data exist in the system. |
| **Supporting Actors** | Driver record, Timekeeping record                                                                       |

> **Corrections vs. the original narrative**
> - The sidebar item is **"Time Keeping"** (two words), and the add button is **"New Entry"** — the original said *"Timekeeping button"* / *"add entry button"*.
> - The precondition text in the original was garbled (*"authenticated & 2. Timekeeping data already exists has administrative privileges"*); it is split into three conditions here.
> - **E4 (clock out before clock in)** and **E5 (overlapping shifts)** were not implemented in the original narrative and are now enforced by the controller.
> - A leave entry (sick / vacation) is a first-class alternative to a shift, which the original narrative did not model.

---

## MAIN FLOW

| #   | Actor's Action                                                             | System Response                                                                                                                                       |
| --- | --------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver Manager opens the dashboard / map view.                              | System displays the dashboard with the driver-manager analytics.                                                                                        |
| 2   | Driver Manager clicks **"Time Keeping"** on the sidebar.                    | System redirects to the timekeeping management page (`driver-manager.time-keeping`).                                                                     |
| 3   |                                                                             | System displays the time entry table with columns **`#`, `Driver & Date`, `Shift`, `Hours`, `Overtime`, `Type`**, the type filter chips and a **"New Entry"** button. |

> Steps 4+ are the alternate flows (A1–A2); the main flow is "open the ledger and read it".

---

## Alternate Flow

**A1 – Create a Time Entry (shift or leave)**
1. In the list, the Driver Manager clicks **"New Entry"**.
2. System displays the **New Entry** modal: *Driver*, *Date*, *Time In*, *Time Out* — or, when **Log Leave** is toggled, a *Leave Type* choice (*Sick Leave* / *Vacation Leave*).
3. Driver Manager selects a driver, a date and (for a shift) the clock-in / clock-out times, then clicks **"Save Entry"** (**"Log Leave"** for leave).
4. System validates `driver_id` **required, exists** and `date` **required, date**; `time_in` / `time_out` are **required for a shift** and must be `H:i`.
5. System computes `hours_worked` from the two timestamps and `overtime_hours` as `max(0, hours − 8)`, stores the row and confirms *"Time entry saved successfully."*
6. A leave entry stores `sick` or `vacation` with zero hours and no timestamps.

**A2 – Filter by Type**
1. Driver Manager clicks one of the chips *All*, *Regular*, *Sick*, *Vacation*, *Overtime*.
2. System filters the table client-side to the selected type; the entry counter updates. **Overtime** means `overtime_hours > 0`.

---

## Postconditions

1. The timekeeping ledger has been re-read, or a new time entry has been added to it.
2. The timekeeping record in the database has been written (`time_keepings`).
3. The system notifies the Driver Manager with a success message.
4. `hours_worked` and `overtime_hours` are derived server-side; they are never trusted from the client.

---

## Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Missing Required Fields**
No driver or no date was supplied. System lists the offending fields and stores nothing.

**E3 – Database Error**
The insert fails at the database level. System cancels the operation and notifies the Driver Manager.

**E4 – Clock Out Is Before Clock In**
The clock-out time is earlier than (or equal to) the clock-in time. System rejects the request on *time_out* with *"The clock-out time must be later than the clock-in time."* and stores nothing.

**E5 – Overlapping Shifts**
The shift being written shares part of the day with an entry that already exists for that driver on that date. System rejects the request on *time_in* with *"This shift overlaps an existing time entry for the driver on that date."* and stores nothing.

---

## Known Gaps / Notes

- **Shifts cannot span midnight.** An overnight shift (e.g. 22:00 → 06:00) is rejected by E4; the manager must split it into two dated entries.
- **No overlap check against the driver's own clock-in** (UCN_SC_E009) — a manager entry and a self-clocked entry on the same day are only blocked when they genuinely overlap in time.
- **Entries are immutable**: there is no edit or delete for a manager-entered row; only the driver's own clock-out (E010) fills in `time_out`.
- `time_in` / `time_out` are stored as free-form strings (`"08:00"` from this form, `"08:00 AM"` from the driver's clock-in), so downstream consumers must parse rather than compare as time.
- The list is paginated at 10 rows, but the type filter applies only to the current page.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `driver-manager.time-keeping` (`GET /time-keeping`), `driver-manager.time-keeping.store` (`POST /time-keeping`), both inside `role:driver_manager` |
| Controller | `app/Http/Controllers/DriverManagerController.php::timeKeeping()`, `timeKeepingStore()`, `shiftOverlaps()` |
| Model | `app/Models/TimeKeeping.php`, `app/Models/Driver.php::timeKeeping()` |
| View | `resources/views/driver-manager/time-keeping.blade.php` |
| Menu | `config/menu.php` — *Time Keeping* (driver_manager) |
| Middleware | `auth`, `verified`, `role:driver_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_time_entry_where_clock_out_precedes_clock_in_is_rejected`, `test_an_overlapping_shift_is_rejected`, `test_a_second_non_overlapping_shift_is_accepted`) |
| Related | UCN_SC_E009 (Clock In), UCN_SC_E010 (Clock Out), UCN_SC_E012 (Manage Drivers) |