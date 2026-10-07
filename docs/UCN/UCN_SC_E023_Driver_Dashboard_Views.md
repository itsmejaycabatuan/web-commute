# UCN_SC_E023 — Driver Dashboard Views

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E023                                                                                                                                      |
| **Use Case Name**     | Driver Dashboard Views                                                                                                                           |
| **Primary Actor**     | Driver (authenticated user holding the `driver` role)                                                                                            |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow a driver to view their weekly timekeeping records, violation history, and to toggle their availability status (active/inactive).         |
| **Trigger**           | Driver opens the sidebar and selects **Timekeeping** or **Violations**, or toggles the status switch in the header.                              |
| **Preconditions**     | 1. Driver is authenticated and logged in.<br>2. Driver has a `Driver` profile (approved or pending – the views are accessible regardless of approval).<br>3. System is operational. |
| **Supporting Actors** | `TimeKeeping` model, `ViolationLog` model, `User` model, `Driver` model                                                                         |

---

## MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver clicks the **Timekeeping** item in the sidebar (or navigates to `/driver/timekeeping`). | System authenticates the driver, fetches the driver’s `TimeKeeping` records for the current week (or the week selected via query param), calculates weekly totals, and returns the `driver.time-keeping` view with the data. |
| 2   |                                                                                   | The view renders a weekly summary card (hours, overtime, progress) and a table (or mobile cards) showing each day of the week with date, time‑in, time‑out, hours worked, overtime, and status (Complete/Active/Absent/etc.). |
| 3   | Driver can change the displayed week by clicking **Previous Week**, **Next Week**, or **Today** links. | System receives the `week` query parameter, repeats step 1 for the requested week, and updates the view accordingly.                           |
| 4   | Driver clicks the **Violations** item in the sidebar (or navigates to `/driver/violations`). | System fetches the driver’s `ViolationLog` records (with eager‑loaded `violationCode`), computes total violations and total fines, and returns the `driver.violations` view. |
| 5   |                                                                                   | The view renders summary cards (total violations, total fines, latest violation) and a list/table of each violation with details: violation type, code, offense count, date, location, fine, penalty, and remarks. |
| 6   | Driver toggles the **status switch** in the header (or submits a form to `/driver/status` with `status=active|inactive`). | System validates the `status` input (`required|in:active,inactive`), updates the `status` column on the driver’s `Driver` record, and returns a success toast (“You have successfully set your driver status”). |
| 7   |                                                                                   | The page reloads (or the toast appears) and the driver’s current status is reflected in the header UI.                                          |

---

## Alternate Flow

**A1 – Unauthenticated access attempt**
1. An unauthenticated user tries to access `/driver/timekeeping`, `/driver/violations`, or submits to `/driver/status`.
2. The `auth` middleware redirects the user to the login page (`/login`) with no data retrieved.

**A2 – Invalid status value**
1. Driver submits a status value other than `active` or `inactive` (e.g., `maintenance`).
2. Server‑side validation fails (`Rule::in`), and the request is rejected with a validation error (the view shows the error message or redirects back with `error` flash).

**A3 – Database error while fetching records**
1. A PDO exception occurs when querying `TimeKeeping` or `ViolationLog`.
2. System logs the error, returns a generic error view or flashes *“Time‑keeping failed. Please try again later.”* (or analogous for violations) and does not display partial data.

**A4 – Driver profile missing**
1. Despite being authenticated, the related `Driver` record is absent (e.g., manually deleted).
2. The controller methods (`timekeeping`, `violations`, `updateStatus`) return an error view or flash *“Driver profile not found.”* and stop further processing.

---

## Postconditions

- For **Timekeeping** view: the driver’s weekly time‑in/time‑out, hours, overtime, and daily status are correctly displayed; week navigation updates the shown week.
- For **Violations** view: the driver’s violation logs are listed with accurate totals and details; summary cards reflect counts and sums.
- For **Status update**: the driver’s `status` column in the `drivers` table is set to `active` or `inactive`; a success message is shown; subsequent checks (e.g., clock‑in/out) respect the new status.
- No side‑effects on other tables (e.g., `TimeKeeping` or `ViolationLog` are read‑only in these flows).

---

## Exceptions

**E1 – Driver profile not found**
The authenticated user lacks a linked `Driver` record. System returns an error view or flashes *“Driver profile not found.”* and aborts the request.

**E2 – Invalid status input**
The `status` parameter is missing or not one of `active`/`inactive`. System returns a validation error (HTTP 422) with message *“The status field must be either active or inactive.”* and does not update the record.

**E3 – Database query failure**
A query exception occurs while fetching `TimeKeeping` or `ViolationLog`. System logs the exception, returns a generic error view or flashes *“[Time‑keeping/Violations] failed. Please try again later.”* and shows no data.

**E4 – Unexpected server error**
Any other unhandled exception results in a 500 error page (or the Laravel default error page) and logs the incident.

---

## Known Gaps / Notes

- The time‑keeping view distinguishes between **Complete**, **Active**, **Absent**, **Sick**, **Vacation**, **Upcoming**, and **Waiting** states based on the presence of `time_in`, `time_out`, `sick`, `vacation` flags and whether the date is past/today/future.
- The violation view is **read‑only**; drivers cannot add, edit, or delete violations through this interface (those actions are reserved for admins/managers via UCN_SC_E014).
- The status toggle only affects the `status` column on the `driver` table; it does **not** automatically clock the driver in or out. Clock‑in/out actions are handled separately via UCN_SC_E009/E010.
- Weekly totals are calculated from the stored `hours_worked` and `overtime_hours` columns; they are not recomputed on the fly from raw time‑in/out.
- The view uses Alpine.js for client‑side interactivity (week navigation, card animations) but all data is sourced from server‑side queries.
- No pagination is applied; the view assumes a maximum of seven days (one week) of records.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /driver/timekeeping` (`driver.timekeeping`), `GET /driver/violations` (`driver.violations`), `POST /driver/status` (`driver.status.update`) (all inside `role:driver` middleware group) |
| Controller | `app/Http/Controllers/DriverController.php::timekeeping()`, `violations()`, `updateStatus()` |
| Views | `resources/views/driver/time-keeping.blade.php`, `resources/views/driver/violations.blade.php` |
| Models | `App\Models\TimeKeeping`, `App\Models\ViolationLog`, `App\Models\Driver` |
| Related UCNs | UCN_SC_E009 (Clock In), UCN_SC_E010 (Clock Out), UCN_SC_E012 (Manage Drivers – approval), UCN_SC_E014 (Manage Violations – admin view) |