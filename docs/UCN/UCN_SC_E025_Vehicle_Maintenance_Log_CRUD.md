# UCN_SC_E025 — Vehicle Maintenance Log CRUD

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E025                                                                                                                                      |
| **Use Case Name**     | Vehicle Maintenance Log CRUD                                                                                                                     |
| **Primary Actor**     | Maintenance Manager (authenticated user holding the `maintenance_manager` role)                                                                  |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow a maintenance manager to create, view, edit, and delete maintenance service logs for vehicles in the fleet.                               |
| **Trigger**           | User navigates to `/maintenance-manager/vehicle-maintenance-log` to view the logs, or submits the form to add, edit, or delete a log.         |
| **Preconditions**     | 1. User is authenticated and logged in.<br>2. User holds the `maintenance_manager` role.<br>3. System is operational.<br>4. At least one vehicle exists in the fleet (otherwise the view shows empty states). |
| **Supporting Actors** | `VehicleMaintenanceLog` model, `Vehicle` model, `MaintenanceTask` model                                                                          |

---

## MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User opens the sidebar and selects **Vehicle Maintenance Log** (or navigates to `/maintenance-manager/vehicle-maintenance-log`). | System authenticates the user, verifies the `maintenance_manager` role, fetches the list of vehicles (with driver) and the selected vehicle (via query param `vehicle_id` or the first vehicle), loads the maintenance logs for that vehicle (with eager‑loaded `maintenanceTask`), calculates summary statistics (total cost, total services, latest odometer, cost per km), and returns the `maintenance-manager.vehicle-maintenance-log` view. |
| 2   |                                                                                   | The view renders three main sections: **Fleet Information & Cost Summary** (vehicle details, total costs, cost/km summary), **Maintenance Guide & Log Button** (external guide link and a “Log New Service” button), and the **Log Table** (listing all maintenance logs for the selected vehicle). |
| 3   | To add a new log, the user clicks the **Log New Service** button (or the “Log first service” placeholder when the table is empty). A modal appears. | The modal contains a form with fields: Vehicle (dropdown, pre‑selected), Maintenance Task (dropdown), Service Date (date picker), Mileage at Service (number), Performed By (text), Cost (number), Invoice Number (optional text), Remarks (optional text). The user fills in the required fields and clicks **Log New Service** (or **Create Code** in the modal). |
| 4   |                                                                                   | System validates the input: `vehicle_id` required, exists; `maintenance_task_id` required, exists; `service_date` required, date; `mileage_at_service` required, integer, min 0; `performed_by` required, string, max 255; `cost` required, numeric, min 0; `invoice_number` nullable, string, max 100; `remarks` nullable, string, max 500. On success, a new `VehicleMaintenanceLog` record is created and the modal closes; the table updates (via page reload) to show the new log. A success flash is shown: *“Maintenance info logged!”*. |
| 5   | To edit an existing log, the user clicks the **Edit** button on a table row. A modal appears pre‑filled with that log’s data. | The user modifies any fields and clicks **Update** (the modal’s submit button). System validates the same rules as creation (with the ID included). On success, the record is updated, the modal closes, the table reloads to reflect the changes, and a success flash appears: *“Maintenance log updated!”*. |
| 6   | To delete a log, the user clicks the **Delete** (trash) button on a table row. A JavaScript `confirm()` prompt appears; if confirmed, a DELETE request is sent. | System checks that the `VehicleMaintenanceLog` record exists, then calls `$log->delete()`. On success, the table removes the row (via reload) and a success flash appears: *“Maintenance log deleted.”*. No further confirmation is needed beyond the JS prompt. |
| 7   | After any successful add/edit/delete operation, the user may optionally change the selected vehicle via the dropdown in the Fleet Information section; the table and summary update accordingly. | System receives the new `vehicle_id` query parameter, repeats steps 1‑2 for the newly selected vehicle, and updates the view. |

---

## Alternate Flow

**A1 – Unauthenticated access**
1. An unauthenticated user tries to access `/maintenance-manager/vehicle-maintenance-log` or submit to the store/update/delete endpoints.
2. The `auth` middleware redirects the user to the login page (`/login`).

**A2 – Invalid role**
1. An authenticated user lacking the `maintenance_manager` role attempts to access the URL.
2. The route is not explicitly role‑protected; however the controller does not check the role, so the user could see data. In practice, the UI is only linked in the sidebar for maintenance managers; relying on obscurity is not ideal. (Note: this is a known gap – the endpoint should be middleware‑protected.)

**A3 – Validation failure (store/update)**
1. The user submits a form with missing required fields, invalid date, negative mileage or cost, etc.
2. Server‑side validation returns HTTP 422 with field‑specific error messages (e.g., *“The service date field must be a date.”*, *“The cost field must be at least 0.”*). The modal remains open with errors displayed and the previously entered values retained.

