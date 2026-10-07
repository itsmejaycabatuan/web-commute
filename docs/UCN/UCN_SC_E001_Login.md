# UCN_SC_E001 — Login

| Field                          | Value                                                                                                                   |
| ------------------------------ | ----------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**                | UCN SC E001                                                                                                             |
| **Use Case Name**              | Login                                                                                                                   |
| **Primary Actor**              | Guest (unauthenticated user)                                                                                            |
| **Secondary Actor**            | System                                                                                                                  |
| **Goal**                       | Successfully authenticate the user and grant access to the system.                                                      |
| **Trigger**                    | Guest attempts to log in.                                                                                               |
| **Preconditions**              | 1. Guest must have a registered account.<br>2. System's auth service is online.<br>3. Guest is currently not logged in. |
| **Actors / Supporting Actors** | System (Laravel session guard + `users` table), Mail service                                                            |

---

## MAIN FLOW

| #   | Actor's Action                           | System Response                                                                                                                                                                |
| --- | ---------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | Guest clicks the "Log in" button.        | System displays the login form (email + password).                                                                                                                             |
| 2   | Guest enters email address and password. | System masks the password as it is typed (eye toggle available). The email field is validated for format on the client (`type="email"`).                                       |
| 3   | Guest clicks the "Log in" button.        | System validates the credentials server-side (`required`, `email`, `required`).                                                                                                |
| 4   |                                          | System regenerates the session ID (session-fixation guard) and creates a secure session token.                                                                                 |
| 5   |                                          | System checks email verification status.<br>• Verified → System redirects the guest to the map as an authenticated user.<br>• Not verified → see **E2 – Email Not Confirmed**. |
| 6   |                                          | System shows a "Logged in Successfully!" toast and records a `Log In` activity-log entry.                                                                                      |

---

## Alternate Flow

**A1 – "Remember me" functionality**
1. At step 2, Guest checks the "remember me" box.
2. At step 4, the System creates a persistent **remember-me cookie** (long-lived) instead of only a session cookie, so the Guest is auto-logged in on the next visit.

**A2 – Guest navigates directly to a protected page**
1. Guest opens any protected page (e.g. `/dashboard`, `/payment`).
2. System redirects the Guest to the login page.
3. After successful login the Guest continues to the originally requested page.

---

## Postconditions

1. Guest is now authenticated and holds the **commuter** role (role is assigned at registration, UCN_SC_E002).
2. A session token is generated and the session ID is regenerated.
3. A success toast is displayed and a `Log In` activity-log entry is created (the System does **not** send an email notification).
4. System redirects the commuter to the map (`/map`, protected by `auth` + `verified` middleware).

---

## Exceptions

**E7 – Account Suspended**
The user account is suspended. System cancels the login and displays *"This account has been suspended. Please contact the administrator."*

**E1 – Database / Auth Service Error**
System fails to query or authenticate against the database. System cancels the login and displays *"Login is unavailable right now. Please try again later."* No partial session is created.

**E2 – Email Not Confirmed**
Account exists but `email_verified_at` is null. System redirects the Guest to the email-verification notice page, where a **Resend Verification** action is available (throttled to 6 requests/minute). The session is **still regenerated** before this redirect.

**E3 – Invalid Credentials (covers wrong email *and* wrong password)**
The provided email and/or password does not match. System displays the single generic message *"The provided credentials do not match our records."* and cancels the login.
> **Design note:** the System intentionally does **not** distinguish "email not found" from "password incorrect" in order to prevent account enumeration.

**E4 – Malformed / Empty Fields**
Guest submits with an empty field or an invalid email format. System displays field-level validation errors and does not query the database.

**E5 – Too Many Attempts (Rate Limiting)**
Guest submits more than **5 login attempts per minute**. System blocks the request (HTTP 429) and displays the standard throttle message; the attempt is aborted.

**E6 – Already Authenticated**
Guest is already logged in when attempting to log in. The `guest` middleware group rejects the request and redirects to the map.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /login`, `POST /login` (`throttle:5,1`, inside `guest` middleware) |
| Controller | `app/Http/Controllers/UserController.php::login()` |
| Views | `resources/views/auth/login.blade.php` |
| Redirect for unauthenticated access | `app/Http/Middleware/Authenticate.php::redirectTo()` |
| Session lifetime | `config/session.php` → `SESSION_LIFETIME` (default 120 min) |
| Verification routes | `routes/web.php` → `verification.notice`, `verification.send` (`throttle:6.1`), `verification.verify` |