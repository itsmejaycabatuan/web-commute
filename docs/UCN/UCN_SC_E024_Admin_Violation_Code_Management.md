# UCN_SC_E024 — Admin Violation Code Management

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E024                                                                                                                                      |
| **Use Case Name**     | Admin Violation Code Management                                                                                                                  |
| **Primary Actor**     | Driver Manager (authenticated user holding the `driver_manager` role)                                                                            |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow an authorized user to create, view, edit, and delete violation codes (traffic offenses) that define the fines for each offense level.         |
| **Trigger**           | User navigates to `/driver-manager/violation-codes` to view the list, or submits the form to add, edit, or delete a code.                     |
| **Preconditions**     | 1. User is authenticated and logged in.<br>2. User holds the `driver_manager` role (the route group is `role:driver_manager`).<br>3. System is operational. |
| **Supporting Actors** | `ViolationCode` model                                                                                                                            |

---

## MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User opens the sidebar and selects **Violation Codes** (or navigates to `/driver-manager/violation-codes`). | System authenticates the user, verifies the `driver_manager` role, fetches all `ViolationCode` records, and returns the `driver-manager.violation-codes` view. |
| 2   |                                                                                   | The view renders a table listing each violation code with columns: **Code**, **Violation Name**, **1st Offense (₱)**, **2nd Offense (₱)**, **3rd Offense (₱)**, **4th+ Offense (₱)**, and **Actions** (Edit / Delete). A form at the top allows adding a new code. |
| 3   | To add a new code, the user fills in the form fields (Code, Name, fines for each offense level) and clicks **Add Code**. | System validates the request: `code` required, string, max 255, unique; `name` required, string, max 255; each fine (`first`, `second`, `third`, `fourth_plus`) required, numeric, min 0. On success, a new `ViolationCode` record is created and the user is redirected back with a success flash (“Violation code successfully added!”). |
| 4   | To edit an existing code, the user clicks the **Edit** button for a row, which populates the form with that code’s current values. After modifying the desired fields and clicking **Update Code**, System validates (same rules as creation, with the `unique` rule ignoring the current ID), updates the record, and returns a success flash (“Violation code successfully updated!”). |
| 5   | To delete a code, the user clicks the **Delete** button for a row. System first checks whether the code is referenced by any `ViolationLog` (i.e., in use). If referenced, deletion is blocked and an error flash is shown (“This violation code is in use by existing violation logs and cannot be deleted.”). If not referenced, the `ViolationCode` record is deleted and a success flash is shown (“Violation code successfully deleted!”). |
| 6   | After any successful add/edit/delete operation, the page refreshes (or the list is updated via AJAX) to reflect the current set of violation codes. |

---

## Alternate Flow

**A1 – Unauthenticated access attempt**
1. An unauthenticated user tries to access `/driver-manager/violation-codes` or submit to the store/update/delete endpoints.
2. The `auth` middleware redirects the user to the login page (`/login`).

**A2 – Invalid role**
1. An authenticated user lacking the `driver_manager` role attempts to access the URL.
2. The `role:driver_manager` middleware returns a 403 error (“Unauthorized.”).

**A3 – Validation failure (store/update)**
1. The user submits a form with missing required fields, duplicate code, or non‑numeric fine values.
2. Server‑side validation redirects back to the form (HTTP 422 only for JSON requests) with field‑specific error messages (e.g., *“The code has already been taken.”*, *“The first offense field must be a number.”*). The form is redisplayed with the errors and the previously entered values.

**A4 – Delete attempt on in‑use code**
1. User attempts to delete a violation code that has one or more associated `ViolationLog` records.
2. The `destroyViolationCode` method detects the reference and returns an error flash (“This violation code is in use by existing violation logs and cannot be deleted.”). The code remains in the database.

**A5 – Database error during CRUD operation**
1. A PDO exception occurs while inserting, updating, or deleting a `ViolationCode`.
2. System logs the exception, returns a generic error view or flashes *“An error occurred while processing the violation code. Please try again later.”* and leaves the database unchanged.

---

## Postconditions

- After a successful **add**, the `violation_codes` table contains a new row with the submitted attributes.
- After a successful **edit**, the existing row’s columns are updated to the new values.
- After a successful **delete**, the row is removed from the `violation_codes` table (provided it had no dependent `ViolationLog` rows).
- The user sees appropriate success or error messages via session flash.
- No unintended side‑effects on other tables (e.g., `ViolationLog` remains unchanged unless a delete is blocked).

---

## Exceptions

**E1 – Violation code not found (edit/delete)**
The requested `ViolationCode` ID does not exist. System returns an error flash (“Error no violation code found.”) and aborts the operation.

**E2 – Duplicate code**
During store or update, the submitted `code` already exists in the `violation_codes` table (ignoring the current row on update). System returns a validation error: *“The code has already been taken.”*

**E3 – Missing or invalid fields**
One or more required fields are absent, or a fine field is not a numeric value ≥ 0. System returns validation errors per field (e.g., *“The first offense field is required.”*, *“The second offense field must be a number.”*).

