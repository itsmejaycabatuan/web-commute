# UCN_SC_E024 — Admin Violation Code Management

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E024                                                                                                                                      |
| **Use Case Name**     | Admin Violation Code Management                                                                                                                  |
| **Primary Actor**     | Admin / Driver Manager (authenticated user holding the `driver_manager` role; admins with equivalent permissions can also access)            |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow an authorized user to create, view, edit, and delete violation codes (traffic offenses) that define fines and revocation rules.         |
| **Trigger**           | User navigates to `/driver-manager/violation-codes` to view the list, or submits the form to add, edit, or delete a code.                     |
| **Preconditions**     | 1. User is authenticated and logged in.<br>2. User holds the `driver_manager` role (or an admin with the same permissions).<br>3. System is operational. |
| **Supporting Actors** | `ViolationCode` model                                                                                                                            |

---

## MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User opens the sidebar and selects **Violation Codes** (or navigates to `/driver-manager/violation-codes`). | System authenticates the user, verifies the `driver_manager` role, fetches all `ViolationCode` records, and returns the `driver-manager.violation-codes` view. |
| 2   |                                                                                   | The view renders a table listing each violation code with columns: **Code**, **Violation Name**, **1st Offense (₱)**, **2nd Offense (₱)**, **3rd Offense (₱)**, **4th+ Offense (₱)**, **Revocation?**, and **Actions** (Edit / Delete). A form at the top allows adding a new code. |
| 3   | To add a new code, the user fills in the form fields (Code, Name, fines for each offense level, Revocation toggle) and clicks **Add Code**. | System validates the request: `code` required, string, max 255, unique; `name` required, string, max 255; each fine (`first`, `second`, `third`, `fourth_plus`) required, numeric, min 0; `is_revocation` required, boolean. On success, a new `ViolationCode` record is created and the user is redirected back with a success flash (“Violation code successfully added!”). |
| 4   | To edit an existing code, the user clicks the **Edit** button for a row, which populates the form with that code’s current values. After modifying the desired fields and clicking **Update Code**, System validates (same rules as creation, with the `unique` rule ignoring the current ID), updates the record, and returns a success flash (“Violation code successfully updated!”). |
| 5   | To delete a code, the user clicks the **Delete** button for a row. System first checks whether the code is referenced by any `ViolationLog` (i.e., in use). If referenced, deletion is blocked and an error flash is shown (“This violation code is in use by existing violation logs and cannot be deleted.”). If not referenced, the `ViolationCode` record is deleted and a success flash is shown (“Violation code successfully deleted!”). |
| 6   | After any successful add/edit/delete operation, the page refreshes (or the list is updated via AJAX) to reflect the current set of violation codes. |

---

## Alternate Flow

**A1 – Unauthenticated access attempt**
1. An unauthenticated user tries to access `/driver-manager/violation-codes` or submit to the store/update/delete endpoints.
2. The `auth` middleware redirects the user to the login page (`/login`).

**A2 – Invalid role**
1. An authenticated user lacking the `driver_manager` role (and not an admin with equivalent permissions) attempts to access the URL.
2. The `role:driver_manager` middleware returns a 403 error (“Unauthorized.”).

**A3 – Validation failure (store/update)**
1. The user submits a form with missing required fields, duplicate code, or non‑numeric fine values.
2. Server‑side validation returns HTTP 422 with field‑specific error messages (e.g., *“The code field must be unique.”*, *“The first offense field must be a number.”*). The form is redisplayed with the errors and the previously entered values.

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
During store or update, the submitted `code` already exists in the `violation_codes` table (ignoring the current row on update). System returns a validation error: *“The code field must be unique.”*

**E3 – Missing or invalid fields**
One or more required fields are absent, or a fine field is not a numeric value ≥ 0. System returns validation errors per field (e.g., *“The first offense field is required.”*, *“The second offense field must be a number.”*).

**E4 – Code in use**
When attempting to delete, the code is referenced by at least one row in `violation_logs`. System blocks deletion and returns the error flash noted in Alternate Flow A4.

**E5 – Database query/write failure**
A PDO exception occurs during any database interaction. System logs the exception and shows a generic error message.

---

## Known Gaps / Notes

- Fine amounts are stored as plain numbers (no currency formatting) in the database; the view prefixes them with “₱” for display.
- The `is_revocation` column is a boolean; when true, the “4th+ Offense” column shows the label **Revocation** instead of a numeric fine.
- The UI includes a **Bulk Add** modal that allows entering multiple codes at once; each row is validated individually before insertion.
- The `code` field is limited to 255 characters and is expected to be a short identifier (e.g., “UV01”, “Speeding”).
- Deletion is prohibited only when the code is referenced by `violation_logs`; there is no cascade‑delete or soft‑delete mechanism.
- The view uses Alpine.js for client‑side interactivity (modals, form handling) but all persistence is performed via standard POST/PUT/DELETE requests to the Laravel backend.
- No pagination is applied; the table assumes a modest number of violation codes (typically under a few dozen).

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /driver-manager/violation-codes` (`driver-manager.violation-codes`), `POST /violation-codes/store` (`storeViolationCode`), `PUT /violation-codes/{id}/update` (`updateViolationCode`), `DELETE /violation-codes/{id}/delete` (`destroyViolationCode`) (all inside `role:driver_manager` middleware group) |
| Controller | `app/Http/Controllers/DriverManagerController.php::violationCodes()`, `storeViolationCode()`, `updateViolationCode()`, `destroyViolationCode()` |
| View | `resources/views/driver-manager/violation-codes.blade.php` |
| Model | `App\Models\ViolationCode` |
| Related UCNs | UCN_SC_E014 (Manage Violations – view logs), UCN_SC_E012 (Manage Drivers – approval/reject) |