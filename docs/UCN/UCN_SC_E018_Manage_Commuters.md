# UCN_SC_E018 — Manage Commuters

| Field                 | Value                                                                                                   |
| --------------------- | --------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E018                                                                                             |
| **Use Case Name**     | Manage Commuters                                                                                        |
| **Primary Actor**     | Admin (`role:admin`)                                                                                    |
| **Secondary Actor**   | System                                                                                                  |
| **Goal**               | Allow the Admin to add, edit, suspend, review and delete the commuter accounts in the system.            |
| **Trigger**           | Admin clicks the **"PUJ Commuters"** item on the sidebar (`GET /commuters`).                              |
| **Preconditions**     | 1. Admin is authenticated and registered to the system.<br>2. Commuter data exists in the database.       |
| **Supporting Actors** | User record, Wallet record, Payment records, Activity log                                               |

> **Corrections vs. the original narrative**
> - The sidebar item is **"PUJ Commuters"**, not *"Commuters"*.
> - The table columns are **`#`, `Commuter`, `Status`, `Registered`, `Balance`, `Actions`**. *Status* shows **Verified / Pending / Suspended**.
> - **A4 (suspend / unsuspend)** and **A5 (view commuter details)** were not implemented in the original narrative and now are, backed by `users.is_suspended` / `users.suspended_at`.
> - **E8 (cannot delete a commuter with a balance)** is now enforced — deletion is refused while the wallet holds money.
> - **A3 was a copy/paste error** — it said *"confirms the **driver** deletion"* in the commuter use case. Corrected here.
> - The delete confirmation reads *"This action is permanent and cannot be undone."*, which is now conditional on E8.
> - The messages in E5–E7 are the framework validation messages (see `E2` in UCN_SC_E008 for the same wording on the commuter self-service password change).

---

## MAIN FLOW

| #   | Actor's Action                                                        | System Response                                                                                                  |
| --- | ---------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| 1   | Admin opens up the dashboard on the map.                              | System displays the dashboard with the admin analytics.                                                             |
| 2   | Admin clicks the **"PUJ Commuters"** button on the sidebar.           | System redirects to the commuter management page (`commuters.index`), showing the registered-account count.       |
| 3   |                                                                            | System displays the commuter table with columns **`#`, `Commuter`, `Status`, `Registered`, `Balance`, `Actions`**, a search box, filter chips (*All / Verified / Pending / Active / Suspended*) and an **"Add Commuter"** button. |

> Steps 4+ are the alternate flows (A1–A5); the main flow is "open the list and read it".

---

## Alternate Flow

**A1 – Add a Commuter**
> UI is modal-based; `create.blade.php` / `edit.blade.php` do not exist.
1. In the list, the Admin clicks the **"Add Commuter"** button.
2. System displays the **Add Commuter** modal: *Email*, *Password*, *Confirm Password*, and a **Mark email as verified** toggle.
3. Admin inputs all required fields and clicks **"Create Commuter"**.
4. System validates `email` **required, email, unique:users** and `password` **required, min 8, confirmed**; creates the User with role `commuter` (verified or pending per the toggle) and confirms *"Commuter account created."*

**A2 – Edit a Commuter**
> Modal-based edit (no `commuters.edit` route or `edit.blade.php` view).
1. In the list, the Admin clicks the edit icon on a commuter row.
2. System displays the **Edit Commuter** modal pre-filled with the stored email and verification state.
3. Admin changes the email, optionally sets a new password and toggles verification, then clicks **"Save Changes"**.
4. System validates `email` **required, email, unique (ignoring the current row)** and `password` **nullable, min 8, confirmed** (blank keeps the current password), updates the record and confirms *"Commuter updated."*

**A3 – Delete a Commuter**
1. In the list, the Admin clicks the delete icon on a commuter row.
2. System displays the *"Delete Commuter?"* confirmation (*"This action is permanent and cannot be undone."*).
3. Admin confirms by clicking **"Delete"**.
4. System deletes the account and confirms *"Commuter removed."* — unless E8 applies.

