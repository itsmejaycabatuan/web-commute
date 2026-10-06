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
| **Supporting Actors** | Vehicle, Maintenance task, Preventive maintenance record, Vehicle maintenance log                            |

> **Corrections vs. the original narrative**
> - **The identifier changed**: the original sheet was numbered `UCN_SC_E015`, which collided with *Manage Vehicles*. The maintenance schedule is now **UCN_SC_E016**.
> - The page does **not** show a "list of maintenance services to be logged"; it is a per-vehicle schedule board: a vehicle picker plus one card per maintenance task showing *last serviced*, *odometer*, *cost* and the computed **next due date / next due odometer**.
> - The buttons are **"Log"** (or **"Update"** once logged) per task card and **"Save Log"** in the modal — not *"Log button"* / *"Save Log button"* on a list row.
> - **E2 (invalid odometer reading)** is now enforced: a reading lower than the last recorded one for that vehicle is rejected.
> - Logging a scheduled service now also writes a **service-log record** (`vehicle_maintenance_logs`), which is what the fleet cost/kilometre analytics and the reports read.

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
2. System displays the **Log Service** modal: *Odometer (km)*, *Date*, *Cost*, *Comments*.
3. Maintenance Manager fills the fields and clicks **"Save Log"**.
4. System validates `vehicle_id` **required, exists**, `task_id` **required, exists**, `last_service_odo` **required, integer, min 0**, `last_service_date` **required, date**, `last_service_cost` **required, numeric, min 0**, `comments` **nullable**.
5. System checks the odometer against the last reading recorded for that vehicle (E2), then **upserts** the `preventive_maintenances` row for *(vehicle, task)*.
6. System **writes the matching service log** (`vehicle_maintenance_logs`: vehicle, task, service date, odometer, cost, remarks) so the history and cost analytics stay complete, and confirms *"Preventive maintenance logged successfully!"*

**A2 – View the Maintenance Logs**
1. In the Preventive Maintenance Page, the Maintenance Manager selects **"Maintenance Logs"** on the sidebar (`GET /maintenance-logs`).
2. System redirects to the maintenance logs page, showing the recorded service logs — optionally narrowed to a single vehicle via the vehicle selector.

**A3 – Log a Service for a Different Vehicle**
1. The Maintenance Manager uses the **vehicle picker** at the top of the page.
2. System reloads the schedule for the newly selected vehicle, keeping the same task cards and picking that vehicle's own logged values.
3. Steps 4–6 of A1 then apply to the newly selected vehicle.

---

## Postconditions

1. The Maintenance Manager has reviewed the schedule and/or logged a service.
2. The service record in the database has been written (`preventive_maintenances` and `vehicle_maintenance_logs`).
3. The system notifies the Maintenance Manager with a success message.
4. The task card now shows the new last-service values and recalculates the next due date and odometer from the task's `miles_between_service` / `months_between_service`.
5. The service log is immediately visible on the Maintenance Logs page and in the fleet cost-per-kilometre analytics shown on the dashboard.

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

- **The odometer check compares against the preventive schedule only**, not against mileage recorded elsewhere (for example on the vehicle maintenance log page), so a reading can still be lower than a mileage entry made on the other page.
- **The summary is per vehicle, per calendar year** — there is no cross-vehicle roll-up page and no year picker (the current year is always used).
- **The schedule is per task, not per service**: logging a task again **overwrites** that task's last-service row and appends a new service log, so repeated logging accumulates history in the log but not in the schedule.
- **Next-due values are computed on the fly** in the view; nothing is persisted, so changing a task's interval retroactively changes every vehicle's projected due date.
- The vehicle picker falls back to the first vehicle when the requested one no longer exists, and renders an empty board when the fleet is empty.
- There is no notification when a task becomes overdue.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `maintenance-manager.preventive-maintenance` (`GET /preventive-maintenance`), `maintenance-manager.preventive-maintenance.store` (`POST /preventive-maintenance`), `maintenance-manager.maintenance-logs` (`GET /maintenance-logs`), all inside `role:maintenance_manager` |
| Controller | `app/Http/Controllers/MaintenanceManagerController.php::preventiveMaintenance()`, `preventiveMaintenanceStore()`, `maintenanceLogs()` |
| Models | `app/Models/PreventiveMaintenance.php`, `app/Models/VehicleMaintenanceLog.php`, `app/Models/MaintenanceTask.php` |
| View | `maintenance-manager/preventive-maintenance.blade.php`, `maintenance-manager/maintenance-logs.blade.php` |
| Menu | `config/menu.php` — *Preventive Maintenance*, *Maintenance Logs* (maintenance_manager) |
| Middleware | `auth`, `verified`, `role:maintenance_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_an_odometer_reading_below_the_last_recorded_one_is_rejected`, `test_an_odometer_reading_above_the_last_recorded_one_is_accepted`), `tests/Feature/UcnSuspensionAndMaintenanceLogTest.php` (`test_logging_a_scheduled_service_also_writes_a_service_log`) |
| Related | UCN_SC_E015 (Manage Vehicles), UCN_SC_E017 (Manage Maintenance Tasks), UCN_SC_E020 (System Reports — maintenance report) |