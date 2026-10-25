# UCN_SC_E012 — Manage Drivers

| Field                 | Value                                                                                          |
| --------------------- | ---------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E012                                                                                    |
| **Use Case Name**     | Manage Drivers                                                                                 |
| **Primary Actor**     | Driver Manager / Admin (`role:admin|driver_manager`)                                           |
| **Secondary Actor**   | System, Email Service                                                                          |
| **Goal**               | Allow the Driver Manager to review, add, edit, suspend, approve or reject driver accounts in the system. |
| **Trigger**           | Actor selects the **"PUJ Drivers"** (Admin) or **"Drivers"** (Driver Manager) item on the sidebar (`GET /drivers`). |
| **Preconditions**     | 1. Driver Manager / Admin is authenticated and registered to the system.<br>2. The actor holds a role permitted to manage driver data. |
| **Supporting Actors** | User record, Driver record, Vehicle record, Activity log                                        |

> **Corrections vs. the original narrative**
> - The sidebar item is **"PUJ Drivers"** for an Admin and **"Drivers"** for a Driver Manager (see `config/menu.php`); the original just said "Drivers".
> - The table columns are **`#`, `Driver`, `Status`, `Registered`, `Actions`** — the original said *"Driver ID, Name, Status and Action buttons"*, and there is no separate ID column.
> - The add button is **"Add Driver"**, not *"Create Driver"*; the commit button in that modal is **"Create Driver"**.
> - The `Driver ID` in exception E2 is the **license number** (the field is unique); the `driver_code` is also unique but is not what the original message described.
> - **Suspend / Reactivate (A4)** and the *"a driver holding a vehicle cannot be deleted"* guard (E8) were added after the original narrative was written.
> - The original main flow left rows 3–8 empty; those steps are the alternate flows below.

---

## MAIN FLOW

| #   | Actor's Action                                                          | System Response                                                                                                                                          |
| --- | ------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver Manager clicks the **"PUJ Drivers" / "Drivers"** sidebar item.   | System redirects to the driver management page (`admin.drivers.index`).                                                                                     |
| 2   |                                                                          | System displays the driver table with columns **`#`, `Driver`, `Status`, `Registered`, `Actions`**, a search box, status filter chips (*All / Pending / Approved / Rejected / Suspended*) and an **"Add Driver"** button. |

> Steps 3+ are alternate flows (A1–A6); the main flow is "open the list and read it".

---

## Alternate Flow

**A1 – Add a Driver**
1. In the list, the actor clicks **"Add Driver"**.
2. System displays the **Add Driver** modal: *Name, Email, Password, Confirm Password, License Number, License Code, Expiration Date, Contact Info, License Image*. The **driver code is not typed in**: it is generated automatically (`DRV-000001`, `DRV-000002`, …).
3. Driver Manager inputs all required fields and clicks **"Create Driver"**.
4. System validates: `name`, `license_number`, `license_code`, `expiration_date`, `contact_info`, `license_image` **required**; `email` **required, email, unique:users**; `password` **required, min 8, confirmed**; `license_image` **image, jpg / jpeg / png, max 2048 KB**; `contact_info` **a Philippine mobile number** (`9XX XXX XXXX`, optionally with `0` or `+63`); `license_number` **unique:drivers** (E2).
5. System stores the licence image (`storage/app/public/licenses`), creates the **User** (email verified immediately, role `driver`), creates the **Driver** profile with `is_approved = 1` and a generated driver code, and confirms *"Driver account created."* A validation failure keeps the modal open and shows each message under its own field.

**A2 – Edit a Driver**
1. In the list, the actor clicks the edit icon on a driver row.
2. System displays the editable **Driver** form (name, licence fields, contact; the driver code is shown read-only) together with the stored licence image. The edit icon is **not offered for a pending registration** — a pending driver can only be approved or rejected (A6), and the server refuses the update with *"This driver is still pending approval…"*.
3. Driver Manager updates the fields and clicks **"Save Changes"**.
4. System validates the same field set as A1 (ignoring the current row for uniqueness), optionally replaces the licence image, updates the record and confirms *"Driver updated."*