**A4 – Suspend / Reactivate a Commuter**
1. In the list, the Admin clicks the suspend icon (or *reactivate* on an already-suspended commuter).
2. System displays the confirmation dialog, stating that the account *"will not be able to sign in"* and that its wallet and receipts are kept for auditing.
3. Admin confirms.
4. System sets `is_suspended` / `suspended_at`, and the login flow refuses the account until it is reactivated; the status badge switches to **Suspended**.
5. Reactiving reverses it and confirms that the account may sign in again.

**A5 – View Commuter Details**
1. In the list, the Admin clicks the view icon on a commuter row.
2. System opens the read-only commuter details page (`commuters.show`, `/commuters/{user}`) with the wallet balance, number of fares paid, total spent, registration and verification dates, suspension date, last fare (id and date) and top-up count.

---

## Postconditions

1. A commuter account has been added, edited, suspended, reactivated or removed.
2. The commuter record in the database has been updated (`users`, plus `wallets` on create).
3. The system notifies the Admin with a success message.
4. A **suspended** commuter cannot sign in; their wallet balance and receipts remain intact and auditable.
5. A commuter whose wallet still holds a balance is **never deleted** by this flow.

---

## Exceptions

**E1 – Connection Error**
The list or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Missing Required Commuter Info**
Any required field is empty. System lists the offending fields and performs no write.

**E3 – Database Error**
The insert/update/delete fails at the database level. System cancels the operation and notifies the Admin.

**E4 – Email Already Registered**
The email address already exists in the system. System rejects the request on *email* and performs no write.

**E5 – Invalid Email Format**
The address is missing `@` or a valid domain. System displays the framework message *"The email field must be a valid email address."* and performs no write.

**E6 – Weak Password**
The password is under **8 characters**. System displays *"Password must be at least 8 characters."* and performs no write.

**E7 – Passwords Do Not Match**
The two fields differ. System displays *"The password confirmation does not match."* (rendered under the confirm field) and performs no write.

**E8 – Cannot Delete a Commuter With a Balance**
The commuter's wallet holds more than ₱0. System refuses the deletion with *"Cannot delete commuter with a balance of ₱N. Settle the wallet first."*

**E9 – Self-Referential Action**
The Admin deletes or suspends their own account. System refuses with *"You cannot delete/suspend your own account."*

---

## Known Gaps / Notes

- **Suspension has no expiry and no automatic reactivation**, and there is no reason field or note recorded (unlike the driver suspension, which takes an optional reason).
- **A suspended commuter is not force-logged-out**; an already-open session keeps working until it expires. Only sign-in is blocked.
- **There is no password reset from this screen** — the admin sets a password directly, and the commuter is not notified.
- The details page (A5) exposes fare and top-up totals but offers no link to the individual receipts.
- The commuter list is not paginated and loads every commuter with their fare counts and wallet balances.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `commuters.index`, `commuters.store`, `commuters.show` (`GET /commuters/{user}`), `commuters.suspension` (`PATCH /commuters/{user}/suspension`), `commuters.destroy`, all inside `role:admin` |
| Controller | `app/Http/Controllers/Admin/CommuterController.php::index()`, `store()`, `show()`, `toggleSuspension()`, `destroy()`, `assertCommuter()` |
| Models | `app/Models/User.php` (`isSuspended()`, `suspend()`, `unsuspend()`), `app/Models/Wallet.php`, `app/Models/Payment.php` |
| Views | `resources/views/admin/commuters/index.blade.php`, `resources/views/admin/commuters/show.blade.php` (modal-based UI for add/edit; no separate `create.blade.php` / `edit.blade.php` views) |
| Menu | `config/menu.php` — *PUJ Commuters* (admin) |
| Middleware | `auth`, `verified`, `role:admin` |
| Schema | `2026_10_03_090000_add_suspension_to_users_and_unique_license.php` (`users.is_suspended`, `users.suspended_at`) |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_commuter_with_a_balance_cannot_be_deleted`, `test_a_suspended_commuter_cannot_sign_in`, `test_a_suspended_commuter_can_be_reactivated`, `test_the_commuter_details_page_renders`) |
| Related | UCN_SC_E001 (Login), UCN_SC_E002 (Create Account), UCN_SC_E005/E006/E007 (top-up, pay fare, payment history) |