# UCN_SC_E008 — Change Password

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E008                                                                                                                                                           |
| **Use Case Name**     | Change Password                                                                                                                                                       |
| **Primary Actor**     | All authenticated users (Commuter, Driver, Driver Manager, Maintenance Manager, Admin) — i.e. everyone except a Guest |
| **Secondary Actor**   | System                                                                                                                                                                |
| **Goal**               | Allow the user to change the password of their own account.                                                                                                           |
| **Trigger**           | User opens Settings and selects the **Security** tab / the *"Change Password"* panel.                                                                                 |
| **Preconditions**     | 1. User is authenticated and logged in.<br>2. User knows their current password.<br>3. The user has a `Driver` profile **only if** they are a driver (unrelated to this use case). |
| **Supporting Actors**   | User records, Session store                                                                                                                                           |

> The use case diverges by role: commuters use the `/profile` endpoint (`UserController::updateProfile`) which updates the password **without** invalidating other sessions, while staff (driver, driver manager, maintenance manager, admin) use the `/settings/password` endpoint (`SettingsController::updatePassword`) which **does** call `Auth::logoutOtherDevices()` to invalidate all other active sessions. The views also differ: commuters see `commuter/settings`; staff see `settings`.

---

## MAIN FLOW

| #   | Actor's Action                                             | System Response                                                                                                                                                    |
| --- | ---------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User navigates to Settings (`/settings`) and opens the **Security** section. | System displays the **Change Password** form: *Current Password*, *New Password*, *Confirm New Password*.                                                          |
| 2   | User enters their current password.                        | *(no system response — client-side field masking only)*                                                                                                           |
| 3   | User enters the new password and confirms it.              | *(no system response — client-side field masking only)*                                                                                                           |
| 4   | User clicks **"Update Password"**.                         | System validates the request server-side: `current_password` **required** and must match the stored hash (`current_password` rule); `password` **required, string, min 8, confirmed**. |
| 5   |                                                                | System **hashes** the new password (`Hash::make`) and updates the user record.                                                                                     |
| 6   |                                                                | For staff roles, System **invalidates all other active sessions/tokens** (`Auth::logoutOtherDevices`), keeping only the current device signed in. (Commuters do not invalidate other sessions.) |
| 7   |                                                                | System displays the success message *"Password changed successfully."*                                                                                             |

> Steps 2–3 have no system response by design; those cells are intentionally left blank rather than filled with "makes the input".

---

## Alternate Flow

**A1 – Staff (non-commuter) account**
1. A driver / driver manager / maintenance manager / admin opens `/settings`.
2. System detects the role and serves the staff layout (`settings`) instead of the commuter layout (`commuter.settings`).
3. The steps are otherwise **identical** — same fields, same endpoint (`PUT /settings/password`), same validation.

> This is a **presentation difference, not a divergent flow**; it is documented here because the original narrative modelled it as an alternate flow.

**A2 – User wants to end other sessions without changing the password**
1. The user presses **"Log out other devices"** (`POST /settings/logout-others`).
2. System calls `logoutOtherDevices()` and confirms *"All other sessions have been terminated."* — this remains available as a standalone action.

---

## Postconditions

1. The user's password has been changed and stored **hashed** (never in plain text).
2. The user record has been updated.
3. The system shows a success message.
4. **All other active sessions/tokens for this user are invalidated**; only the current device remains signed in.
5. Any session opened with the old password can no longer authenticate.

---

## Exceptions

**E1 – Incorrect Current Password**
The entered current password does not match the stored hash. System rejects the request with *"The password is incorrect."* and performs **no** change and **no** session invalidation.

**E2 – Weak New Password**
The new password is missing or shorter than **8 characters**. System rejects the request with the corresponding validation message (*"The password must be at least 8 characters."*) and performs **no** change.

**E3 – Passwords Do Not Match**
`password` and `password_confirmation` differ. System rejects the request with *"The password confirmation does not match."* and performs **no** change.

**E4 – Database / System Error**
The user record update fails. System responds with *"Password could not be changed. Please try again later."* and performs **no** session invalidation.

**E5 – Unauthenticated / Guest**
A guest reaching `/settings` is redirected to the login page by the `auth` + `verified` middleware; no change is possible.

---

## Known Gaps / Notes

- **Length-only password policy.** There is no complexity requirement (no upper/lower/digit/symbol rule), no check against the user's name/email, and **no password-history or reuse prevention**.
- **No rate limiting on password changes.** The endpoint is behind `auth` but has no throttle; combined with a hijacked session this allows repeated attempts at E1.
- **No active-session list.** The user cannot see *which* devices are signed in; sessions can only be terminated blindly.
- **Session invalidation is immediate but not retroactive for tokens already issued to third parties** — Sanctum personal access tokens are unaffected by `logoutOtherDevices()` and must be revoked separately.
- **The user is not asked to re-authenticate** beyond the current-password field, and no re-authentication is required to *view* the security settings.
- No password-change history or notification email is sent to the account address.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `settings.edit` (`GET /settings`), `settings.password` (`PUT /settings/password`), `settings.logout-others` (`POST /settings/logout-others`) |
| Controller | `app/Http/Controllers/SettingsController.php::edit()`, `updatePassword()`, `logoutOtherDevices()` |
| Role-based view | `commuter.settings` for the `commuter` role, otherwise `settings` |
| Views | `resources/views/commuter/settings.blade.php` (Security tab), `resources/views/settings.blade.php` |
| Middleware | `auth`, `verified` |
| Related | UCN_SC_E001 (Login), UCN_SC_E002 (Create Account — initial password) |
**Divergence Note:** Commuter vs staff password change paths diverge at the view layer (`commuter/settings` vs `settings`) but share the same endpoint (`PUT /settings/password`) and validation rules. No divergence in logic.