**A3 – Delete a Driver**
1. In the list, the actor clicks the delete icon on a driver row.
2. System displays a confirmation dialog ("Delete Driver?").
3. Driver Manager confirms with **"Delete"**.
4. System deletes the **User** and the **Driver** record and confirms *"Driver removed."* — unless E8 applies. A driver who also has payments or top-ups is **anonymised** instead: the driver profile and licence image are removed, the user becomes an anonymised shell, and the payments and top-ups are kept (an `anonymised` audit entry is written instead of `deleted`). A driver who holds a vehicle shows a lock instead of the delete icon.

**A4 – View Driver Details**
1. In the list, the actor clicks the ID-card icon on a driver row.
2. System displays a read-only modal with the driver's identity, licence data and expiry, followed by the driver's **Timekeeping** (latest 10 days, with total days / hours / overtime) and **Violations** (latest 10, with fine, **Paid / Unpaid** status and the outstanding total). Both are fetched when the modal opens (`GET /drivers/{id}/records`), so the list itself stays light.
3. From that modal the actor may jump straight into A2 (**"Edit"**).
4. The list has a **License** column: a badge reads *Valid*, *Expires in N days* (30 days or fewer) or *Expired*, with the expiry date below it, and the **License Expiring** / **License Expired** filter chips narrow the list. The **View** modal shows the same state on its *Expires* card (red date + *Expired* badge, or amber *Expires in N days*). The driver sees the warning on their own dashboard and at every sign-in. The Add / Edit modals check the same things the registration form does while typing: e-mail format, password length (8) and confirmation, a Philippine mobile number (`+63`, `9XX XXX XXXX`, formatted as you type) and a JPG / PNG licence image of at most 2 MB; the server repeats every check.

**A5 – Suspend / Reactivate a Driver**
1. In the list, the actor clicks the suspend icon (or *reactivate* when the driver is already suspended). Only an **approved** driver can be suspended — the icon is not shown for a pending or rejected registration and the server refuses it. A suspension also takes effect on a session that is already open: the driver's next request signs them out.
2. System displays a confirmation dialog: suspending states that the driver *"will not be able to sign in, clock in or broadcast a location, and is detached from any assigned vehicle"*; an optional **Reason** field is provided.
3. Driver Manager confirms.
4. System sets `is_suspended`, records `suspended_at` and the reason, moves the driver `status` to **suspended**, **detaches any assigned vehicle** (and removes them from its duty roster from today on), writes a `suspended` audit-trail entry (with the reason) and confirms *"Driver suspended. They can no longer sign in."* The seat they held is then offered to a replacement (A8).
5. Reactivating reverses all of it (`status` back to **inactive**), starts a fresh inactivity clock (`reactivated_at`, A7) and confirms *"Driver reactivated."* The driver is not put back on the vehicle they left.

**A6 – Approve or Reject a Driver Application**
1. A **pending** driver row shows a **"Review"** button instead of the ID-card icon.
2. Driver Manager opens the review modal (identity fields next to the licence image) and chooses **"Approve"** or **"Reject"**.
3. System validates the approve payload (`license_number` unique, `license_code`, `expiration_date`), assigns the generated driver code when the driver has none yet, sets `is_approved = 1` and confirms *"Driver approved. They can sign in now."*, or sets `is_rejected = 1` and confirms *"Driver registration rejected. They cannot sign in until you approve them again."* Either way the **driver is emailed the decision** (`DriverApplicationDecided`).