**A4 – Edit/delete on non‑existent log**
1. The user attempts to edit or delete a log whose ID no longer exists (e.g., deleted by another user concurrently).
2. The route‑model binding (`VehicleMaintenanceLog $log`) results in a model not found exception, leading to a 404 error page.

**A5 – Database error during CRUD operation**
1. A PDO exception occurs while inserting, updating, or deleting a `VehicleMaintenanceLog`.
2. System logs the exception, returns a generic error view or flashes *“An error occurred while processing the maintenance log. Please try again later.”* and leaves the database unchanged.

**A6 – Selected vehicle has no logs**
1. The user selects a vehicle that has no maintenance logs recorded.
2. The table shows an empty state with a placeholder message (“No maintenance logs recorded yet.”) and a button to log the first service.

---

## Postconditions

- After a successful **add**, the `vehicle_maintenance_logs` table contains a new row with the submitted attributes, linked to the selected vehicle and maintenance task.
- After a successful **edit**, the existing row’s columns are updated to the new values.
- After a successful **delete**, the row is removed from the `vehicle_maintenance_logs` table.
- The summary statistics (total cost, total services, latest odometer, cost per km) are recalculated based on the current set of logs for the selected vehicle.
- The user sees appropriate success or error messages via session flash.
- No unintended side‑effects on other tables (e.g., `Vehicle`, `MaintenanceTask` remain unchanged unless cascades are defined).

---

## Exceptions

**E1 – Validation error**
One or more input fields fail the rules defined in `vehicleLogStore` or `vehicleLogUpdate`. System returns HTTP 422 with field‑specific messages.

**E2 – Related model not found**
The submitted `vehicle_id` or `maintenance_task_id` does not exist in the respective tables. System returns a validation error (`exists` rule fails).

**E3 – Log not found (edit/delete)**
The `VehicleMaintenanceLog` ID supplied in the route does not exist. System throws a model‑not‑found exception, leading to a 404 response.

**E4 – Database query/write failure**
A PDO exception occurs during any database interaction. System logs the exception and shows a generic error message.

**E5 – Unexpected server error**
Any other unhandled exception results in a 500 error page.

---

## Known Gaps / Notes

- The endpoint `/maintenance-manager/vehicle-maintenance-log` is **not** explicitly protected by a `role:maintenance_manager` middleware; reliance on UI hiding is a security gap. In a production deployment, the route should be wrapped in the appropriate middleware.
- The `mileage_at_service` and `cost` fields are stored as plain numbers; the view formats mileage as an integer and cost with two decimal places and the “₱” prefix.
- The `maintenance_task_id` foreign key links to the `maintenance_tasks` table (managed via UCN_SC_E017). If a task is deleted, existing logs will retain a dangling foreign key unless the database is set to `ON DELETE SET NULL` or cascades are applied.
- The summary block (total cost, total services, latest odometer, cost/km) is computed by the `FleetMaintenanceService` service methods (`emptySummary()` and `summaryFor($vehicle)`), ensuring consistency with the full `/fleet-maintenance-log` page (UCN_SC_E016).
- The UI uses Alpine.js for modal interactions and table row highlighting, but all persistence is performed via standard POST/PUT/DELETE requests to the Laravel backend.
- No pagination is applied; the table assumes a modest number of logs per vehicle (typically under a few dozen). For fleets with extensive history, pagination would be needed.
- The `remarks` field is optional and can contain free‑form text; the UI highlights if the remark contains the word “warranty” (case‑insensitive) with a badge.
- Deletion is immediate and irreversible; there is no soft‑delete or trash bin.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /maintenance-manager/vehicle-maintenance-log` (`maintenance-manager.vehicle-maintenance-log`), `POST /maintenance-manager/vehicle-maintenance-log` (`vehicleLogStore`), `PATCH /maintenance-manager/vehicle-maintenance-log/{log}` (`vehicleLogUpdate`), `DELETE /maintenance-manager/vehicle-maintenance-log/{log}` (`vehicleLogDelete`) (all currently **without** explicit role middleware; should be added) |
| Controller | `app/Http/Controllers/MaintenanceManagerController.php::vehicleLog()`, `vehicleLogStore()`, `vehicleLogUpdate()`, `vehicleLogDelete()` |
| View | `resources/views/maintenance-manager/vehicle-maintenance-log.blade.php` |
| Models | `App\Models\VehicleMaintenanceLog`, `App\Models\Vehicle`, `App\Models\MaintenanceTask` |
| Service | `App\Services\FleetMaintenanceService` (used for summary) |
| Related UCNs | UCN_SC_E016 (Manage Maintenance Schedule – full‑page log), UCN_SC_E017 (Manage Maintenance Tasks), UCN_SC_E012 (Manage Drivers – vehicle assignment), UCN_SC_E015 (Manage Vehicles – fleet list) |