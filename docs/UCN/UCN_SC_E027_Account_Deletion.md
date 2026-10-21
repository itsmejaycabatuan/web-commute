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
| 2   |                                                                                   | The `UserController::destroy` method writes a `deleted_self` audit-trail entry (keeping the email, because the account is about to go), logs the user out (`Auth::logout()`), invalidates the session (`$request->session()->invalidate()`), and regenerates the CSRF token. |
| 3   |                                                                                   | System calls `User::eraseAccount()`. An account with **no payments or top-ups** is deleted from `users` (its wallet, preferences and other rows go with it). An account **with** payments or top-ups is **anonymised** instead: its email becomes `deleted-<id>@deleted.invalid`, and its password, roles, API tokens, sessions, preferences, reset links, map markers and any driver profile are removed, so it can never sign in again — while the user row, wallet, payments and top-ups are kept.                               |
| 4   |                                                                                   | If the deletion succeeds, System redirects the user to the login page (`/login`) with a success toast: *“Account deleted successfully.”* An account whose **wallet still holds money** is refused first (*"Your wallet still holds PHP N. Use it up before deleting your account."*) and stays signed in. A driver who still holds a vehicle is refused first (*"You are still assigned to a vehicle…"*) and stays signed in. |
| 5   |                                                                                   | The login page is displayed, and the user can now create a new account or log in with different credentials.                                   |

---

## Alternate Flow

**A1 – Unauthenticated attempt**
1. An unauthenticated user tries to access `/delete-account` (e.g., by typing the URL directly).
2. The `auth` middleware redirects the user to the login page (`/login`) with no deletion performed.

**A2 – Database deletion failure**
1. A PDO exception occurs while trying to delete the user record (a generic DB error — payments and top-ups can never be the cause: their foreign keys are `RESTRICT`, which is why an account that has them is anonymised rather than deleted).
2. The exception is **not caught** by the controller, so no friendly message is produced — the request falls through to the default 500 error page. The logout/session invalidation at step 2 have already executed, so the session on this device is already ended.

**A3 – Unexpected server error**
1. Any other unhandled exception occurs during the controller logic.
2. System logs the exception and shows a 500 error page (or the Laravel default error page). The user’s session state is undefined; they may need to reload the page or log in again.

---

## Postconditions

- The user’s session is terminated and the session ID is regenerated.
- **Deleted** (no payments or top-ups): the user's row in `users` is removed, and with it, by cascade, the wallet, preferences, location rows, driver profile (and its timekeeping) and violation logs.
- **Anonymised** (payments or top-ups exist): the user row stays with a placeholder email and `anonymised_at` set; the account is suspended, has no roles, an unknown password, no tokens, sessions, preferences, reset links, map markers or driver profile (the licence image file is deleted). The wallet, **payments and top-ups are kept**, so the ledger and the Admin's audit stay whole. The original email address can be registered again.
- Rows linked by id with **no** foreign key — role assignments (`model_has_roles`) in the *deleted* case, `sessions`, API tokens, password-reset rows, `activity_log` entries — are not cascaded; the anonymise path removes the first four explicitly, and audit entries are kept for the retention period.
- The user sees a success message and is returned to the login page.
- No further action is required; the user may register a new account if desired.

---

## Exceptions

**E1 – Authentication missing**
The request lacks a valid authenticated session. System redirects to `/login` (handled by middleware) and does not attempt deletion.

**E2 – Database delete failure**
A query exception occurs during `User::destroy()`. It is not caught: the controller produces no friendly message and the user sees the default 500 error page.

**E3 – Unexpected controller error**
Any other exception in the `destroy` method results in a 500 error page.

---

## Known Gaps / Notes

- The deletion is **hard delete**; there is no soft‑delete or “deactivated” state.
- **The wallet must be empty.** There is no refund flow, so deletion is refused while the wallet holds money — the same rule the Admin is held to (UCN_SC_E018 E8). Spend it first.
- **Cascades:** every other foreign key to `users` still cascades; only `payments.paid_by`, `topup_histories.user_id` and `topup_histories.wallet_id` are `RESTRICT`. The application decides delete-or-anonymise itself and does not rely on that restriction, which exists so a future stray `delete()` fails loudly instead of erasing the ledger.
- **An anonymised account is permanent:** it cannot be restored, and the person must register anew (their old email is free again).
- The action is written to the audit trail as a single `deleted_self` entry (email, role, `outcome` = `deleted` or `anonymised`) before the sign-out; the model's own delete event is suppressed so it is not logged twice. Refused requests write nothing.
- The action is immediate and irreversible; there is no confirmation step beyond the browser’s `confirm()` dialog.
- After deletion, the user must register anew if they wish to use the system again.
- The route `/delete-account` is not grouped under any role middleware; it is accessible to any authenticated user regardless of role. A **driver who still holds a vehicle cannot delete their account** — they are told to ask the maintenance manager to unassign it first.
- Success message is flashed via the session and displayed on the subsequent login page load.

---

## Implementation References

| Element | Location |
|---|---|
| Route | `routes/web.php` — `DELETE /delete-account` (`users.delete-account`) (inside `auth` middleware) |
| Controller | `app/Http/Controllers/UserController.php::destroy()` |
| Model | `App\Models\User` |
| Related UCNs | UCN_SC_E001 (Login – for post‑deletion login), UCN_SC_E002 (Create Account – for re‑registration), UCN_SC_E008 (Change Password – shows that account modification is possible) |