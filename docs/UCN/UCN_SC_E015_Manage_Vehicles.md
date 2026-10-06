# UCN_SC_E015 — Manage Vehicles

| Field                 | Value                                                                                                     |
| --------------------- | ----------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E015                                                                                               |
| **Use Case Name**     | Manage Vehicles                                                                                           |
| **Primary Actor**     | Maintenance Manager (`role:maintenance_manager`)                                                          |
| **Secondary Actor**   | System / Admin (read-only role boundaries)                                                                |
| **Goal**               | Allow the Maintenance Manager to review, add, edit or delete the vehicles in the fleet.                    |
| **Trigger**           | Maintenance Manager selects the **"Vehicles"** item on the sidebar (`GET /vehicles`).                       |
| **Preconditions**     | 1. Maintenance Manager is authenticated and has fleet-management permissions.<br>2. Vehicle records exist in the system. |
| **Supporting Actors** | Vehicle record, Driver record, Timekeeping record, Live map                                              |

> **Corrections vs. the original narrative**
> - **The vehicle page is restricted to the `maintenance_manager` role.** It used to sit in the shared authenticated group, so any signed-in role (commuter, driver, …) could open it; the routes now carry `role:maintenance_manager`.
> - **E3 (cannot dispose an active vehicle)** and **E4 (cannot assign an under-maintenance vehicle)** were not enforced in the original narrative and are now blocked by the controller.
> - The postcondition *"removed from the live tracking map"* is now implemented: the map endpoint filters on vehicle status, so a unit set to **maintenance** or **disposed** drops off immediately instead of lingering until its last GPS ping ages out.
> - The postcondition *"marked Disposed, historical logs preserved"* is enforced by refusing to hard-delete a vehicle that has service history (E6).
> - A vehicle taken out of service now **loses its assigned driver** automatically.

---

## MAIN FLOW

| #   | Actor's Action                                                      | System Response                                                                                          |
| --- | -------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| 1   | Maintenance Manager opens the dashboard / map view.                  | System displays the dashboard with the maintenance-manager analytics.                                      |
| 2   | Maintenance Manager clicks **"Vehicles"** on the sidebar.            | System redirects to the vehicle management page (`vehicles.index`), showing a summary strip and the fleet table. |

> Steps 3+ are the alternate flows (A1–A5); the main flow is "open the list and read it".

---

## Alternate Flow

**A1 – Add a Vehicle**
1. In the list, the Maintenance Manager clicks **"Add Vehicle"**.
2. System displays the **Add Vehicle** modal: *Year, Brand, Model, Plate Number, VIN, Fuel Type, Tank Capacity, Assigned Driver, Location, Status, Acquisition Date, Expected Disposal Date*.
3. Maintenance Manager fills the required fields and clicks **"Add Vehicle"**.
4. System validates: `year` **required, integer, 1990–2030**; `brand`, `model`, `plate_number`, `status`, `acquisition_date` **required**; `plate_number` **unique** (E2); `status` **in `active, maintenance, inactive, disposed`**; `exp_disposal_date` **after `acquisition_date`**; `driver_id` **exists** and assignable (E4).
5. System stores the vehicle — detaching `driver_id` when the status is not roadworthy — and confirms *"Vehicle successfully added."*

**A2 – Edit a Vehicle**
1. In the list (or from the detail modal), the Maintenance Manager clicks the edit icon.
2. System displays the **Edit Vehicle** modal pre-filled with the record.
3. Maintenance Manager changes the values and clicks **"Save Changes"**.
4. System applies the same validation as A1 (uniqueness ignores the current row), applies the disposal and assignment guards (E3, E4), updates the record and confirms *"Vehicle successfully updated."*

**A3 – Delete a Vehicle**
1. In the list, the Maintenance Manager clicks the delete icon.
2. System displays the *"Delete Vehicle?"* confirmation.
3. Maintenance Manager clicks **"Delete"**.
4. System deletes the vehicle and confirms *"Vehicle successfully deleted."* — unless E6 applies, in which case the record is kept.

**A4 – Review a Specific Vehicle**
1. In the list, the Maintenance Manager clicks the view icon.
2. System displays a read-only modal with the full vehicle record.
3. The modal offers **"Edit"** to jump straight into A2.

