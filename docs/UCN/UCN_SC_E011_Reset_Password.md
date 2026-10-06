# UCN_SC_E011 — Reset Password

| Field                 | Value                                                                                                      |
| --------------------- | ---------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E011                                                                                                |
| **Use Case Name**     | Reset Password                                                                                             |
| **Primary Actor**     | Guest (any registered account holder)                                                                       |
| **Secondary Actor**   | System, Email Service (mail transport)                                                                      |
| **Goal**               | Allow a user who has forgotten their password to set a new one and regain access to their account.          |
| **Trigger**           | User clicks the *"Forgot Password"* link on the Login Page (`/login`).                                      |
| **Preconditions**     | 1. User has an account in the system.<br>2. The user knows the email address the account was registered with.<br>3. An email transport is configured (`MAIL_MAILER`). |
| **Supporting Actors** | User record, `password_resets` table, mail log                                        |

> **Corrections vs. the original narrative:** the original precondition *"User has an existing verified account in the system"* was dropped — the flow works for an unverified account too, because password reset is not gated by email verification. The original step 1 also implied the reset view is reached "from the login page"; it is in fact reached **from the emailed link** (`/reset-password/{token}?email=…`).

---

## MAIN FLOW

| #   | Actor's Action                                                        | System Response                                                                                                                                       |
| --- | --------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User clicks *"Forgot Password"* on the Login Page.                    | System renders the **Forgot Password** view (`auth/forgot-password`) with a single *Email* input and a **"Send Reset Link"** button (`GET /forgot-password`). |
| 2   | User enters their email address and clicks **"Send Reset Link"**.    | System validates `email` **required, email** (`POST /forgot-password`).                                                                                 |
| 3   |                                                                        | System asks Laravel's password broker to issue a **time-limited token** (`password_resets` row) and emails a **reset link** containing the token.     |
| 4   | User opens their inbox and clicks the link in the email.              | System validates the token, then renders the **Reset Password** view with *New Password* / *Confirm Password* inputs and a client-side strength meter (`GET /reset-password/{token}?email=…`). |
| 5   | User enters a new password.                                           | The **password strength indicator** updates live (4-segment meter, *"Minimum 8 characters"*). The eye icon reveals the typed password (**A1**).       |
| 6   | User re-enters the same password in the confirm field.                | The **match indicator** updates as the two values agree (**A1**).                                                                                       |
| 7   | User clicks **"Reset Password"**.                                     | System validates `token` **required**, `email` **required, email**, `password` **required, min 8**, `confirm-password` **required, same:password**.          |
| 8   |                                                                        | System **hashes** the new password, stores it, **regenerates the remember token** (`Str::random(60)`) and fires the `PasswordReset` event.             |
| 9   |                                                                        | System **logs the activity** (*"Reset Password"*) and redirects to the **Login Page** with the success message *"Your password has been reset!"* (`lang/en/passwords.php`). |

---

## Alternate Flow

**A1 – Toggle Password Visibility / Watch the Strength Meter**
1. User clicks the eye icon inside a password field.
2. System reveals the typed value in plain text and swaps the icon to *eye-slash*; clicking again re-masks it. Purely client-side.
3. The strength meter re-evaluates on every keystroke and the confirm field shows whether the two values match.

**A2 – Return to Login**
1. User decides not to reset and clicks *"Back to login"* (available on both the request and the reset view).
2. System redirects to `/login`; no token is consumed and no record is changed.

---

## Postconditions

1. The user's password has been changed and stored **hashed**.
2. The remember token has been regenerated, invalidating persistent logins issued before the reset.
3. The `PasswordReset` event has been dispatched, so any registered listener can invalidate other sessions.
4. An activity-log entry has been recorded against the user.
5. The password reset token has been consumed and can no longer be replayed.
6. The user is redirected to the login page and must authenticate with the new password.

---

## Exceptions

**E1 – Invalid Email Format**
The entered address is not a valid email. System rejects the request with *"The email field must be a valid email address."* and sends nothing.

**E2 – Email Not Registered**
The address is well-formed but no account matches. The broker's `INVALID_USER` status is surfaced as a field error on *email*: *"We can't find a user with that email address."*

**E3 – Weak Password**
The new password is shorter than **8 characters**. System rejects the request with *"The password must be at least 8 characters."* and changes nothing.

**E4 – Passwords Do Not Match**
The two fields differ. System rejects the request with *"The password confirmation does not match."* and changes nothing.

**E5 – Expired / Invalid Token**
The link has expired (default 60 minutes) or was already used. The broker's `INVALID_TOKEN` status is surfaced as an error on *email* (*"This password reset token is invalid."*) and the user is returned to the form.

**E6 – Email Service Failure**
The mail transport fails (SMTP down, rejected envelope). The exception is not swallowed by the form: the reset link is never delivered and the user sees no success confirmation, so the token can be re-requested.

---

## Known Gaps / Notes

- **No rate limiting on the reset *request***. `POST /forgot-password` has no throttle, so an attacker can enumerate registered emails by timing the response (the response message differs between `INVALID_USER` and `RESET_LINK_SENT`).
- **No notification to the account owner** that the password was changed — only the in-app activity log records it.
- **The reset view requires the email as a query parameter** alongside the token; a user opening the link from a different client without `?email=` is asked to supply it again.
- No password-history or reuse prevention (see UCN_SC_E008 for the shared policy notes).

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `password.request` (`GET /forgot-password`), `password.email` (`POST /forgot-password`), `password.reset` (`GET /reset-password/{token}`), `password.update` (`POST /reset-password`) |
| Controller | `app/Http/Controllers/UserController.php::forgotPassword()`, `requestPassword()`, `resetPassword()`, `updatePassword()` |
| Password broker | `Illuminate\Auth\Passwords\PasswordBroker` via `Password::sendResetLink()` / `Password::reset()` |
| Language lines | `lang/en/passwords.php` |
| Event | `Illuminate\Auth\Events\PasswordReset` |
| Views | `resources/views/auth/forgot-password.blade.php`, `resources/views/auth/reset-password.blade.php` (strength meter + `togglePassword()`) |
| Middleware | `guest` group |
| Related | UCN_SC_E001 (Login), UCN_SC_E002 (Create Account), UCN_SC_E008 (Change Password) |