**E4 – Code in use**
When attempting to delete, the code is referenced by at least one row in `violation_logs`. System blocks deletion and returns the error flash noted in Alternate Flow A4.

**E5 – Database query/write failure**
A PDO exception occurs during any database interaction. System logs the exception and shows a generic error message.

---

## Known Gaps / Notes

- Fine amounts are stored as plain numbers (no currency formatting) in the database; the view prefixes them with “₱” for display.
- Violation codes carry **no license-revocation flag** (the feature was removed together with the `is_revoked` column); the 4th+ offense is always a fine.
- The UI includes a **Bulk Add** modal that allows entering multiple codes at once; each row is validated individually before insertion.
- The `code` field is limited to 255 characters and is expected to be a short identifier (the seeded codes are `UV01`–`UV14`; see *Seeded violation codes* below).
- Deletion is prohibited only when the code is referenced by `violation_logs`; there is no cascade‑delete or soft‑delete mechanism.
- The view uses Alpine.js for client‑side interactivity (modals, form handling) but all persistence is performed via standard POST/PUT/DELETE requests to the Laravel backend.
- The code table is filtered in the browser and shown **10 codes per page** with a pager. Add / edit / delete post in the background: a validation error keeps the modal open with the message under its field, and success closes it with a toast.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /driver-manager/violation-codes` (`driver-manager.violation-codes`), `POST /violation-codes/store` (`violation-codes.store`), `PUT /violation-codes/{id}/update` (`violation-codes.update`), `DELETE /violation-codes/{id}/delete` (`violation-codes.destroy`) (all inside `role:driver_manager` middleware group) |
| Controller | `app/Http/Controllers/DriverManagerController.php::violationCodes()`, `storeViolationCode()`, `updateViolationCode()`, `destroyViolationCode()` |
| View | `resources/views/driver-manager/violation-codes.blade.php` |
| Model | `App\Models\ViolationCode` |
| Related UCNs | UCN_SC_E014 (Manage Violations – view logs), UCN_SC_E012 (Manage Drivers – approval/reject) |
| Seeder | `database/seeders/ViolationCodeSeeder.php` (called by `DatabaseSeeder`; `DevSeeder` calls it only when the table does not hold the seeded set) |
| Tests (automation) | `tests/Feature/AutomationTest.php` — `test_seeder_replaces_the_violation_codes_with_the_puj_schedule`, `test_seeder_is_repeatable` |

---

## Seeded violation codes

`php artisan db:seed` (`ViolationCodeSeeder`) loads the violations most often written up against Philippine public utility jeepney (PUJ) drivers and operators. **Running it replaces the whole table**: codes made earlier are removed and, because a violation log belongs to its code, so are their logged violations. The amounts follow the LTO traffic schedule, LTFRB/DOTr-LTO Joint Administrative Order 2014-01 (franchise and conduct violations) and RA 10913 (anti-distracted driving); sources disagree on some figures and they are revised from time to time, so treat them as a **starting schedule** to check against current LTFRB / LTO issuances. Any amount can be edited above.

| Code | Violation | 1st | 2nd | 3rd | 4th+ |
|---|---|---:|---:|---:|---:|
| `UV01` | Disregarding Traffic Signs / Signals | ₱1,000 | ₱1,000 | ₱1,000 | ₱1,000 |
| `UV02` | Reckless Driving | ₱2,000 | ₱10,000 | ₱10,000 | ₱10,000 |
| `UV03` | Using a Mobile Phone While Driving | ₱5,000 | ₱10,000 | ₱15,000 | ₱20,000 |
| `UV04` | Driving Without a Valid Driver's License | ₱3,000 | ₱3,000 | ₱3,000 | ₱3,000 |
| `UV05` | Unregistered or Expired Vehicle Registration | ₱10,000 | ₱10,000 | ₱10,000 | ₱10,000 |
| `UV06` | Operating Without Franchise (Colorum) | ₱50,000 | ₱100,000 | ₱200,000 | ₱200,000 |
| `UV07` | Operating Outside the Authorized Route | ₱5,000 | ₱10,000 | ₱50,000 | ₱50,000 |
| `UV08` | Overcharging of Fare | ₱5,000 | ₱10,000 | ₱15,000 | ₱15,000 |
| `UV09` | Refusal to Convey Passengers | ₱5,000 | ₱10,000 | ₱15,000 | ₱15,000 |
| `UV10` | Trip Cutting | ₱5,000 | ₱10,000 | ₱15,000 | ₱15,000 |
| `UV11` | Overloading of Passengers | ₱1,500 | ₱3,000 | ₱5,000 | ₱5,000 |
| `UV12` | Defective Parts or Accessories (Unroadworthy Vehicle) | ₱500 | ₱1,000 | ₱1,500 | ₱1,500 |
| `UV13` | Failure to Display Fare Matrix or Franchise Details | ₱500 | ₱1,000 | ₱1,500 | ₱2,000 |
| `UV14` | Illegal Loading and Unloading of Passengers | ₱500 | ₱1,000 | ₱1,500 | ₱1,500 |