**A7 – A driver who stays away is warned, then suspended (automatic)**
1. Every day at 08:00 the scheduler runs `php artisan drivers:inactivity-check` over every approved, not-rejected, not-suspended driver and counts the **days since the driver last clocked in**. Leave a Driver Manager recorded counts as activity; a driver who never clocked in counts from the day the account was created or last reinstated (`reactivated_at`).
2. At `DRIVER_INACTIVITY_WARN_AFTER_DAYS` (default **3**) the driver is emailed once (`DriverInactivityWarning`) to contact HR about the inactivity (`HR_EMAIL` is named in the mail), and `inactivity_warned_at` is stamped. An *Inactivity warning sent* entry is written to the audit trail.
3. Once the driver has been away **more than** `DRIVER_INACTIVITY_SUSPEND_AFTER_DAYS` (default **7**) **and** the warning has been out for at least a day, the system suspends them exactly as in A5 (reason *"Automatically suspended: inactive for N days"*), logged as `suspended` with `automatic: true`. A driver is **never suspended without a warning first**.
4. Clocking in again clears the warning, so the next lapse starts afresh. `--dry-run` lists who would be warned or suspended without emailing or suspending anyone.

**A8 – A driver who leaves a vehicle is replaced automatically**
1. A driver leaves a vehicle when they are **suspended** (A5 or A7) or when their **licence expires** (the hourly `vehicles:cover-vacancies` job takes an approved, not-suspended driver with an expired licence off their vehicle exactly as a suspension would, emails them, and lets them keep their account).
2. The seat they held becomes a **vacancy** (`driver_vacancies`: vehicle, former driver, reason, the number of drivers the vehicle should have again). A manual unassign on the Vehicles page is never a vacancy.
3. With `AUTO_REPLACE_DRIVERS` on (default) the system picks the best **free** driver — approved, not suspended, licence not expired, no vehicle, no inactivity warning outstanding — ranked by a valid (rather than soon-to-expire) licence, then fewest violations and least overtime in the last 90 days, and assigns them.
4. If the vehicle is **shared** again (two drivers) a 30-day alternating duty roster is written from today, the driver already on the vehicle first (UCN_SC_E015 A6); a single-driver vehicle needs none.
5. The replacement is emailed (vehicle, who they share it with, next duty day), the Maintenance Managers are told, and the audit trail records *vacancy opened* and *replacement assigned*.
6. If nobody is free the vacancy stays open: it is shown on the Maintenance Manager's dashboard, retried every hour, and reported to the Maintenance Managers once a day with the best candidates. A vacancy whose vehicle left service, or that a manager already staffed, is closed as *not needed* instead. With `AUTO_REPLACE_DRIVERS=false` the system only reports the vacancy and suggests the drivers.

---

## Postconditions

1. A driver record has been added, modified, suspended, reactivated or removed from the drivers list.
2. The driver record in the database has been updated accordingly (`drivers` + `users`).
3. The system notifies the actor with a success message.
4. On suspension: the account is locked out of sign-in, clock-in and location broadcast, and any assigned vehicle is released.
5. On deletion: the driver no longer appears in the list, in driver pickers, or in the driver's own sign-in.

6. A new application and each approval or rejection have been emailed (Admins and Driver Managers are told of an application — UCN_SC_E022; the driver is told the decision — A6).
7. A driver who left a vehicle by suspension or an expired licence has left a vacancy, and a replacement has been assigned or the vacancy is waiting for one (A8).
---

## Exceptions

**E1 – Connection Error**
The list or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Duplicate License Number**
The license number entered already exists on another driver. System rejects the request with *"The license number has already been taken."* (enforced by a `unique` rule **and** a unique index on `drivers.license_number`); no record is written.

**E3 – Missing Required Fields**
Any required field is empty or fails its format rule. System lists the offending fields and performs no write.

**E4 – Database Error**
The insert/update/delete fails at the database level. System cancels the operation and notifies the actor.

**E5 – Duplicate Email**
The email address is already registered. System rejects the request on the *email* field (`unique:users,email`).

**E6 – Weak Password / Passwords Do Not Match**
The password is under 8 characters or the confirmation differs (`password` **required, min 8, confirmed**); no account is created.

**E7 – Invalid Licence Image**
The uploaded file is not an image or exceeds 2 MB (`image, max:2048`).

**E8 – Cannot Delete a Driver With a Vehicle**
The driver is still assigned to a vehicle. System refuses the deletion with *"This driver is still assigned to a vehicle. Unassign the vehicle first."* so the vehicle is not orphaned.

