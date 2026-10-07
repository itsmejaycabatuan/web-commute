# UCN_SC_E027 — Account Deletion

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E027                                                                                                                                      |
| **Use Case Name**     | Account Deletion                                                                                                                                 |
| **Primary Actor**     | Authenticated user (any role)                                                                                                                    |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow a user to permanently delete their own account, logging them out and removing all associated data.                                         |
| **Trigger**           | User clicks the **Delete Account** button (typically found in the settings or profile page) and confirms the action.                             |
| **Preconditions**     | 1. User is authenticated and logged in.<br>2. System is operational.<br>3. User intends to delete their own account (no administrative deletion of other users). |
| **Supporting Actors** | `User` model, Session store                                                                                                                      |

---

## MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User clicks **Delete Account** and confirms the action in a prompt (JavaScript `confirm()`). | System sends a `DELETE` request to `/delete-account` with the CSRF token.                                                                      |
| 2   |                                                                                   | The `UserController::destroy` method logs the action, logs the user out (`Auth::logout()`), invalidates the session (`$request->session()->invalidate()`), and regenerates the CSRF token. |
| 3   |                                                                                   | System attempts to delete the authenticated user’s record from the `users` table via `User::destroy($userId)`.                               |
| 4   |                                                                                   | If the deletion succeeds, System redirects the user to the login page (`/login`) with a success flash message: *“Account deleted successfully.”* |
| 5   |                                                                                   | The login page is displayed, and the user can now create a new account or log in with different credentials.                                   |

---

## Alternate Flow

**A1 – Unauthenticated attempt**
1. An unauthenticated user tries to access `/delete-account` (e.g., by typing the URL directly).
2. The `auth` middleware redirects the user to the login page (`/login`) with no deletion performed.

**A2 – Database deletion failure**
1. A PDO exception occurs while trying to delete the user record (e.g., foreign‑key constraint failure if another table references the user and the deletion is not cascaded, or a generic DB error).
2. System logs the exception, returns a generic error view or flashes *“Account deletion failed. Please try again later.”* and does **not** log the user out or invalidate the session (the user remains logged in).

**A3 – Unexpected server error**
1. Any other unhandled exception occurs during the controller logic.
2. System logs the exception and shows a 500 error page (or the Laravel default error page). The user’s session state is undefined; they may need to reload the page or log in again.

---

## Postconditions

- The authenticated user’s row in the `users` table is removed.
- The user’s session is terminated and the session ID is regenerated.
- Any data that is **not** tied to the user via foreign keys with `ON DELETE CASCADE` may become orphaned (e.g., related records in tables that do not have cascade delete set). In the current codebase, most related tables (e.g., `wallet`, `driver`, `time_keeping`, `violation_log`, `payment`, `topup_history`, `vehicle_location_history`) either have no foreign key to `users` or are set to cascade/delete via application logic (not enforced at the DB level). Consequently, deleting a user may leave orphaned rows; this is a known limitation.
- The user sees a success message and is returned to the login page.
- No further action is required; the user may register a new account if desired.

---

## Exceptions

**E1 – Authentication missing**
The request lacks a valid authenticated session. System redirects to `/login` (handled by middleware) and does not attempt deletion.

**E2 – Database delete failure**
A query exception occurs during `User::destroy()`. System logs the exception and returns an error message (“Account deletion failed. Please try again later.”) without logging out the user.

**E3 – Unexpected controller error**
Any other exception in the `destroy` method results in a 500 error page.

---

## Known Gaps / Notes

- The deletion is **hard delete**; there is no soft‑delete or “deactivated” state.
- The controller does **not** attempt to delete related records (e.g., `Wallet`, `Driver`, `TimeKeeping`, `ViolationLog`, `Payment`, `TopupHistory`, `VehicleLocationHistory`) explicitly; it relies on either:
  - Database‑level `ON DELETE CASCADE` foreign keys (if defined), or
  - The fact that many of those tables either do not have a foreign key to `users` or are cleared elsewhere (e.g., via event listeners). In the current schema, most tables either lack the foreign key or have it set to `NULL` on delete, which can leave orphaned rows.
- For a production‑ready implementation, consider adding explicit cleanup (e.g., deleting related records first) or using database cascades to avoid data inconsistency.
- The action is immediate and irreversible; there is no confirmation step beyond the browser’s `confirm()` dialog.
- After deletion, the user must register anew if they wish to use the system again.
- The route `/delete-account` is not grouped under any middleware besides `auth`; it is accessible to any authenticated user regardless of role.
- Success message is flashed via the session and displayed on the subsequent login page load.

---

## Implementation References

| Element | Location |
|---|---|
| Route | `routes/web.php` — `DELETE /delete-account` (`users.delete-account`) (inside `auth` middleware) |
| Controller | `app/Http/Controllers/UserController.php::destroy()` |
| Model | `App\Models\User` |
| Related UCNs | UCN_SC_E001 (Login – for post‑deletion login), UCN_SC_E002 (Create Account – for re‑registration), UCN_SC_E008 (Change Password – shows that account modification is possible) |