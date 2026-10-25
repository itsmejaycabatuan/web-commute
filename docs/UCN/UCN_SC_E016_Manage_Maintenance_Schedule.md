# UCN_SC_E016 — Manage Maintenance Schedule

| Field                 | Value                                                                                                        |
| --------------------- | -------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E016                                                                                                  |
| **Use Case Name**     | Manage Maintenance Schedule (Preventive Maintenance)                                                         |
| **Primary Actor**     | Maintenance Manager (`role:maintenance_manager`)                                                              |
| **Secondary Actor**   | System / Admin (read-only role boundaries)                                                                    |
| **Goal**               | Allow the Maintenance Manager to review the preventive-maintenance schedule of a vehicle and log a service against it. |
| **Trigger**           | Maintenance Manager selects the **"Preventive Maintenance"** item on the sidebar (`GET /preventive-maintenance`). |
| **Preconditions**     | 1. Maintenance Manager is authenticated and registered to the system.<br>2. Vehicle records exist in the system.<br>3. Maintenance task data exists in the database. |
| **Supporting Actors** | Vehicle, Maintenance task, Preventive maintenance record                                                       |

> **Corrections vs. the original narrative**
> - **The identifier changed**: the original sheet was numbered `UCN_SC_E015`, which collided with *Manage Vehicles*. The maintenance schedule is now **UCN_SC_E016**.
> - The page does **not** show a "list of maintenance services to be logged"; it is a per-vehicle schedule board: a vehicle picker plus one card per maintenance task showing *last serviced*, *odometer*, *cost* and the computed **next due date / next due odometer**.
> - The buttons are **"Log"** (or **"Update"** once logged) per task card and **"Save Log"** in the modal — not *"Log button"* / *"Save Log button"* on a list row.
> - **E2 (invalid odometer reading)** is now enforced: a reading lower than the last recorded one for that vehicle is rejected.
> - The *vehicle maintenance log* (formerly its own use case, numbered E025 until the October 2026 renumbering — see the UCN README) is **back, as a page of its own** (**"Maintenance Logs"** on the Maintenance Manager's sidebar, `GET /maintenance-logs`, Maintenance Manager only — the Admin and every other role get 403) rather than a use case of its own: the schedule keeps only the latest service per task, so every service logged is also written to `vehicle_maintenance_logs` and listed there per vehicle (newest first, 10 per page). The Preventive Maintenance page itself no longer shows the log. The fleet cost/kilometre analytics and the reports still read the schedule rows.

---

## MAIN FLOW

| #   | Actor's Action                                                                   | System Response                                                                                                       |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| 1   | Maintenance Manager opens the dashboard / map view.                               | System displays the dashboard with the maintenance-manager analytics.                                                   |
| 2   | Maintenance Manager clicks **"Preventive Maintenance"** on the sidebar.           | System redirects to the preventive-maintenance page, showing a summary strip and the schedule of the selected vehicle.   |
| 3   |                                                                                   | System lists one card per maintenance task with *task name*, *interval*, *last service date*, *last service odometer*, *last service cost*, *comments*, and the computed **next due date / next due odometer**, each with a **"Log"** / **"Update"** button. |

> Steps 4+ are the alternate flows (A1–A3); the main flow is "read the schedule".

---

## Alternate Flow

**A1 – Log a Scheduled Service**
1. In the schedule, the Maintenance Manager clicks **"Log"** (or **"Update"**) on a task card.
2. System displays the **Log Service** modal: *Odometer (km)*, *Date*, *Cost*, *Performed by* and *Invoice no.* (both optional), *Comments*.
3. Maintenance Manager fills the fields and clicks **"Save Log"**.
4. System validates `vehicle_id` **required, exists**, `task_id` **required, exists**, `last_service_odo` **required, integer, min 0**, `last_service_date` **required, date**, `last_service_cost` **required, numeric, min 0**, `comments` **nullable**, `performed_by` **nullable, max 255**, `invoice_number` **nullable, max 100**.
5. System checks the odometer against the last reading recorded for that vehicle (E2), then — in one transaction — **upserts** the `preventive_maintenances` row for *(vehicle, task)* **and appends a `vehicle_maintenance_logs` row** for the service.
6. System confirms *"Preventive maintenance logged successfully!"*

**A2 – Log a Service for a Different Vehicle**
1. The Maintenance Manager uses the **vehicle picker** at the top of the page.
2. System reloads the schedule for the newly selected vehicle, keeping the same table rows and picking that vehicle's own logged values.
3. Steps 4–6 of A1 then apply to the newly selected vehicle.

**A3 – Overdue and due-soon services are pushed to the manager**
1. A service is **due soon** when its next due date is within `MAINTENANCE_DUE_SOON_DAYS` (default **7**) days, or its next due odometer is within `MAINTENANCE_DUE_SOON_KM` (**500**) km of the vehicle's current reading; it is **overdue** once the date has passed or the reading has reached the due odometer. A task with no interval is never due.
2. The system has no live odometer, so a vehicle's "current" reading is the **highest odometer logged for any of its services**: a later service on the same vehicle is what shows an earlier one is coming up.
3. The preventive-maintenance page labels each task *On Track*, *Due Soon* or *Past Due*; a task that has only a distance interval is no longer shown overdue merely because it has no time interval.
4. Every day at 07:00 `php artisan maintenance:due-alerts` emails every active Maintenance Manager a digest of the overdue and due-soon services per vehicle (it sends nothing when none are due; `--dry-run` lists them without emailing).
5. The same flags show on the Vehicles list and on the dashboard (*Maintenance needs attention*), and, if enabled, can stop an overdue vehicle going back to `active` (UCN_SC_E015 A8 / E7). Disposed vehicles are never flagged.

---

## Postconditions

1. The Maintenance Manager has reviewed the schedule and/or logged a service.
2. The service record in the database has been written (`preventive_maintenances`).
3. The system notifies the Maintenance Manager with a success message.
4. The task card now shows the new last-service values and recalculates the next due date and odometer from the task's `miles_between_service` / `months_between_service`.
5. The service is immediately visible in the dashboard's *Service History* tab and in the fleet cost-per-kilometre analytics.

6. Overdue and due-soon services are emailed daily and flagged on the Vehicles page and the dashboard (A3).
---

## Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Invalid Odometer Reading**
The entered odometer is lower than the last reading recorded for that vehicle (across its scheduled services). System rejects the request on *last_service_odo* with *"The odometer reading cannot be lower than the last recorded reading of N km."* and changes nothing.

**E3 – Missing Required Service Details**
Any required field is empty or malformed. System lists the offending fields and stores nothing.

**E4 – Database Error**
The insert or update fails at the database level. System cancels the operation and notifies the Maintenance Manager.

---

## Known Gaps / Notes

- **The summary is per vehicle, per calendar year** — there is no cross-vehicle roll-up page and no year picker (the current year is always used).
- **The schedule is per task, not per service**: logging a task again **overwrites** that task's last-service row. The history is kept in the **Maintenance Logs** page (`vehicle_maintenance_logs`), which is append-only: there is no edit or delete for a log entry, and a log entry outlives the schedule row it came from (`maintenance_id` is cleared, not cascaded). Services that were on the schedule when the log was re-introduced were copied in as its first entries; earlier overwritten services cannot be recovered.
- **Next-due values are computed on the fly** in the view; nothing is persisted, so changing a task's interval retroactively changes every vehicle's projected due date.
- The vehicle picker falls back to the first vehicle when the requested one no longer exists, and renders an empty board when the fleet is empty.
- **Alerts need the scheduler** (A3): without `php artisan schedule:run` on a cron the daily email is not sent, though the flags on the pages are computed live. Kilometre alerts rely on logged service odometers; there is no live odometer.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `maintenance-manager.preventive-maintenance` (`GET /preventive-maintenance`), `maintenance-manager.preventive-maintenance.store` (`POST /preventive-maintenance`), all inside `role:maintenance_manager` |
| Controller | `app/Http/Controllers/MaintenanceManagerController.php::preventiveMaintenance()`, `preventiveMaintenanceStore()` |
| Models | `app/Models/PreventiveMaintenance.php`, `app/Models/MaintenanceTask.php` |
| View | `maintenance-manager/preventive-maintenance.blade.php` |
| Menu | `config/menu.php` — *Preventive Maintenance* (maintenance_manager) |
| Middleware | `auth`, `verified`, `role:maintenance_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_an_odometer_reading_below_the_last_recorded_one_is_rejected`, `test_an_odometer_reading_above_the_last_recorded_one_is_accepted`), `tests/Feature/BugFixRegressionTest.php` (`test_logging_a_preventive_service_feeds_the_fleet_summary`) |
| Due alerts | `app/Services/MaintenanceDueService.php`, `PreventiveMaintenance::dueStatus()` / `nextDueDate()` / `nextDueOdo()`, `app/Console/Commands/MaintenanceDueAlerts.php` (`maintenance:due-alerts`, daily 07:00), `app/Notifications/MaintenanceDueDigest.php`, `config/maintenance.php` |
| Tests (automation) | `tests/Feature/AutomationTest.php` — `test_due_status_by_date`, `test_due_status_by_distance`, `test_a_later_service_shows_an_earlier_one_is_coming_up`, `test_due_alert_command_emails_the_maintenance_managers` |
| Related | UCN_SC_E015 (Manage Vehicles), UCN_SC_E017 (Manage Maintenance Tasks), UCN_SC_E020 (System Reports — maintenance report) |