**E9 – Already Approved / Already Rejected**
The driver is already in that state. System refuses with *"This driver is already approved."* / *"This driver is already rejected."* On approve, the licence fields are validated first, so a missing or duplicate field is reported (**E2** / **E3**) before the already-approved check.

---

## Known Gaps / Notes

- **Suspension and an expired licence both free the vehicle.** A suspended driver is detached and replaced (A8); an expired-licence driver is released the same way, not suspended. Turn the licence release off with `RELEASE_EXPIRED_LICENCE_DRIVERS=false`.

- **A suspended driver's seat is refilled automatically.** Suspending a driver (by an Admin, or by the inactivity job) takes them off their vehicle and opens a vacancy; the system assigns the best free driver, rebuilds the duty roster if the vehicle is shared, and emails the replacement and the Maintenance Managers. Nobody free: the vacancy stays open and is reported daily. See `docs/AUTOMATED_FEATURES.md`.

- **Drivers added by an admin are approved immediately** (`is_approved = true`), so the approve/reject flow effectively applies only to self-registered drivers (UCN_SC_E002).
- **Suspension has no expiry and no auto-reactivation** — it must be lifted manually (and an automatic inactivity suspension (A7) needs the same manual lift).
- **A suspended driver is signed out on their next request**: the `account.active` middleware re-checks the account on every authenticated request, ends the session and sends the driver to the login page (JSON calls get a 403 with the redirect). Sign-in, clock-in, status changes and location broadcast are all refused.
- **There is no "un-approve"** — the reverse of approve is commented out in the controller.
- Deleting a driver does not remove the uploaded licence image from storage when it was stored on disk.
- The list is filtered in the browser and shown **10 drivers per page** with a pager (the server still loads every driver).

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `drivers.index`, `drivers.store`, `drivers.create`, `drivers.edit`, `drivers.update`, `drivers.destroy`, `drivers.approve`, `drivers.reject`, `drivers.suspension` (all inside `role:admin|driver_manager`) |
| Controller | `app/Http/Controllers/Admin/DriverApprovalController.php::index()`, `store()`, `edit()`, `update()`, `destroy()`, `toggleSuspension()`, `approve()`, `reject()`, `showLicense()` |
| Model | `app/Models/Driver.php` (`isSuspended()`, `suspend()`, `unsuspend()`), `app/Models/User.php::driver()` |
| View | `resources/views/admin/drivers/index.blade.php` |
| Menu | `config/menu.php` — *PUJ Drivers* (admin), *Drivers* (driver_manager) |
| Middleware | `auth`, `verified`, `role:admin|driver_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php`, `tests/Feature/UcnSuspensionTest.php` |
| Inactivity | `app/Console/Commands/DriverInactivityCheck.php` (`drivers:inactivity-check`, daily 08:00), `app/Notifications/DriverInactivityWarning.php`, `Driver::daysInactive()`, `Driver::inactiveSince()`, `config/timekeeping.php` (`inactivity_*`, `hr_email`); schema `2026_10_26_090000_add_inactivity_tracking_to_drivers_table.php` |
| Replacement | `app/Services/ReplacementDriverService.php`, `app/Console/Commands/CoverVacancies.php` (`vehicles:cover-vacancies`, hourly), `app/Models/DriverVacancy.php`, `Driver::suspend()` / `releaseFromVehicles()`, `config/fleet.php`; schema `2026_10_27_090000_create_driver_vacancies_table.php` |
| Emails | `app/Notifications/DriverApplicationDecided.php`, `ReplacementAssigned.php`, `VehicleVacancyFilled.php`, `VehicleVacancyOpen.php`, `LicenceExpiredRelease.php`, via `app/Support/Notifier.php` |
| Tests (automation) | `tests/Feature/ReplacementDriverTest.php` (22), `tests/Feature/AutomationPhase2Test.php` (`test_driver_hears_about_approval_and_rejection`), `tests/Feature/AutomationTest.php` (inactivity) |
| Related | UCN_SC_E002 (driver self-registration), UCN_SC_E009/E010 (clock in / out), UCN_SC_E013 (timekeeping), UCN_SC_E014 (violations), UCN_SC_E015 (vehicles) |
