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
4. System validates: `name`, `license_number`, `license_code`, `expiration_date`, `contact_info`, `license_image` **required**; `email` **required, email, unique:users**; `password` **required, min 8, confirmed**; `license_image` **image, max 2048**; `license_number` **unique:drivers** (E2).
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
4. System deletes the **User** and the **Driver** record and confirms *"Driver removed."* — unless E8 applies. A driver who holds a vehicle shows a lock instead of the delete icon.

**A4 – View Driver Details**
1. In the list, the actor clicks the ID-card icon on a driver row.
2. System displays a read-only modal with the driver's identity, licence data and expiry.
3. From that modal the actor may jump straight into A2 (**"Edit"**).
4. The list has a **License** column: a badge reads *Valid*, *Expires in N days* (30 days or fewer) or *Expired*, with the expiry date below it, and the **License Expiring** / **License Expired** filter chips narrow the list. The driver sees the same warning on their own dashboard.

**A5 – Suspend / Reactivate a Driver**
1. In the list, the actor clicks the suspend icon (or *reactivate* when the driver is already suspended). Only an **approved** driver can be suspended — the icon is not shown for a pending or rejected registration and the server refuses it. A suspension also takes effect on a session that is already open: the driver's next request signs them out.
2. System displays a confirmation dialog: suspending states that the driver *"will not be able to sign in, clock in or broadcast a location, and is detached from any assigned vehicle"*; an optional **Reason** field is provided.
3. Driver Manager confirms.
4. System sets `is_suspended`, records `suspended_at` and the reason, moves the driver `status` to **suspended**, **detaches any assigned vehicle**, logs the action and confirms *"Driver suspended. They can no longer sign in."*
5. Reactiving reverses all of it (`status` back to **inactive**) and confirms *"Driver reactivated."*

**A6 – Approve or Reject a Driver Application**
1. A **pending** driver row shows a **"Review"** button instead of the ID-card icon.
2. Driver Manager opens the review modal (identity fields next to the licence image) and chooses **"Approve"** or **"Reject"**.
3. System validates the approve payload (`license_number` unique, `license_code`, `expiration_date`), assigns the generated driver code when the driver has none yet, sets `is_approved = 1` and confirms *"Driver approved. They can sign in now."*, or sets `is_rejected = 1` and confirms *"Driver registration rejected. They cannot sign in until you approve them again."*

---

## Postconditions

1. A driver record has been added, modified, suspended, reactivated or removed from the drivers list.
2. The driver record in the database has been updated accordingly (`drivers` + `users`).
3. The system notifies the actor with a success message.
4. On suspension: the account is locked out of sign-in, clock-in and location broadcast, and any assigned vehicle is released.
5. On deletion: the driver no longer appears in the list, in driver pickers, or in the driver's own sign-in.

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

- **Drivers added by an admin are approved immediately** (`is_approved = true`), so the approve/reject flow effectively applies only to self-registered drivers (UCN_SC_E002).
- **Suspension has no expiry and no auto-reactivation** — it must be lifted manually.
- **A suspended driver is signed out on their next request**: the `account.active` middleware re-checks the account on every authenticated request, ends the session and sends the driver to the login page (JSON calls get a 403 with the redirect). Sign-in, clock-in, status changes and location broadcast are all refused.
- **There is no "un-approve"** — the reverse of approve is commented out in the controller.
- Deleting a driver does not remove the uploaded licence image from storage when it was stored on disk.
- The list is not paginated; it loads every driver.

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
| Related | UCN_SC_E002 (driver self-registration), UCN_SC_E009/E010 (clock in / out), UCN_SC_E013 (timekeeping), UCN_SC_E014 (violations), UCN_SC_E015 (vehicles) |