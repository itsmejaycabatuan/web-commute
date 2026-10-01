# UCN_SC_E002 — Create Account

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E002                                                                                                                                      |
| **Use Case Name**     | Create Account                                                                                                                                   |
| **Primary Actor**     | Guest (unauthenticated user)                                                                                                                     |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow Guest to perform Create Account.                                                                                                           |
| **Trigger**           | Guest initiates Create Account.                                                                                                                  |
| **Preconditions**     | 1. Guest does not currently hold an active, authenticated session in the system.<br>2. The email address is not yet used by an existing account. |
| **Supporting Actors** | System (database), Mail service (verification link)                                                                                              |

---

## MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Guest clicks the "Register" button.                                               | System displays the registration form (email, password, confirm password, Terms & Conditions checkbox).                                                                                                   |
| 2   | Guest enters an email address, a password and confirms it.                        | System masks both password inputs (eye toggle available for each).                                                                                                                                        |
| 3   | Guest checks the "I agree to Terms & Conditions" box and clicks "Create Account". | System validates all inputs **server-side** (`required`, `email`, `unique`, `min:8`, `same`, `accepted`).                                                                                                 |
| 4   |                                                                                   | System creates the user record in the database with an **unverified** email (`email_verified_at = null`), assigns the role **commuter**, creates an empty **wallet** for the user, and logs the Guest in. |
| 5   |                                                                                   | System sends an automated verification email containing a signed verification link to the provided email address.                                                                                         |
| 6   |                                                                                   | System redirects the newly authenticated user to the email-verification notice page with the message *"Account created, please verify your email!"*                                                       |

---

## Alternate Flow

**A1 – User wants to buy a trip but has to create an account**
1. User goes back to the landing page and presses the map button (or opens `/map/guest`).
2. System redirects the user to the map as a **guest** (no login required).
3. User opens the left sidebar and selects the starting and stopping points.
4. System displays the fare calculator on the left side of the screen.
5. System calculates the fare price and distance between the selected points.
6. User presses the **"Buy a Ride"** button.
7. System stores the selected points temporarily and redirects the user to the **registration page**.
8. User registers for an account (MAIN FLOW).

**A2 – Resend Verification Email**
1. After step 6, the user closes the browser window without verifying the email.
2. Later, the user logs in and is redirected to the email-verification page.
3. User presses "Resend verification email"; System re-sends the verification link (throttled to 6 requests/minute).

**A3 – Driver Account**
Driver registration is a **separate flow** at `/register/driver`. This use case covers **commuter accounts only**; the registration page links to the driver flow via the "OR" divider.

---

## Postconditions

1. A new user record exists in the database with an **unverified** status, and a verification email has been sent to the provided address.
2. The user has been assigned the **commuter** role.
3. An empty **wallet** has been created for the user (used later for fare top-ups/payments).
4. The user is now **authenticated** (registration auto-logs the user in) and must verify their email before reaching `/map`.

---

## Exceptions

**E1 – Email Already Registered**
The email entered matches an existing account (`unique:users,email`). System displays *"The email has already been taken."* and the form retains the entered email address.

**E2 – Invalid Email Format**
The email is missing, malformed, or not a valid domain. System displays *"The email field must be a valid email address."*

**E3 – Weak Password**
The password does not meet the minimum security requirement. System displays *"The password field must be at least 8 characters."*

**E4 – Passwords Do Not Match**
The password and confirm-password fields differ. System displays *"The password confirmation does not match."*

**E5 – Terms and Conditions Not Accepted**
The Guest submits without checking the agreement box. System displays *"The terms field must be accepted."*

**E6 – Database Error**
The System fails to insert the user record. System displays *"A system error occurred while creating your account. Please try again later."* and cancels the registration (no account is created).

**E7 – Too Many Attempts (Rate Limiting)**
The Guest submits more than **6 registration attempts per minute**. System blocks the request (HTTP 429).

---

## Known Gaps / Notes

- The **Terms of Service** and **Privacy Policy** links in the registration form are currently placeholders (`href="#"`); no policy pages exist yet.
- Error handling currently wraps the user insert only; a failure in role assignment or wallet creation surfaces a generic 500 page.
- `UserController::emailVerification()` (custom mail + `activate` view) is **dead code** — no route points to it. The live flow uses Laravel's standard signed verification link at `/email/verify/{id}/{hash}`.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /register`, `POST /register` (`throttle:6,1`, inside `guest` middleware) |
| Controller | `app/Http/Controllers/UserController.php::register()` |
| Views | `resources/views/auth/register.blade.php`, `resources/views/auth/verify-email.blade.php` |
| Verification link handling | `routes/web.php` → `verification.verify` (signed URL) |
| "Buy a Ride" → register redirect | `resources/views/map.blade.php` — fare form `data-guest-register` + submit guard |