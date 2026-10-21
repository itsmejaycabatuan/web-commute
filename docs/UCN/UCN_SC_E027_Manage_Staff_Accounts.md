# UCN_SC_E027 — Manage Staff Accounts

| Field                 | Value                                                                                                   |
| --------------------- | --------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E027                                                                                             |
| **Use Case Name**     | Manage Staff Accounts                                                                                   |
| **Primary Actor**     | Admin (`role:admin`)                                                                                    |
| **Secondary Actor**   | System                                                                                                  |
| **Goal**               | Allow the Admin to create, view, edit, suspend and delete the back-office staff accounts: **Driver Managers** and **Maintenance Managers**. |
| **Trigger**           | Admin clicks the **"Staff"** item on the sidebar (`GET /staff`).                                        |
| **Preconditions**     | 1. Admin is authenticated and registered to the system.                                                  |
| **Supporting Actors** | User record, role assignment (Spatie permission), Activity log                                          |

> **Scope.** Only accounts holding the `driver_manager` or `maintenance_manager` role are reachable here. Admin accounts, commuters (UCN_SC_E018) and drivers (UCN_SC_E012) have their own screens; asking this controller to act on any of them answers 404.

---

## MAIN FLOW

| #   | Actor's Action                                              | System Response                                                                                                  |
| --- | ------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ |
| 1   | Admin clicks **"Staff"** on the sidebar.                    | System verifies the `admin` role and shows the staff list, **10 accounts per page**, with the count of staff and of suspended accounts. |
| 2   | Admin may search by e-mail and filter by **role** and **status** (*Active / Suspended*), then press **Filter**. | System narrows the list; the filters survive paging. **Clear** restores the full list. |

---

## Alternate Flow

**A1 – Add a Staff Account**
1. Admin clicks **"Add Staff"**. System displays the modal: *Email, Role, Password, Confirm Password*.
2. The modal checks e-mail format, password length and confirmation while typing. Admin submits. System validates `email` **required, email, unique:users**, `role` **in `driver_manager, maintenance_manager`**, `password` **required, min 8, confirmed**.
3. System creates the user (e-mail marked verified — staff are created by an admin, not by e-mail sign-up), assigns the role, writes a `created` audit-trail entry (with the role) and confirms *"Staff account created."*

**A2 – Edit a Staff Account**
1. Admin clicks the edit icon. The modal opens pre-filled with the e-mail and role.
2. Admin changes the e-mail and/or role and optionally sets a new password (blank keeps the current one).
3. System validates as in A1 (e-mail unique ignoring the current row), updates the user, **replaces the role** and confirms *"Staff account updated."*

**A3 – Suspend / Reactivate a Staff Account**
1. Admin clicks the suspend icon (or *reactivate*) and confirms.
2. System sets or clears `is_suspended`; a suspended account is **signed out on its next request** (see UCN_SC_E012 notes on `account.active`) and cannot sign in. Confirms *"… has been suspended."* / *"… has been reactivated."*

**A4 – Delete a Staff Account**
1. Admin clicks the delete icon and confirms *"Delete staff account?"*.
2. System deletes the user, writes a `deleted` audit-trail entry (with the email and role) and confirms *"Staff account removed."* Should the account somehow have payments or top-ups it is anonymised instead, and the entry reads `anonymised`.

---

## Exceptions

**E1 – Validation Error**
Any rule above fails. The **modal stays open**, each message is shown **under its own input** and an error toast reads *"Please correct the highlighted fields and try again."* Nothing is stored.

**E2 – Own Account**
The Admin tries to suspend or delete their own account. System refuses with *"You cannot suspend/delete your own account."*

**E3 – Not a Staff Account**
The target user holds neither staff role. System answers 404.

**E4 – Database Error**
The write fails. System cancels, logs the exception and shows *"A database error occurred. …"*

**E5 – Not an Admin**
Any other role (or a guest) is refused (403 / redirect to login).

---

## Postconditions

1. The staff account has been created, changed, suspended or removed, and the list shows the new state after the page refreshes.
2. A success toast confirms the action.

---

## Known Gaps / Notes

- Admin accounts cannot be created or removed from this screen.
- Deleting a staff account is a hard delete; there is no undo.
- A staff member holds exactly one of the two roles; changing the role removes the old one.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `staff.index`, `staff.store`, `staff.update`, `staff.suspension`, `staff.destroy`, inside `role:admin` |
| Controller | `app/Http/Controllers/Admin/StaffController.php` |
| View | `resources/views/admin/staff/index.blade.php` |
| Menu | `config/menu.php` — *Staff* (admin) |
| Tests | `tests/Feature/BugFixRegressionTest.php` (staff tests), `tests/Feature/RouteProtectionTest.php` |
| Related | UCN_SC_E012 (Manage Drivers), UCN_SC_E018 (Manage Commuters) |
