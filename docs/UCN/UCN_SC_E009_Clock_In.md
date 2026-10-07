# UCN_SC_E009 — Clock In

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E009                                                                                                                                                           |
| **Use Case Name**     | Clock In                                                                                                                                                              |
| **Primary Actor**     | Driver                                                                                                                                                                |
| **Secondary Actor**   | System                                                                                                                                                                |
| **Goal**               | Allow the driver to clock in to the system and start their shift.                                                                                                     |
| **Trigger**           | Driver clicks the **"Clock In"** button on the **Today's Shift** card in the map's left sidebar.                                                                        |
| **Preconditions**     | 1. Driver is authenticated and registered to the system.<br>2. The driver has no timekeeping record for today yet.<br>3. The System is operational.                |
| **Supporting Actors** | Timekeeping ledger, Driver record                                                                                                                                     |

> **Vehicles are intentionally not a precondition.** A driver's vehicle is assigned by staff **at registration/approval time**, so there is no per-shift assignment to check at clock-in.

---

## MAIN FLOW

| #   | Actor's Action                                                       | System Response                                                                                                                                                                        |
| --- | -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver opens the map's left sidebar.                                | System shows the driver left sidebar: the **Availability** card (Active / Inactive toggle), the **Today's Shift** card, and the **Weekly Log** link to the full timekeeping page.             |
| 2   | Driver clicks **"Clock In"** on the Today's Shift card.             | System creates a **timekeeping record** for today with `time_in` set to the current time (**Asia/Manila**, `h:i A`).                                                                |
| 3   | Driver waits for the process to complete.                            | System sets the driver status to **`active`** (visible to commuters), refreshes the UI — the card switches to **"In Progress"** and shows *Time In* — and notifies *"Clocked in at HH:MM AM/PM."* |

---

## Alternate Flow

**A1 – Driver has already clocked in today**
1. The driver presses **"Clock In"** again (the button is hidden once a record exists, so this is only reachable by a replayed request).
2. System detects an existing record for today, performs **no** write, and responds with *"You have already clocked in today."*

**A2 – Driver profile missing**
1. The authenticated user has the `driver` role but no `Driver` row.
2. System aborts with *"Driver profile not found."* instead of crashing.

---

## Postconditions

1. The driver has successfully clocked in (a `TimeKeeping` row exists for today with a `time_in`).
2. The timekeeping record has been saved to the database.
3. The driver is set to **`active`** and is therefore advertised as available to commuters.
4. The system notifies the driver that they are clocked in, and the UI shows **"In Progress"**.

---

## Exceptions
- Suspension exceptions apply to suspended accounts (see E015).

**E1 – Database / System Error**
The timekeeping record cannot be created. System responds with *"Clock-in failed. Please try again later."*, performs **no** write and does **not** change the driver status.

**E2 – Driver Profile Not Found**
See A2 — the driver has no `Driver` record. System responds with *"Driver profile not found."*

**E3 – Already Clocked In**
See A1 — *"You have already clocked in today."*

---

## Known Gaps / Notes

- **One shift per day, enforced.** `clockIn` rejects a second record for the same date, so a driver **cannot** clock in twice (e.g. an AM and a PM shift). Multi-split shifts would need a different uniqueness rule.
- **No GPS / location verification.** The original precondition *"the driver's device GPS is enabled and permissions are granted"* is **not checked** — clock-in works from any device, anywhere.
- **No shift/vehicle assignment check** (deliberate — vehicles are assigned by staff at registration, see note above). The clock-in is therefore **not** tied to the vehicle the driver will actually take out.
- **No manager approval or overtime validation** — a driver may clock in at any hour, and a shift longer than 8 hours is only flagged as overtime at clock-out (UCN_SC_E010).
- **Clock-in and status are two separate writes.** If the record is created but the `drivers.status` update fails, the driver has a shift but stays offline.
- Time is stored as a formatted **string** (`h:i A`) in a `date` column, while clock-out re-parses it with `Carbon::parse(..., 'Asia/Manila')` — string time storage is fragile across locales/DST-free zones.
- The clock-in/out buttons live on the **map**; the `/timekeeping` page is read-only for drivers (staff can add entries via the driver-manager screen).

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `driver.timekeeping.clock-in` (`POST /timekeeping/clock-in`), inside `role:driver` + `auth` + `verified` |
| Controller | `app/Http/Controllers/DriverController.php::clockIn()` |
| Sidebar UI | `resources/views/map.blade.php` — Availability card, **Today's Shift** card, Clock In / Clock Out forms, Weekly Log link |
| Weekly log | `resources/views/driver/time-keeping.blade.php` — `DriverController::timekeeping()` |
| Models | `app/Models/TimeKeeping.php`, `app/Models/Driver.php` |
| Related | UCN_SC_E010 (Clock Out), driver registration/approval |