# UCN_SC_E010 — Clock Out

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E010                                                                                                                                                           |
| **Use Case Name**     | Clock Out                                                                                                                                                             |
| **Primary Actor**     | Driver                                                                                                                                                                |
| **Secondary Actor**   | System                                                                                                                                                                |
| **Goal**               | Allow the driver to clock out of the system and end their shift.                                                                                                        |
| **Trigger**           | Driver clicks the **"Clock Out"** button on the **Today's Shift** card in the map's left sidebar.                                                                       |
| **Preconditions**     | 1. Driver is authenticated and registered to the system.<br>2. The driver is already clocked in today and that record has **no** `time_out` yet.<br>3. The System is operational. |
| **Supporting Actors** | Timekeeping ledger, Driver record                                                                                                                                     |

> **Vehicles are intentionally not a precondition.** A driver's vehicle is assigned by staff **at registration/approval time**, so there is no per-shift assignment to check at clock-out.

---

## MAIN FLOW

| #   | Actor's Action                                                     | System Response                                                                                                                                                                       |
| --- | ------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver opens the map's left sidebar.                              | System shows the driver left sidebar: the **Availability** card, the **Today's Shift** card (now **"In Progress"** with *Time In*), and the **Weekly Log** link.                          |
| 2   | Driver clicks **"Clock Out"** on the Today's Shift card.          | System finds today's open record and computes the worked duration from `time_in` to now (Asia/Manila), rounding to 2 decimals, and splits it into **regular** (capped at 8 h) and **overtime** hours. |
| 3   | Driver waits for the process to complete.                          | System writes `time_out`, `hours_worked` and `overtime_hours`; sets the driver status to **`inactive`** so they are no longer advertised as available; refreshes the UI to **"Shift Complete"** with the total; and notifies *"Clocked out at HH:MM AM/PM. Total: X hrs."* |

> Clocking out **takes the driver offline**. Previously the status stayed `active` after clock-out, which left a finished driver visible to commuters.

---

## Alternate Flow

**A1 – No active shift (driver is not clocked in)**
1. The driver presses **"Clock Out"** without an open shift (the button is hidden in this state, so this is mainly reachable by a replayed request or a stale page).
2. System finds no open record for today, performs **no** write, and responds with *"No active shift found to clock out."*

**A2 – Shift longer than 8 hours (overtime)**
1. The shift duration exceeds 8 hours.
2. System records `hours_worked` as the full duration and `overtime_hours` as `hours_worked − 8`, and the card additionally shows *"+N.N OT"*.

**A3 – Driver profile missing**
1. The authenticated user has the `driver` role but no `Driver` row.
2. System aborts with *"Driver profile not found."* instead of crashing.

---

## Postconditions

1. The driver has successfully clocked out — today's record now has a `time_out`.
2. The timekeeping record has been updated with `time_out`, `hours_worked` and `overtime_hours`.
3. The driver is set to **`inactive`** (offline) and is no longer shown to commuters as available.
4. The system notifies the driver, and the UI shows **"Shift Complete"** plus the total hours (and overtime, if any).
5. The closed shift appears in the **Weekly Log** for that week.

---

## Exceptions

**E1 – Database / System Error**
The record cannot be updated. System responds with *"Clock-out failed. Please try again later."*, performs **no** write and leaves the shift open.

**E2 – No Active Shift Found**
See A1 — *"No active shift found to clock out."*

**E3 – Driver Profile Not Found**
See A3 — *"Driver profile not found."*

---

## Known Gaps / Notes

- **No GPS / location verification** — a driver can clock out from anywhere, and nothing confirms the vehicle actually stopped.
- **Duration is measured from `time_in` parsed out of a formatted string** (`Carbon::parse($record->date . ' ' . $record->time_in, 'Asia/Manila')`). A `time_in` written by the driver-manager screen in a different format/timezone will skew the hours; storing real datetimes would remove this fragility.
- **`hours_worked` is rounded to 2 decimals**, and breaks are not considered — the value is a raw difference between clock-in and clock-out.
- **The `regularHours` local is computed but never used** (dead code in `clockOut()`); only `hours_worked` and `overtime_hours` are persisted.
- **No manager confirmation / adjustment flow for drivers** — corrections must be made by a driver manager, who can add whole entries (`DriverManagerController::timeKeepingStore()`) but cannot edit a driver's self-recorded shift.
- **Status and timekeeping are two separate writes** — if the `drivers.status` update fails after the record is closed, the driver remains advertised as active.
- **The Availability toggle can contradict the shift state.** `driver.status.update` lets a driver flip Active/Inactive at any time, so a driver who has clocked out can be switched back to `active` by hand (and vice-versa). The shift record is the source of truth for the clock; the toggle is not validated against it.
- Drivers can only hold **one shift per day** (see UCN_SC_E009), so a re-clock-in on the same day is refused and the driver must be handled by a manager.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `driver.timekeeping.clock-out` (`POST /timekeeping/clock-out`), inside `role:driver` + `auth` + `verified` |
| Controller | `app/Http/Controllers/DriverController.php::clockOut()` |
| Sidebar UI | `resources/views/map.blade.php` — Today's Shift card, Clock Out form, Shift Complete state |
| Weekly log | `resources/views/driver/time-keeping.blade.php` — `DriverController::timekeeping()` |
| Manager corrections | `app/Http/Controllers/DriverManagerController.php::timeKeepingStore()` |
| Models | `app/Models/TimeKeeping.php`, `app/Models/Driver.php` |
| Related | UCN_SC_E009 (Clock In) |