**A5 – Search / Filter the Fleet**
1. Maintenance Manager uses the search box, the status chips (*Active / Maintenance / Inactive*), the *All Fuel Types* and *All Vehicles* (assigned / unassigned) selects, or the sort select (*Newest First / Oldest First / Brand A–Z*).
2. System filters and re-orders the table client-side and updates the row counter.

---

## Postconditions

1. The vehicle list has been re-read, or a vehicle record has been added, modified or removed.
2. The vehicle record in the database has been updated (`vehicles`).
3. The system notifies the Maintenance Manager with a success message.
4. If the status is set to **maintenance** or **disposed**: the vehicle is **removed from the live tracking map immediately** and **any assigned driver is detached**.
5. A vehicle with service history is never erased: it is kept with status **disposed**, and its trip and maintenance logs remain available for auditing.

---

## Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Duplicate Plate Number**
The plate number already exists. System rejects the request with *"The plate number has already been taken."* and stores nothing.

**E3 – Cannot Dispose Active Vehicle**
The Maintenance Manager tries to set the status to **disposed** while the assigned driver has an open timekeeping row for today (i.e. they are still clocked into the unit). System rejects the request on *status* with *"Cannot dispose a vehicle that a driver is currently clocked into. Clock the driver out first."* The vehicle keeps its previous status and driver.

**E4 – Assigning an Under-Maintenance Vehicle**
The Maintenance Manager tries to assign a driver to a vehicle whose status is **maintenance** or **disposed**. System rejects the request on *driver_id* with *"A driver cannot be assigned to a vehicle with a … status."* Keeping the vehicle's *existing* driver while changing the status out of service is allowed — the driver is simply detached when the status is saved.

**E5 – Missing Required Fields**
Any required field is empty or out of range (year, status, acquisition date). System lists the offending fields and performs no write.

**E6 – Cannot Delete a Vehicle With Service History**
The vehicle already has maintenance or preventive-maintenance records. System refuses the deletion with *"This vehicle has maintenance records and cannot be deleted. Mark its status as Disposed instead."*

---

## Known Gaps / Notes

- **"Active vehicle" in E3 means "clocked in right now", not "on the road"** — a vehicle assigned to a driver who is not clocked in can be disposed with no further check.
- **Hard deletion is still available** for a vehicle with no maintenance history; its location history rows are removed with it (foreign keys cascade).
- **There is no restore/undelete**: a deleted vehicle's plate number is free to reuse immediately.
- **Map removal is status-driven, not history-driven**: a vehicle under maintenance disappears from the map at once, while a vehicle marked *inactive* stays visible if it is still broadcasting.
- The **"Newest First" sort is by creation date**, not by last service or last location ping.
- The vehicle list is not paginated and loads the full fleet.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `vehicles.index`, `vehicles.store`, `vehicles.update`, `vehicles.destroy`, inside `role:maintenance_manager` |
| Controller | `app/Http/Controllers/VehicleController.php::index()`, `store()`, `update()`, `destroy()`, `assertDriverAssignable()`, `assertNotDisposedWhileInUse()` |
| Models | `app/Models/Vehicle.php`, `app/Models/TimeKeeping.php` |
| Live map | `app/Http/Controllers/VehicleTrackingController.php::getActiveVehicles()` — filters on `vehicles.status` |
| View | `resources/views/maintenance-manager/vehicles.blade.php` |
| Menu | `config/menu.php` — *Vehicles* (maintenance_manager) |
| Middleware | `auth`, `verified`, `role:maintenance_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_driver_cannot_be_assigned_to_a_vehicle_under_maintenance`, `test_a_vehicle_with_a_clocked_in_driver_cannot_be_disposed`, `test_setting_a_vehicle_to_maintenance_detaches_its_driver`, `test_a_vehicle_under_maintenance_is_not_shown_as_active`, `test_a_commuter_cannot_reach_the_vehicle_management_page`, `test_a_vehicle_with_maintenance_records_cannot_be_deleted`) |
| Related | UCN_SC_E016 (Maintenance Schedule), UCN_SC_E017 (Maintenance Tasks), UCN_SC_E009/E010 (clock in / out), UCN_SC_E003 (Track PUJ) |