# UCN_SC_E017 — Manage Maintenance Tasks

| Field                 | Value                                                                                        |
| --------------------- | ---------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E017                                                                                  |
| **Use Case Name**     | Manage Maintenance Tasks                                                                     |
| **Primary Actor**     | Maintenance Manager (`role:maintenance_manager`)                                              |
| **Secondary Actor**   | System / Admin (read-only role boundaries)                                                    |
| **Goal**               | Allow the Maintenance Manager to add, edit or delete the maintenance tasks / services used across the fleet. |
| **Trigger**           | Maintenance Manager selects the **"Maintenance Tasks"** item on the sidebar (`GET /maintenance-tasks`). |
| **Preconditions**     | 1. Maintenance Manager is authenticated and registered to the system.<br>2. Maintenance task data exists in the database. |
| **Supporting Actors** | Maintenance task, Preventive maintenance, Vehicle maintenance log                             |

> **Corrections vs. the original narrative**
> - The sidebar item is **"Maintenance Tasks"** (plural), not *"Maintenance Task"*.
> - **A2 and A3 were copy/paste errors in the original** — they were titled *"wants to edit a vehicle"* / *"wants to delete a vehicle"* in a use case about tasks. They are corrected here to **edit** and **delete a task**.
> - The commit buttons are **"Create Task"** (add) and **"Update Task"** (edit), not *"Save Changes"*.
> - The table columns are **`Task Performed`, `Km Interval`, `Month Interval`, `Actions`**.

---

## MAIN FLOW

| #   | Actor's Action                                                        | System Response                                                                                       |
| --- | ---------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| 1   | Maintenance Manager opens the dashboard / map view.                    | System displays the dashboard with the maintenance-manager analytics.                                    |
| 2   | Maintenance Manager clicks **"Maintenance Tasks"** on the sidebar.     | System redirects to the maintenance tasks page (`maintenance-manager.maintenance-tasks`), showing a summary strip and the task table. |
| 3   |                                                                            | System displays the task table with columns **`Task Performed`, `Km Interval`, `Month Interval`, `Actions`** and an **"Add Task"** button. |

> Steps 4+ are the alternate flows (A1–A3); the main flow is "open the list and read it".

---

## Alternate Flow

**A1 – Add a Maintenance Task to the List**
1. In the list, the Maintenance Manager clicks **"Add Task"**.
2. System displays the task modal: *Task Performed*, *Km Interval*, *Month Interval*.
3. Maintenance Manager inputs the required fields and clicks **"Create Task"**.
4. System validates `tasks_performed` **required, string, max 255**; `miles_between_service` and `months_between_service` **nullable, integer**.
5. System stores the task and confirms *"Task successfully added!"*

**A2 – Edit a Maintenance Task**
1. In the list, the Maintenance Manager clicks the edit icon on a task row.
2. System displays the **Edit Task** modal pre-filled with the stored values.
3. Maintenance Manager updates the fields and clicks **"Update Task"**.
4. System applies the same validation as A1, updates the record and confirms *"Task successfully updated!"*

**A3 – Delete a Maintenance Task**
1. In the list, the Maintenance Manager clicks the delete icon on a task row.
2. System displays the *"Delete Task?"* confirmation.
3. Maintenance Manager clicks **"Delete"**.
4. System deletes the task and confirms *"Task successfully deleted!"*

---

## Postconditions

1. The Maintenance Manager has successfully added, edited or deleted a task or service.
2. The service record in the database has been updated (`maintenance_tasks`).
3. The system notifies the Maintenance Manager with a success message.
4. A task's `miles_between_service` / `months_between_service` drive the projected next-due odometer and date on the preventive-maintenance board (UCN_SC_E016).

---

## Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Missing Required Task Details**
`tasks_performed` is empty or over 255 characters. System lists the offending fields and stores nothing.

**E3 – Database Error**
The insert/update/delete fails at the database level. System cancels the operation and notifies the Maintenance Manager.

---

## Known Gaps / Notes

- **Task names are not unique** — the same task can be added twice and will then appear twice on every vehicle's schedule.
- **Deleting a task cascades** to its preventive-maintenance rows and to the service logs that reference it, so historical cost/km figures for that task disappear from the fleet analytics.
- **Intervals are optional**, so a task without `miles_between_service` / `months_between_service` produces no projected due date.
- There is no deactivation: a task in use must be deleted (with the data loss above) or left in place.
- The task list is not paginated.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `maintenance-manager.maintenance-tasks` (`GET /maintenance-tasks`), `…tasks.store` (`POST`), `…tasks.update` (`PUT /maintenance-tasks/{task}`), `…tasks.destroy` (`DELETE`), all inside `role:maintenance_manager` |
| Controller | `app/Http/Controllers/MaintenanceManagerController.php::maintenanceTasks()`, `maintenanceTasksStore()`, `maintenanceTasksUpdate()`, `maintenanceTasksDestroy()` |
| Model | `app/Models/MaintenanceTask.php` |
| View | `resources/views/maintenance-manager/maintenance-tasks.blade.php` |
| Menu | `config/menu.php` — *Maintenance Tasks* (maintenance_manager) |
| Middleware | `auth`, `verified`, `role:maintenance_manager` |
| Related | UCN_SC_E015 (Manage Vehicles), UCN_SC_E016 (Manage Maintenance Schedule), UCN_SC_E020 (System Reports — maintenance report) |