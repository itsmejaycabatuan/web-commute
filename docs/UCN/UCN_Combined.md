# Use Case Narratives (UCN) — Web Commute (Combined)

Documentation of the implemented behaviour of the system's core guest, commuter, staff and driver journeys.

| Use Case ID | Name | Actor | Section |
|---|---|---|---|
| UCN_SC_E001 | Login | Guest | [Login](#ucn_sc_e001--login) |
| UCN_SC_E002 | Create Account | Guest | [Create Account](#ucn_sc_e002--create-account) |
| UCN_SC_E003 | Track PUJ | Guest | [Track PUJ](#ucn_sc_e003--track-puj) |
| UCN_SC_E004 | Plan Trip | Guest / Commuter | [Plan Trip](#ucn_sc_e004--plan-trip) |
| UCN_SC_E005 | Topup Balance | Commuter | [Topup Balance](#ucn_sc_e005--topup-balance) |
| UCN_SC_E006 | Pay Fare | Commuter | [Pay Fare](#ucn_sc_e006--pay-fare) |
| UCN_SC_E007 | View Payment History | Commuter | [View Payment History](#ucn_sc_e007--view-payment-history) |
| UCN_SC_E008 | Change Password | All users except Guest | [Change Password](#ucn_sc_e008--change-password) |
| UCN_SC_E009 | Clock In | Driver | [Clock In](#ucn_sc_e009--clock-in) |
| UCN_SC_E010 | Clock Out | Driver | [Clock Out](#ucn_sc_e010--clock-out) |
| UCN_SC_E011 | Reset Password | Guest | [Reset Password](#ucn_sc_e011--reset-password) |
| UCN_SC_E012 | Manage Drivers | Driver Manager / Admin | [Manage Drivers](#ucn_sc_e012--manage-drivers) |
| UCN_SC_E013 | Manage Timekeeping | Driver Manager | [Manage Timekeeping](#ucn_sc_e013--manage-timekeeping) |
| UCN_SC_E014 | Manage Violations | Driver Manager | [Manage Violations](#ucn_sc_e014--manage-violations) |
| UCN_SC_E015 | Manage Vehicles | Maintenance Manager | [Manage Vehicles](#ucn_sc_e015--manage-vehicles) |
| UCN_SC_E016 | Manage Maintenance Schedule | Maintenance Manager | [Manage Maintenance Schedule](#ucn_sc_e016--manage-maintenance-schedule) |
| UCN_SC_E017 | Manage Maintenance Tasks | Maintenance Manager | [Manage Maintenance Tasks](#ucn_sc_e017--manage-maintenance-tasks) |
| UCN_SC_E018 | Manage Commuters | Admin | [Manage Commuters](#ucn_sc_e018--manage-commuters) |
| UCN_SC_E019 | Manage Fare Rates | Admin | [Manage Fare Rates](#ucn_sc_e019--manage-fare-rates) |
| UCN_SC_E020 | View System Reports | Admin | [View System Reports](#ucn_sc_e020--view-system-reports) |
| UCN_SC_E021 | Share Vehicle Location (Driver Broadcast) | Driver | [Share Vehicle Location (Driver Broadcast)](#ucn_sc_e021--share-vehicle-location-driver-broadcast) |
| UCN_SC_E022 | Driver Registration | Guest | [Driver Registration](#ucn_sc_e022--driver-registration) |
| UCN_SC_E023 | Driver Dashboard Views | Driver | [Driver Dashboard Views](#ucn_sc_e023--driver-dashboard-views) |
| UCN_SC_E024 | Admin Violation Code Management | Driver Manager | [Admin Violation Code Management](#ucn_sc_e024--admin-violation-code-management) |
| UCN_SC_E025 | Profile and Dashboard Views | All authenticated roles | [Profile and Dashboard Views](#ucn_sc_e025--profile-and-dashboard-views) |
| UCN_SC_E026 | Account Deletion | Authenticated user | [Account Deletion](#ucn_sc_e026--account-deletion) |
| UCN_SC_E027 | Manage Staff Accounts | Admin | [Manage Staff Accounts](#ucn_sc_e027--manage-staff-accounts) |
| UCN_SC_E028 | View Activity Log | Admin | [View Activity Log](#ucn_sc_e028--view-activity-log) |

> **Numbering note.** E011–E020 were reconciled against the implementation. Two sheets previously
> carried a duplicate identifier — *Manage Violations* shared `UCN_SC_E013` with *Manage Timekeeping*
> and *Manage Maintenance Schedule* shared `UCN_SC_E015` with *Manage Vehicles*. Violations are now
> **E014** and the maintenance schedule **E016**.

## Terminology

| Term | Meaning |
|---|---|
| **Guest** | An *unauthenticated* visitor browsing the public map (`/map/guest`). |
| **Commuter** | An authenticated user holding the `commuter` role. A commuter starts as a guest and becomes a commuter by registering (UCN_SC_E002) and verifying their email. |
| **Driver** | An authenticated user holding the `driver` role with an approved `Driver` record and a staff-assigned vehicle. Drivers clock in/out from the map sidebar (UCN_SC_E009 / E010). |
| **Wallet** | The commuter's stored balance, created at registration. Funded via top-up (UCN_SC_E005) and debited when a fare is paid by wallet (UCN_SC_E006). |
| **Payment record** | An immutable ledger row for one paid fare (transaction id `#SC-XXXXXXXX`), readable by the commuter who paid it and, through the same receipt route, by an Admin auditing the ledger. A `pending` payment is closed as `cancelled` after 12 hours. Carries a lifecycle status: `pending` → `paid` / `failed` / `cancelled`. |
| **Gateway** | Optional PayMongo integration for GCash/Maya fares. Disabled by default; enabled with sandbox test keys — see `docs/PAYMONGO_SETUP.md`. |
| **Timekeeping record** | One row per driver per day, holding `time_in`, `time_out`, `hours_worked` and `overtime_hours`. |
| **Suspension** | A flag that locks an account out of signing in — and signs out a session that is already open on its next request — without deleting it. Commuters carry `users.is_suspended` (UCN_SC_E018 A4); drivers carry `drivers.is_suspended` (UCN_SC_E012 A5), which additionally blocks clock-in and location broadcast and releases any assigned vehicle. |
| **Vehicle status** | `active` (in service, the only status that may carry a driver and broadcast a location), `inactive`, `maintenance` (out of service, off the live map) or `disposed` (retired, kept for auditing) — UCN_SC_E015. |
| **Preventive maintenance** | The maintenance record for a (vehicle, task) pair: the last service's date, odometer and cost, and the interval used to project the next due date/odometer. UCN_SC_E016. |
| **PUJ** | Public Utility Vehicle. On the map, a PUJ marker is a **roadworthy** vehicle (status `active`/`inactive`) with a location reported in the last 5 minutes, coloured by its driver's availability (green available, red unavailable). |
| **Privacy radius** | 200 m. Guest/commuter coordinates are randomly obfuscated within this radius. |

## Document conventions

- Each use case follows the classic UCN layout: header, preconditions, **Main Flow**, **Alternate Flow**, **Postconditions**, **Exceptions**, then implementation references.
- Every behaviour described is traceable to the code path listed in the *Implementation References* table.
- Documents reconciled against the implementation (E011–E020) open with a **"Corrections vs. the original narrative"** note listing what the narrative said, what the system actually does, and why the narrative was changed rather than the code.
- Items marked **Known Gaps / Notes** describe deliberate limitations or behaviours that do **not** yet match the original narrative — they are candidates for the next iteration, not bugs.
- **Messages (all pages except the map banners):** success, error and warning messages are shown as toasts (`<x-toast />`). In add / edit / delete modals a validation error keeps the modal open and shows the message under its own input; on success the modal closes, the page refreshes and a toast confirms. Lists longer than 10 rows are paged 10 per page. Where the documents below say a "flash" or "banner", read "toast".
- Error/warning UI on the **map** uses a single status banner (`#map-alert`) with the priority stack:
  `offline` → `map-service` → `routing-service` → `location-denied` → `no-puj`.

---

## UCN_SC_E001 — Login

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

### MAIN FLOW

| #   | Actor's Action                           | System Response                                                                                                                                                                |
| --- | ---------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | Guest clicks the "Log in" button.        | System displays the login form (email + password).                                                                                                                             |
| 2   | Guest enters email address and password. | System masks the password as it is typed (eye toggle available). The email field is validated for format on the client (`type="email"`).                                       |
| 3   | Guest clicks the "Log in" button.        | System validates the credentials server-side (`required`, `email`, `required`).                                                                                                |
| 4   |                                          | System regenerates the session ID (session-fixation guard) and creates a secure session token.                                                                                 |
| 5   |                                          | System checks email verification status.<br>• Verified → System redirects the guest to the map as an authenticated user.<br>• Not verified → see **E2 – Email Not Confirmed**. |
| 6   |                                          | System shows a "Logged in Successfully!" toast and records a `login` audit-trail entry (who, IP address, device). A failed attempt (`login_failed`, with the email tried) and a suspended account's attempt (`login_blocked`) are recorded too. A **driver** whose licence is expiring (30 days or fewer) or expired also gets a **warning toast at every sign-in** — *"Your driver's license expires in N days (date)…"* or *"…expired on date. Contact the administrator to renew it."*                                                                                      |

---

### Alternate Flow

**A1 – "Remember me" functionality**
1. At step 2, Guest checks the "remember me" box.
2. At step 4, the System creates a persistent **remember-me cookie** (long-lived) instead of only a session cookie, so the Guest is auto-logged in on the next visit.

**A2 – Guest navigates directly to a protected page**
1. Guest opens any protected page (e.g. `/dashboard`, `/payment`).
2. System redirects the Guest to the login page.
3. After successful login the System always redirects to the map (`/map`) — it does **not** resume the originally requested page, so the Guest must navigate back manually.

---

### Postconditions

1. Guest is now authenticated and holds the **commuter** role (role is assigned at registration, UCN_SC_E002).
2. A session token is generated and the session ID is regenerated.
3. A success toast is displayed and a `login` audit-trail entry is created with the user, IP address and device (the System does **not** send an email notification).
4. System redirects the commuter to the map (`/map`, protected by `auth` + `verified` middleware).

---

### Exceptions

**E7 – Account Suspended**
The user account is suspended. System cancels the login and displays *"This account has been suspended. Please contact the administrator."*

**E1 – Database / Auth Service Error**
System fails to query or authenticate against the database. System cancels the login and displays *"Login is unavailable right now. Please try again later."* No partial session is created.

**E2 – Email Not Confirmed**
Account exists but `email_verified_at` is null. System redirects the Guest to the email-verification notice page, where a **Resend link and code** action is available and the 6-digit code from the email can be entered instead of using the link (throttled to 6 requests/minute). The session is **still regenerated** before this redirect.

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

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /login`, `POST /login` (`throttle:5,1`, inside `guest` middleware) |
| Controller | `app/Http/Controllers/UserController.php::login()` |
| Views | `resources/views/auth/login.blade.php` |
| Redirect for unauthenticated access | `app/Http/Middleware/Authenticate.php::redirectTo()` |
| Session lifetime | `config/session.php` → `SESSION_LIFETIME` (default 120 min) |
| Verification routes | `routes/web.php` → `verification.notice`, `verification.send` (`throttle:6.1`), `verification.verify` |

---

## UCN_SC_E002 — Create Account

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

### MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Guest clicks the "Register" button.                                               | System displays the registration form (email, password, confirm password, Terms & Conditions checkbox).                                                                                                   |
| 2   | Guest enters an email address, a password and confirms it.                        | System masks both password inputs (eye toggle available for each).                                                                                                                                        |
| 3   | Guest checks the "I agree to Terms & Conditions" box and clicks "Create Account". | System validates all inputs **server-side** (`required`, `email`, `unique`, `min:8`, `same`, `accepted`).                                                                                                 |
| 4   |                                                                                   | System creates the user record in the database with an **unverified** email (`email_verified_at = null`), assigns the role **commuter**, creates an empty **wallet** for the user, and logs the Guest in. |
| 5   |                                                                                   | System sends an automated verification email containing a signed verification link **and a 6-digit code** to the provided email address.                                                                                         |
| 6   |                                                                                   | System redirects the newly authenticated user to the email-verification notice page with the message *"Account created, please verify your email!"*                                                       |

---

### Alternate Flow

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
3. User presses "Resend link and code"; System re-sends the verification link with a **new** 6-digit code (throttled to 6 requests/minute). The previous code stops working.

**A2b – Verify with the 6-digit code instead of the link**
1. The user cannot use the link (different device, mail client strips it) and instead types the 6-digit code from the same email into the **Verification code** box on the email-verification page, then presses **Verify with code**. Spaces in the code are ignored.
2. System checks the code (`POST /email/verify/code`, throttled to 10 requests/minute): it must be 6 digits, belong to the signed-in user, be unexpired (60 minutes, like the link) and match.
3. System marks the email verified, discards the code, records an `email_verified` audit-trail entry (`method: code`) and redirects to the map with *"Email verified!"*. Verifying by the link also discards the code.

**A3 – Driver Account**
1. On the registration page, the user clicks the driver registration link below the "OR" divider.
2. System redirects the user to the driver registration page (`/register/driver`).
3. User completes driver registration as described in UCN_SC_E022. No commuter account is created, so this use case ends here.

---

### Postconditions

1. A new user record exists in the database with an **unverified** status, and a verification email has been sent to the provided address.
2. The user has been assigned the **commuter** role.
3. An empty **wallet** has been created for the user (used later for fare top-ups/payments).
4. The user is now **authenticated** (registration auto-logs the user in) and must verify their email before reaching `/map`.

---

### Exceptions

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

**E8 – Wrong, Malformed, Expired or Used-Up Verification Code**
Entering a code that is not 6 digits shows *"The code must be 6 digits."* (or *"Enter the 6-digit code from the email."* when empty) and costs no attempt. A well-formed but wrong code shows *"That code is incorrect."*; the **5th** wrong guess burns the code (*"Too many incorrect attempts. Request a new code."*). An old code shows *"That code has expired. Request a new one."* and a user with no code shows *"There is no active code. Request a new one."* — in each case the email stays unverified and the user can resend.

---

### Known Gaps / Notes

- **Vehicles that lose a driver get a replacement.** When a driver on a vehicle is suspended, or their licence expires (the hourly `vehicles:cover-vacancies` job then takes them off it), the seat is filled with the best free driver automatically; vehicles still waiting for one are listed on the Maintenance dashboard. Manually assigning or unassigning drivers on this page is never treated as a vacancy.

- **A suspended driver's seat is refilled automatically.** Suspending a driver (by an Admin, or by the inactivity job) takes them off their vehicle and opens a vacancy; the system assigns the best free driver, rebuilds the duty roster if the vehicle is shared, and emails the replacement and the Maintenance Managers. Nobody free: the vacancy stays open and is reported daily. See `docs/AUTOMATED_FEATURES.md`.

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- The **Terms of Service** and **Privacy Policy** links in the registration form are currently placeholders (`href="#"`); no policy pages exist yet.
- Error handling currently wraps the user insert only; a failure in role assignment or wallet creation surfaces a generic 500 page.
- `UserController::emailVerification()` (custom mail + `activate` view) is **dead code** — no route points to it. The live flow uses Laravel's standard signed verification link at `/email/verify/{id}/{hash}`.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /register`, `POST /register` (`throttle:6,1`, inside `guest` middleware) |
| Controller | `app/Http/Controllers/UserController.php::register()` |
| Views | `resources/views/auth/register.blade.php`, `resources/views/auth/verify-email.blade.php` |
| Verification link handling | `routes/web.php` → `verification.verify` (signed URL) |
| Verification code | `EmailVerificationCodeController::verify` (`verification.code`), `EmailVerificationCode` model (table `email_verification_codes`, hashed, 60 min, 5 attempts), `VerifyEmailWithCode` notification, `RecordEmailVerified` listener |
| "Buy a Ride" → register redirect | `resources/views/map.blade.php` — fare form `data-guest-register` + submit guard |

---

## UCN_SC_E003 — Track PUJ

| Field                 | Value                                                                                                                                                                                                                                   |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E003                                                                                                                                                                                                                             |
| **Use Case Name**     | Track PUJ                                                                                                                                                                                                                               |
| **Primary Actor**     | Guest (unauthenticated visitor)                                                                                                                                                                                                         |
| **Secondary Actor**   | System                                                                                                                                                                                                                                  |
| **Goal**              | Allow Guest to view PUJ locations on the map.                                                                                                                                                                                           |
| **Trigger**           | Guest redirects to the map.                                                                                                                                                                                                             |
| **Preconditions**     | 1. The mapping/tile service is reachable.<br>2. The Guest's device has a network connection.<br>3. *(Optional)* Guest GPS is enabled and location permission is granted — if not granted the System runs in **degraded mode** (see E2). |
| **Supporting Actors** | System, External Mapping Service (MapLibre GL + OpenFreeMap tiles), Push channel (Pusher/Echo)                                                                                                                                          |

---

### MAIN FLOW

| #   | Actor's Action                                 | System Response                                                                                                                                                          |
| --- | ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | Guest opens the map.                           | System requests device location (when permission is granted) and displays the interactive map with navigation, pitch and locate (📍) controls.                           |
| 2   |                                                | System centres the map on the Guest's current location (zoom 15) as soon as the first position fix arrives. Until then the map opens at the default service-area centre. |
| 3   |                                                | System fetches real-time PUJ data (initial fetch + websocket push + polling fallback) and plots the active PUJ markers on the map, coloured by the driver's availability: **green** = available, **red** = unavailable, blue = unknown. Toggling the status updates the marker and its popup within a few seconds.                                       |
| 4   | Guest clicks a specific PUJ marker on the map. | System displays an info popup for the PUJ.                                                                                                                               |
| 5   |                                                | **Info popup displays:** PUJ plate/route, approximate location (privacy-obfuscated, ~200 m radius), the **driver's status** (*Available* / *Unavailable*, from the driver's own availability toggle) and ETA to the Guest's location.                     |

**Marker / ETA detail**
- Active PUJs are those with a location reported within the last **5 minutes**.
- Coordinates are randomly obfuscated within a **200 m radius** before being sent to guests/commuters (`App\Helpers\LocationPrivacy`).
- ETA is an estimate: straight-line (Haversine) distance minus half the privacy radius, at an average speed of **20 kph**, plus a ±privacy buffer — displayed as a range or "~N min".
- ETA badges appear on markers within **5 km**; the floating "Nearest PUJ" indicator within **10 km**.

---

### Alternate Flow

**A1 – Guest wants to check the nearest PUJ in their area**
1. Once the Guest's location is known, the System automatically selects the PUJ nearest to the Guest and shows a floating **"Nearest PUJ"** indicator at the bottom of the map (ETA + distance).
2. Guest taps the indicator.
3. System flies the map to that PUJ and automatically opens its info popup.

**A2 – Guest wants to centre the map manually**
1. Guest taps the 📍 locate control.
2. System centres the map and starts tracking position updates to refresh ETA badges and popups.

---

### Postconditions

1. Guests are able to see the nearest PUJ in their area (floating indicator, ≤ 10 km, requires location permission).
2. Guests now have information about the PUJs in their area (markers, ETAs, popup details).

---

### Exceptions

**E1 – No PUJ Markers**
There are no active PUJs (no driver location reported in the last 5 minutes), so there are no markers on the map. System displays a status banner: **"No PUJs available — No PUJ drivers are clocked in right now, so there are no markers on the map."** The banner clears automatically as soon as a PUJ appears.

**E2 – Location Permission Denied / Unavailable**
Guest refuses or cannot give location access. System cannot centre the map or calculate ETA. System displays a status banner: **"Location access needed — Enable location access to see the map centred on you and to get PUJ ETAs."** The map, markers and popups remain usable; only centring and ETA are degraded. The banner clears automatically once a position fix is received.

**E3 – External Map Service Down**
The mapping API fails to load or repeated tile/style errors occur. System displays a status banner: **"Map service unavailable — The mapping service failed to load. Please refresh the page or try again later."** The banner clears automatically when the style loads successfully.

**E4 – No Internet Connection**
Guest's device is offline. System cannot fetch the map or PUJ coordinates. System displays a status banner: **"No internet connection — You are offline. The map and PUJ locations cannot be refreshed until the connection returns."** Marker and vehicle polling are suspended; they resume automatically when the connection returns.

- `routing-service` added to banner priority stack.
**Status banner priority** (only the highest-priority banner is shown):
`offline` → `map-service` → `routing-service` → `location-denied` → `no-puj`

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- `/api/markers` (developer/mock markers) returns an empty array outside the `local` environment; in production the map relies solely on live driver broadcasts from `/track/vehicles/active`.
- "Active PUJ" is determined by **location freshness (5 minutes)**, not by the driver's clock-in (timekeeping) status. Align either the wording of E1 or the backend rule.
- A1 is a **passive, automatic** indicator limited to 10 km; there is no explicit "nearest PUJ" button.
- Guest guests are limited to **3 map interactions per day** (fare/route lookups) tracked in `localStorage`.

---

### Implementation References

| Element | Location |
|---|---|
| Guest map route | `routes/web.php` → `GET /map/guest` (`guest` middleware) |
| Authenticated map route | `routes/web.php` → `GET /map` (`auth`, `verified`) |
| Map view (all map logic) | `resources/views/map.blade.php` |
| Vehicle tracking helper | `resources/js/map-tracker.js` |
| Marker data API | `routes/api.php` → `GET /api/markers` |
| Live vehicle data API | `routes/web.php` → `GET /track/vehicles/active` |
| Location broadcast | `routes/web.php` → `POST /track/vehicle/broadcast` — the **write** side of live tracking is documented separately in UCN_SC_E021 |
| Live channel | Pusher/Echo channel `vehicle-locations`, event `.vehicle-location-updated` |
| Coordinate obfuscation | `app/Helpers/LocationPrivacy.php` |
| Active-vehicle rule | `app/Http/Controllers/VehicleTrackingController.php::getActiveVehicles()` |

---

## UCN_SC_E004 — Plan Trip

| Field                 | Value                                                                                                                                                                             |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E004                                                                                                                                                                       |
| **Use Case Name**     | Plan Trip                                                                                                                                                                         |
| **Primary Actor**     | Guest / Commuter                                                                                                                                                                  |
| **Secondary Actor**   | System                                                                                                                                                                            |
| **Goal**              | Allow Guest to see the distance and fare prices for their commute.                                                                                                                |
| **Trigger**           | Guest opens the sidebar on the map.                                                                                                                                               |
| **Preconditions**     | 1. System routing engine is operational.<br>2. User has an active internet connection.<br>3. Both a start point and a destination have been selected (by map click or by search). |
| **Supporting Actors** | External Geocoding Service (Photon), External Routing Service (OSRM), Fare rate table (`FareRate`)                                                                                |

---

### MAIN FLOW

| #   | Actor's Action                                         | System Response                                                                                                                                                                                                     |
| --- | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User opens the sidebar on the map page.                | System displays a fare calculator with input fields for origin and destination, plus distance and fare output fields.                                                                                               |
| 2   | User selects Origin and destination (via map or text). | System places **Point A** and **Point B** markers on the map. Text entries are resolved by the geocoding service (Photon), restricted to the service-area bounding box; a tap on the map places the point directly. |
| 3   |                                                        | System requests a driving route from the routing service (OSRM) and draws the recommended route path on the map, then fits the map bounds to it.                                                                    |
| 4   |                                                        | System displays the total **distance (km)** and the **fare costs** (regular and discounted), looked up from the active fare-rate table by distance tier.                                                            |

**Fare calculation detail**
- Fare is looked up by finding the **highest distance tier ≤ the calculated distance** (`getFareFromDB()`); values are rounded up to whole pesos.
- The fare table is passed from the server on page render (`FareRate` for the latest `Fare`), so no fare data is fetched from the client.

---

### Alternate Flow

**A1 – Guest selects points by tapping the map**
1. Guest taps **"Set pick-up"** then clicks a point on the map (or vice versa for the destination).
2. System places the marker and reverse-geocodes the point to a readable address.

**A2 – Guest searches for a place by name**
1. Guest types at least 2 characters into the origin or destination field.
2. System queries the geocoding service and lists up to 6 matches inside the service area.
3. Guest selects a result; System places the marker and calculates the trip.

**A3 – Guest changes their mind**
1. Guest presses **"Reset Route"**.
2. System clears both points, the drawn route, and the distance/fare fields.

**A4 – Guest wants to book the trip**
1. Guest presses **"Buy a Ride"** with both points selected.
2. **Guest (unauthenticated):** System stores the chosen points and redirects to the registration page (see UCN_SC_E002 A1).
3. **Commuter:** System redirects to the payment page with the distance and fare (see E005 / payment flow).

**A5 – Guest exceeds the daily limit**
1. Guest is limited to **3 fare calculations per day** (tracked locally per device).
2. On the 4th attempt the System blocks the calculation and shows the daily-limit modal.

---

### Postconditions

1. Users now have the distance and fare price information for their trip.
2. The selected origin, destination and drawn route are visible on the map.

---

### Exceptions

**E1 – Fare Prices Unavailable**
No fare rates exist for the current fare period. The System still displays the calculated distance, but the fare price is shown as **0**.

**E2 – Location / Place Not Found**
- *No search match:* the System shows **"No results found"** in the dropdown and a banner: *"Location not found — That place could not be found. Try a different name, or tap a point directly on the map."* The banner clears after ~6 seconds.
- *Reverse-geocode failure:* the System falls back to displaying the raw coordinates (`lat, lon`) and **still places the marker**.

**E3 – Routing API Timeout or Failure**
The external routing service fails to respond, returns a non-`Ok` code, or returns a non-2xx status. The System clears the distance/fare fields and displays a banner: *"Route unavailable — The routing service failed to respond, so the distance and fare could not be calculated. Please try again."* The banner clears automatically on the next successful calculation.

**E4 – No Internet Connection**
Device is offline. The System shows the offline banner (see UCN_SC_E003 E4) and does not attempt the routing call.

**E5 – No Points Selected**
The System cannot calculate a trip until both an origin and a destination are set; the "Buy a Ride" step fails validation with *"Pick-up point and destination is required."*

**E6 – Daily Limit Reached (Guests only)**
Guest has used all 3 daily fare calculations. The System displays the limit modal and cancels the calculation.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- The route drawn is **origin → destination** only; it is *not* a route to the nearest PUJ.
- The daily guest limit is stored in `localStorage` and can be bypassed by clearing browser storage.

---

### Implementation References

| Element | Location |
|---|---|
| Fare calculator UI | `resources/views/map.blade.php` (left sidebar, `#distance`, `#price-regular`, `#price-discount`) |
| Route calculation | `resources/views/map.blade.php::calculateRoute()` → OSRM `router.project-osrm.org` |
| Fare lookup | `resources/views/map.blade.php::getFareFromDB()` + `@json($rates)` |
| Place search | `resources/views/map.blade.php::searchPlaces()` → Photon `photon.komoot.io` |
| Reverse geocoding | `resources/views/map.blade.php` (reverse geocode call) |
| Route layers | `resources/views/map.blade.php` → `route`, `route-line`, `route-line-glow` |
| Point markers | `resources/views/map.blade.php` → `pickup-point`, `destination-point` sources |
| Guest daily limit | `resources/views/map.blade.php` → `DAILY_LIMIT`, `toggleLimitModal()` |
| Status banner | `resources/views/map.blade.php` → `#map-alert`, `showMapAlert()` |
| Payment handoff | `app/Http/Controllers/PaymentController.php::index()` |

---

## UCN_SC_E005 — Topup Balance

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E005                                                                                                                                                           |
| **Use Case Name**     | Topup Balance                                                                                                                                                         |
| **Primary Actor**     | Commuter                                                                                                                                                              |
| **Secondary Actor**   | System / Admin                                                                                                                                                        |
| **Goal**              | Allow commuters to have digital currency for easier commuting payments.                                                                                               |
| **Trigger**           | Commuter presses the "+" button on the payment page and selects an amount and payment method.                                                                         |
| **Preconditions**     | 1. Commuter is successfully authenticated and logged in.<br>2. The digital wallet exists for the commuter (created at registration).<br>3. The System is operational. |
| **Supporting Actors** | Wallet, Top-up history ledger                                                                                                                                         |

> **Scope note:** the current implementation **does not integrate an external payment gateway**. The top-up is credited directly by the System after the commuter confirms the method. Steps relating to gateway redirects, callback signature validation and gateway cancellation are therefore **not implemented** — see *Known Gaps*.

---

### MAIN FLOW

| #   | Actor's Action                                                                                | System Response                                                                                                                                                        |
| --- | --------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Commuter clicks the "+" button.                                                               | System displays the Topup page with the current wallet balance, a custom-amount input, and predefined amount options.                                                  |
| 2   | Commuter selects a predefined amount (₱50 / ₱100 / ₱200 / ₱500) **or** types a custom amount. | System highlights the selected preset (and clears the highlight when a non-preset value is typed).                                                                     |
| 3   | Commuter selects a payment method (**GCash** or **Maya**) and clicks **"Confirm Payment"**.           | System validates the request server-side: `amount` required, numeric, **min ₱10**, max ₱100,000; `payment-method` required and restricted to the self-service methods. |
| 4   |                                                                                               | System adds the amount to the commuter's wallet balance.                                                                                                               |
| 5   |                                                                                               | System creates a **top-up history record** (user, wallet, amount added, payment method) for auditing.                                                                  |
| 6   |                                                                                               | System displays the success message *"Successfully topped up!"* and returns to the top-up page showing the new balance.                                                |

---

### Alternate Flow

**A1 – Commuter enters a Custom Amount**
1. At step 2, the commuter types an amount instead of choosing a preset.
2. System validates the amount (must be a number between ₱10 and ₱100,000) and proceeds with the top-up.

**A2 – Commuter changes the payment method**
1. Commuter selects a different payment method card before pressing "Confirm Payment".
2. System uses the newly selected method for the transaction record.

**A3 – Commuter reviews past top-ups**
1. Commuter opens **Top-up History** (`/payment/topup/history`).
2. System lists the records with search (ID) and filters (method, date range), plus total amount added.

**A4 – Admin monitors top-ups**
1. Admin opens `/topups`.
2. System lists **every** top-up — completed, pending, failed and cancelled — with a **Status** column, and filters by *search, payment status, payment method, date range and amount range*. The "funds added" total counts **completed** top-ups only. The list shows 10 per page.

---

### Postconditions

1. The commuter's wallet balance has been increased by the exact (2-decimal rounded) top-up amount.
2. A digital top-up / transaction record has been generated and saved (`TopupHistory`).
3. The commuter sees a success confirmation and the updated balance.

---

### Exceptions

**E1 – Payment Method Unavailable**
The commuter submits a method that is not available for self-service (anything other than **GCash** or **Maya**). System responds with *"The selected payment-method is invalid."* and performs **no** wallet change.
> Cash / "Admin Settlement" is intentionally **not** self-service; it can only be recorded by an admin.

**E2 – Invalid Custom Amount**
The amount is missing, non-numeric, negative, zero, or below the ₱10 minimum. System responds with the corresponding validation message (e.g. *"The amount must be at least 10."*) and performs **no** wallet change. Amounts above ₱100,000 are rejected as well.

**E3 – Already Paid / Duplicate Submission**
The commuter (double-clicks, refreshes, or replays the request) submits an identical top-up (same user, amount and method) within **2 minutes** of a previous successful one. System detects the duplicate, cancels the transaction and displays *"This top-up was already processed. Please wait a moment before retrying."* — the balance is credited only once.

**E4 – Database / System Error**
The wallet update fails. System displays *"Top-up failed. Please try again later."* and performs **no** wallet change.
> **Known gap:** the top-up history row is written *after* the wallet update and outside its error handling. If that insert fails, the wallet has already been credited, no history row exists and the commuter sees the default 500 page.

**E5 – Too Many Attempts (Rate Limiting)**
The commuter submits more than **5 top-up requests per minute**. System blocks the request (HTTP 429) and performs **no** wallet change.

**E6 – Suspected Fraud / Rate Limiting**
Covered by E5 (request-level) and E3 (duplicate-level). Automated credential-stuffing is additionally mitigated by the login throttle (see UCN_SC_E001 E5).

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **No external payment gateway.** There is no transaction ID, no redirect, no callback and **no signature verification**; the wallet is credited directly on form submission. Integrating GCash/Maya (e.g. PayMongo) would require re-adding the original steps 3–5, a webhook/callback endpoint, signature validation, and a pending→settled top-up state.
- **Duplicate detection is heuristic** (amount + method within 2 minutes), not a true idempotency key. Legitimate repeat top-ups of the same amount within 2 minutes are blocked by design.
- **Admin settlement recording has no UI.** Admins can *view* top-ups, but there is no admin action to record a cash/admin settlement, since the commuter-facing option was removed.
- **A top-up receipt is emailed** (amount, method, new balance) once the top-up is completed, in addition to the in-app history. There is still no gateway for top-ups, so nothing can be left pending and there is nothing to reconcile.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `payment.topup`, `payment.topup.process` (`throttle:5,1`), `payment.topup.history`, `admin.topups` |
| Controller | `app/Http/Controllers/PaymentController.php::topup()`, `topupProcess()`, `topupHistory()`, `showTopupsAdmin()` |
| Allowed methods | `PaymentController::SELF_SERVICE_TOPUP_METHODS = ['gcash', 'maya']` |
| View | `resources/views/commuter/topup.blade.php`, `topuphistory.blade.php` |
| Models | `app/Models/Wallet.php`, `app/Models/TopupHistory.php` |

---

## UCN_SC_E006 — Pay Fare

| Field                 | Value                                                                                                                                                                                                                                                                                                                |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E006                                                                                                                                                                                                                                                                                                          |
| **Use Case Name**     | Pay Fare                                                                                                                                                                                                                                                                                                             |
| **Primary Actor**     | Commuter                                                                                                                                                                                                                                                                                                             |
| **Secondary Actor**   | System / PayMongo Gateway                                                                                                                                                                                                                                                                                            |
| **Goal**              | Allow commuters to pay for the planned trip.                                                                                                                                                                                                                                                                         |
| **Trigger**           | Commuter clicks the **"Buy a Ride"** button on the Fare Calculator in the map's left sidebar.                                                                                                                                                                                                                        |
| **Preconditions**     | 1. Commuter is authenticated and logged in (route is behind `auth` + `verified`).<br>2. Commuter has an active, calculated route (pick-up + destination resolved).<br>3. A wallet exists for the commuter (created at registration).<br>4. For a gateway method: the PayMongo gateway is enabled (see *Scope note*). |
| **Supporting Actors** | Wallet, Payment ledger, Fare rate table, PayMongo Gateway                                                                                                                                                                                                                                                            |

> **Scope note — the gateway is optional and flag-controlled.** `config/paymongo.php` defaults to **disabled**, which preserves the original behaviour: a gateway method is recorded as a *declared* method and the commuter goes straight to the receipt. With `PAYMONGO_ENABLED=true` and **sandbox test keys** (`sk_test_`/`pk_test_`) the fare is settled through PayMongo's hosted checkout at **zero cost** — see `docs/PAYMONGO_SETUP.md`. A production deploy would use live keys.
>
> **Pay on board — scan a QR code first.** The Admin generates QR codes (**QR Codes** on the Admin sidebar, `GET /qr-codes`: **Generate QR Code** makes a new code in one click — no label, no vehicle; print, switch off, delete; `php artisan db:seed` also creates one ready-made code via `QrCodeSeeder`) and they are put up inside the PUJ. Pressing **"Buy a Ride"** no longer opens the checkout straight away: unless the commuter has a **valid scan**, the system keeps the trip and sends them to **Scan the QR code** (`GET /payment/scan`), where they scan with the in-app camera, type the code, or use their phone's own camera app (the code is a link, `/ride/scan/{token}`). *Any* active code made by the system works — it is not tied to a vehicle. A scan lasts **15 minutes** and covers **one fare**: the payment that uses it marks it spent (`qr_scans.used_at`, `payment_id`), so the next ride needs another scan. A code that is unknown, switched off or deleted is refused (*\"That QR code isn't valid. Scan one of the QR codes inside the PUJ.\"*), and `process` refuses a payment without a fresh scan even if the checkout URL is replayed. The rule is `config/qr.php` (`REQUIRE_QR_SCAN`, default on; `QR_SCAN_VALID_MINUTES`). A scan on its own proves the commuter holds a code, not that they are on board, so the checkout page also **sends the commuter's position** (`POST /payment/scan/locate`): the server looks for an active vehicle broadcasting within `QR_NEARBY_METERS` (150 m, a vague GPS fix widens it a little) and records that vehicle, its driver on duty and the distance on the scan (`qr_scans.vehicle_id`, `driver_id`, `matched_distance_m`); the payment inherits `vehicle_id` and `driver_id`, so each fare is attributed to a vehicle and driver without anyone typing it. The commuter's coordinates are matched and discarded, never stored. With `QR_REQUIRE_NEARBY_VEHICLE=true` (off by default) a scan that was not matched to a vehicle cannot pay (*"We couldn't confirm you are on a SmartCommute PUJ…"*). Scans never followed by a fare are counted: 3 in 7 days flags the commuter to the Admins (`php artisan fares:flag-evasion`).
>
> **Boarding permission is still not issued** — see *Known Gaps*.

---

### MAIN FLOW — A: Wallet (internal settlement)

| #   | Actor's Action                                                | System Response                                                                                                                                                                                                                                                      |
| --- | ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Commuter clicks **"Buy a Ride"**.                             | System validates `pickup`, `destination`, `distance` and re-**prices the fare server-side** from the published rate table, then displays the checkout page with the Final Fare, the commuter's balance, and the available methods (**GCash**, **Maya**, **Wallet**). |
| 2   | Commuter selects a payment method.                            | System highlights the selected method card and clears the highlight on the others.                                                                                                                                                                                   |
| 3   | Commuter selects **Wallet** and clicks **"Confirm Payment"**. | System re-validates the payload (`amount` numeric ≥ 0, `payment-method` ∈ `GCash, Maya, Wallet`, `transaction-id` required) and rejects a replayed `transaction-id`.                                                                                                 |
| 4   |                                                               | System **deducts the fare from the wallet**; if the balance is short the request is rejected (E2).                                                                                                                                                                   |
| 5   |                                                               | System creates a **payment record** with `status = paid`.                                                                                                                                                                                                            |
| 6   |                                                               | System **redirects (302)** to `/payment/receipt/{id}`, re-reading the stored payment, with the message *"Payment successful"*.                                                                                                                                       |

> The redirect at step 6 is deliberate: the receipt is rendered from the **persisted** record, so refreshing or going back can never charge the commuter twice.

### MAIN FLOW — B: GCash / Maya (PayMongo gateway)

| #   | Actor's Action                                                      | System Response                                                                                                                                                                                                                                                                                                                  |                                                                     |
| --- | ------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- |
| 1   | Commuter completes steps 1–2 above and picks **GCash** or **Maya**. | System creates a **checkout session** in one call (`POST /v1/checkout_sessions`) with `line_items` (amount converted to **centavos**) and `payment_method_types = [gcash                                                                                                                                                         | maya]`. PayMongo creates the payment intent as part of the session. |
| 2   |                                                                     | System writes the **payment record** as `status = pending` with the checkout-session and payment-intent ids. **The wallet is not touched.**                                                                                                                                                                                      |                                                                     |
| 3   |                                                                     | System **redirects the commuter to PayMongo's hosted checkout page**.                                                                                                                                                                                                                                                            |                                                                     |
| 4   | Commuter completes payment on PayMongo.                             | PayMongo sends a **signed webhook** (`POST /webhooks/paymongo`). System verifies the HMAC signature and, on `payment.paid`, sets `status = paid` and `paid_at`.                                                                                                                                                                  |                                                                     |
| 5   | Commuter is returned to `/payment/returned`.                        | System **reconciles**: already `paid` → receipt; still `pending` → System queries the intent at PayMongo and settles it when the status is `succeeded`; if the gateway reports a terminal failure, records `failed` and says so; otherwise shows the *"Confirming your payment"* screen, which auto-refreshes every few seconds. |                                                                     |
| 6   |                                                                     | System displays the receipt, or — on decline/expiry — records `status = failed` / `cancelled`. Every settlement path (webhook, return page, and the 10-minute `payments:reconcile` job that asks PayMongo about payments whose webhook never arrived) goes through one `PaymentSettlement`, which emails the commuter **one receipt** when a fare is paid (wallet payments too) and a *did not go through* email when it fails. Repeated gateway errors (3 in 15 minutes) email the Admins once.                                                                                                                                                                                                                                   |                                                                     |

> Step 5 is the reconciliation fallback: if the webhook is lost, the return URL still settles the payment by asking PayMongo directly. Without it a commuter could sit on "pending" forever. The lookup is throttled to once every 4 seconds because this screen polls.

---

### Alternate Flow

**A1 – Commuter changes the payment method**
1. At the method step, the commuter clicks another method card.
2. System moves the highlight and uses the newly selected method for the transaction.

**A2 – Gateway method with the gateway disabled**
1. The commuter picks GCash/Maya while `PAYMONGO_ENABLED=false` (or no secret key is set).
2. System makes **no** external call and records the payment as a declared method, `status = paid`, then shows the receipt. This is the pre-integration behaviour and keeps local dev and CI working with no credentials.

**A3a – A pending payment expires**
1. A gateway payment still `pending` **12 hours** after it was started is closed as `cancelled` (*"Expired: not completed within 12 hours."*); nothing was charged.
2. This runs hourly (`payments:expire-pending`) and whenever payment history, the admin ledger or a checkout page is opened, so it works without a scheduler. A late webhook for a genuinely paid session still settles it.

**A3 – Commuter refreshes / goes back after paying**
1. The receipt is a `GET` on `/payment/receipt/{id}`; refreshing re-renders it from the stored record.
2. System creates **no** second payment and debits **no** further balance.

**A4 – Commuter cancels at the PayMongo checkout**
1. The commuter abandons the hosted checkout; PayMongo returns them to `/payment/cancelled`.
2. System marks the pending payment `status = cancelled` with *"Cancelled at checkout."* — **nothing is charged**.

**A5 – Commuter pays with the wallet instead**
1. The commuter selects **Wallet**; the card displays the live balance.
2. System subtracts the fare and stores the method as `Wallet`. The gateway is never contacted.

---

### Postconditions

1. A **payment record** exists with a unique transaction id, the chosen method, the route, the **server-computed** fare, and a terminal status.
2. If the method was `Wallet`, the balance is reduced by the fare; for a gateway method the balance is **unchanged**.
3. For a gateway payment: `status = paid` with `paid_at` set, and the PayMongo checkout-session / payment-intent ids stored for reconciliation.
4. The commuter sees the **digital receipt**, and the payment appears in their history (UCN_SC_E007) with a status badge for anything still `pending` / `failed` / `cancelled`.

---

### Exceptions

**E1 – Payment Method Unavailable**
The submitted `payment-method` is not one of `GCash`, `Maya`, `Wallet`. System responds with the validation message and creates **no** payment and **no** balance change.

**E2 – Insufficient Wallet Balance**
The commuter selects **Wallet** but the balance is less than the fare. System responds with *"You don't have enough balance"*; no payment, no balance change.

**E3 – Already Paid / Duplicate Submission**
The commuter replays a request (double click, Back button, refresh) carrying a `transaction-id` this commuter already used. **No second charge** and no second checkout; the answer depends on where that payment stands: **paid** → the receipt opens with *"This fare has already been paid. Here is your receipt."*; **pending** → the *Confirming your payment* screen opens with *"This payment is already being processed…"*; **failed / cancelled** → payment history with *"That payment did not go through and nothing was charged…"*. The *Confirm Payment* button also sends only one request per page view, and coming back to the same trip while a gateway payment is pending shows a *payment already in progress* notice with a **Check payment status** link.

**E4 – External Payment Gateway Timeout / Error**
The gateway rejects the call, times out, or returns an error envelope. System responds with *"The payment service is unavailable right now. Please try again."*, creates **no** payment row and **no** checkout session, and logs `PAYMONGO … FAILED`. The commuter keeps the funds.

**E5 – Payment Declined at the Gateway**
The commuter declines or the card/e-wallet has insufficient funds. PayMongo reports the intent as `failed` (webhook `payment.failed`, or discovered by the return-URL reconciliation). System sets `status = failed` with `failed_at` and a failure message — *"The payment was declined by the gateway."* from the webhook, *"The payment was not completed at the gateway."* from the return-URL reconciliation. The balance is untouched, and instead of the pending screen looping forever the commuter is redirected to the payment history with *"That payment could not be completed. Nothing was charged."*

**E6 – Checkout Session Expired**
PayMongo emits `checkout_session.expired`; System sets `status = cancelled` with *"The checkout session expired."*

**E7 – Unsigned / Tampered Webhook**
A webhook arrives with an invalid HMAC signature. System logs the rejection and responds **401** without touching any payment.

**E8 – Database / System Error**
Creating the payment record fails. System responds with *"There was a problem trying to process the payment"* and performs **no** balance change.

**E9 – Missing Wallet**
The commuter has no wallet row (data inconsistency). System aborts with HTTP 404 rather than crashing.

**E10 – Fare Table Empty**
No fare rates are published for the current fare table, so the fare cannot be priced. System responds with *"Fares are unavailable right now. Please try again later."*

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **The gateway is flag-controlled, and a safety rail refuses to arm it unsafely.** With it off, GCash/Maya are recorded as *declared* and **never charged*. `PayMongoService::configProblems()` refuses to run when the key type and base URL disagree (live key + sandbox host, sandbox key + live host), when a **live key is armed outside `production`**, when the host is not a PayMongo host, or when the secret key is empty. Each refusal logs `PAYMONGO CONFIGURATION REFUSED` and degrades to the declared-payment flow — it never 500s.
- **`statement_descriptor` is not honoured** on a checkout session (only on a standalone payment intent); PayMongo falls back to the merchant's registered descriptor.
- **No boarding permission / ticket.** Paying does **not** create a trip, a one-time ride token, or any state a driver or vehicle can verify. This remains the largest functional gap in this use case.
- **The fare is re-priced server-side, but the *distance* is still client-supplied** (it comes from the browser's OSRM call). The amount can no longer be tampered with, but the distance can. Closing it means routing the trip server-side.
- **Discount fares are not verified.** The calculator displays a discounted figure for Student/Elderly/PWD, but only the **regular** fare is priced and charged server-side; no ID is checked.
- **No refunds, cancellations, or fare disputes** from the commuter side. Once `paid`, a payment is immutable.
- **No webhook event-deduplication store.** Settlement is idempotent (`pending → paid` only happens once), but replayed events are not recorded.
- **No dead-letter/queue handling** — webhook processing is synchronous, so a slow PayMongo call blocks the request.
- The receipt is rendered by a single view (`commuter/viewreceipt.blade.php`); the former duplicate `commuter/receipt.blade.php` was removed in favour of POST/redirect/GET.
- Receipts are in-app only — no email or PDF.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `payment.index`, `payment.process`, `payment.showReceipt`, `payment.returned`, `payment.cancelled`, `paymongo.webhook` |
| Controller | `app/Http/Controllers/PaymentController.php` — `index()`, `process()`, `startGatewayPayment()`, `returned()`, `cancelled()`, `webhook()`, `settle()` |
| Gateway client | `app/Services/PayMongoService.php` — checkout sessions, intent retrieval, configuration audit, signature verification, webhook parsing |
| Fare pricing | `app/Services/FareCalculator.php` — server-side tier lookup (mirrors the client `getFareFromDB()`) |
| Configuration | `config/paymongo.php`, `.env.example`; setup guide `docs/PAYMONGO_SETUP.md` |
| CSRF exemption | `app/Http/Middleware/VerifyCsrfToken.php` — `webhooks/paymongo` |
| Schema | `database/migrations/2026_10_02_090000_add_gateway_lifecycle_to_payments_table.php` |
| Methods | `PaymentController::SELF_SERVICE_FARE_METHODS = ['GCash', 'Maya', 'Wallet']`, `PayMongoService::gatewayMethods()` |
| Views | `resources/views/commuter/payment.blade.php`, `viewreceipt.blade.php`, `paymentpending.blade.php` |
| Models | `app/Models/Payment.php` (`status`, gateway ids, `STATUS_*` constants), `app/Models/Wallet.php` |
| Tests | `tests/Feature/PayMongoPaymentTest.php` (29), `tests/Feature/UcnRegressionTest.php` |
| Related | UCN_SC_E005 (Top-up), UCN_SC_E007 (Payment History) |

---

## UCN_SC_E007 — View Payment History

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E007                                                                                                                                                           |
| **Use Case Name**     | View Payment History                                                                                                                                                   |
| **Primary Actor**     | Commuter                                                                                                                                                              |
| **Secondary Actor**   | System                                                                                                                                                                |
| **Goal**               | Allow the commuter to review past fare payments so they can verify that their money was deducted correctly.                                                          |
| **Trigger**           | Commuter clicks the **"History"** button (map right sidebar / receipt list, or **"Back to History"** on a receipt).                                                     |
| **Preconditions**     | 1. Commuter is authenticated and logged in.<br>2. The payment ledger is readable.<br>3. A wallet exists for the commuter (the page displays the balance).          |
| **Supporting Actors** | Payment ledger, Wallet                                                                                                                                                 |

---

### MAIN FLOW

| #   | Actor's Action                                        | System Response                                                                                                                                                       |
| --- | ----------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Commuter opens Payment History.                      | System queries **only that commuter's** payments and displays them newest-first in a card list, together with the wallet balance and the **total spent**.        |
| 2   | Commuter scrolls down the list.                      | System paginates the list (**10 receipts per page**) and fetches the older page on demand; filters/search are preserved across pages.                            |
| 3   | Commuter taps a specific transaction.                | System displays the **receipt detail** for that payment (route, distance, method, transaction id, fare, paid-at) at `/payment/receipt/{id}`.                       |

> The detail view is **owner-scoped**: `showReceipt()` filters on `paid_by = Auth::id()`, so a commuter cannot open another commuter's receipt by guessing the id (HTTP 404).

---

### Alternate Flow

**A1 – Commuter selects another transaction**
1. At step 3 the commuter opens a different row (either from the list or from the previous receipt via **"Back to History"**).
2. System displays that payment's receipt.

**A2 – Commuter wants top-up history instead**
1. The commuter clicks the **"+" / wallet** button next to the balance in the map header (or the wallet card on the profile page).
2. System opens `/payment/topup/history` (see UCN_SC_E005 A3) and redirects to `/payment/history` when they navigate back.

**A3 – Filter by date range**
1. The commuter picks a **Quick Range** (7 / 30 / 90 days) or types an explicit `from_date` / `to_date`, then clicks **"Apply Filters"**.
2. System filters on `whereDate('paid_at', ...)` and shows only transactions inside that period. A receipt opens at `payment/receipt/{id}`. The **same route** serves the Admin from the fare-transactions ledger (any payment, whatever its status, with the commuter's e-mail and a *Back to Transactions* link); the old `transactions/receipt/{id}` route and view were removed. The commuter's history lists 10 receipts per page and its "total spent" counts settled fares only. **"Clear Filters"** restores the full list.

**A4 – Search by transaction ID**
1. The commuter types into the search field (placeholder: *"Transaction ID or destination"*) and applies.
2. System matches `transaction_id` **or** `destination` with a `LIKE %term%` and shows the matching records.

**A5 – Download a receipt**
1. In the detail view the commuter clicks **"Download Receipt"**.
2. System renders the receipt to a PNG in the browser (html2canvas, scale 3) and saves it to the commuter's device as `Receipt-#SC-XXXXXXXX.png`. Nothing is sent to the server.

---

### Postconditions

1. The list reflects the commuter's **active search term and date range** (carried in the query string and re-applied on every page).
2. The displayed **total spent** is the sum of *all* of the commuter's fare payments.
3. Opening a receipt reveals that single payment only; no other commuter's data is reachable.

---

### Exceptions

**E1 – No History Found**
The commuter has never paid a fare. System renders the empty state *"No transactions found"* — no error, no empty table.

**E2 – No Results for Filter / Search**
The applied filter or search term matches nothing. System renders the same empty state and offers **"Clear Filters"** to return to the full list.

**E3 – Receipt Not Found / Not Yours**
The requested receipt id does not exist, or belongs to another commuter. System responds with HTTP 404.

**E4 – Network / Server Error**
**Not handled explicitly** — see *Known Gaps*.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **No explicit network/server error state.** A failed query renders an empty list rather than an error banner, so "no results" and "something broke" look identical to the commuter.
- **Quick ranges are relative windows (7 / 30 / 90 days)**, not the calendar presets *"Last Month"* / *"This Month"* from the original narrative; only the relative windows and free-form dates exist.
- **"Total spent" ignores the active filters** — it is an unfiltered `sum('price')` over **settled** payments only, so it can disagree with the visible list. Filtered totals would need `sum()` on the filtered query.
- **No sorting or column options** — the order is fixed to `paid_at DESC`.
- Payments still `pending` after 12 hours are closed as `cancelled` when the page is opened (UCN_SC_E006 A3a); pending / failed attempts are sorted by the time they were started.
- **The list markup is duplicated** for the desktop and mobile layouts; the paginator is therefore rendered twice on the page (hidden by breakpoint).
- The page creates the wallet on the fly if the row is missing (`PaymentController::walletFor()` → `Wallet::firstOrCreate`), so it no longer 404s for a commuter who has never topped up.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `payment.history` (`GET /payment/history`), `payment.showReceipt` (`GET /payment/receipt/{id}`), `payment.topup.history` |
| Controller | `app/Http/Controllers/PaymentController.php::history()`, `showReceipt()` |
| Pagination | `->orderByRaw('COALESCE(paid_at, created_at) DESC')->paginate(10)->withQueryString()` |
| Views | `resources/views/commuter/paymenthistory.blade.php`, `resources/views/commuter/viewreceipt.blade.php` |
| Models | `app/Models/Payment.php`, `app/Models/Wallet.php` |
| Related | UCN_SC_E006 (Pay Fare — creates these records), UCN_SC_E005 (Top-up History) |

---

## UCN_SC_E008 — Change Password

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

### MAIN FLOW

| #   | Actor's Action                                             | System Response                                                                                                                                                    |
| --- | ---------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User navigates to Settings (`/settings`) and opens the **Security** section. | System displays the **Change Password** form: *Current Password*, *New Password*, *Confirm New Password*.                                                          |
| 2   | User enters their current password.                        | *(no system response — client-side field masking only)*                                                                                                           |
| 3   | User enters the new password and confirms it.              | *(no system response — client-side field masking only)*                                                                                                           |
| 4   | User clicks **"Update Password"**.                         | System validates the request server-side: `current_password` **required** and must match the stored hash (`current_password` rule); `password` **required, string, min 8, confirmed, different from the current password** (*"The new password must be different from your current password."*). |
| 5   |                                                                | System **hashes** the new password (`Hash::make`) and updates the user record.                                                                                     |
| 6   |                                                                | For staff roles, System **invalidates all other active sessions/tokens** (`Auth::logoutOtherDevices`), keeping only the current device signed in. With database sessions the other sessions are also deleted outright. |
| 7   |                                                                | System displays the success message *"Password changed successfully."*                                                                                             |

> Steps 2–3 have no system response by design; those cells are intentionally left blank rather than filled with "makes the input".

---

### Alternate Flow

**A1 – Staff (non-commuter) account**
1. A driver / driver manager / maintenance manager / admin opens `/settings`.
2. System detects the role and serves the staff layout (`settings`) instead of the commuter layout (`commuter.settings`).
3. The steps are otherwise **identical** — same fields, same endpoint (`PUT /settings/password`), same validation.

> This is a **presentation difference, not a divergent flow**; it is documented here because the original narrative modelled it as an alternate flow.

**A2 – User wants to end other sessions without changing the password**
1. The user presses **"Log out other devices"** (`POST /settings/logout-others`).
2. System calls `logoutOtherDevices()` and confirms *"All other sessions have been terminated."* — this remains available as a standalone action.

---

### Postconditions

1. The user's password has been changed and stored **hashed** (never in plain text).
2. The user record has been updated.
3. The system shows a success message.
4. **All other active sessions/tokens for this user are invalidated**; only the current device remains signed in.
5. Any session opened with the old password can no longer authenticate.

---

### Exceptions

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

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **Length-only password policy.** There is no complexity requirement (no upper/lower/digit/symbol rule), no check against the user's name/email, and **no password-history or reuse prevention**.
- **No rate limiting on password changes.** The endpoint is behind `auth` but has no throttle; combined with a hijacked session this allows repeated attempts at E1.
- **No active-session list.** The user cannot see *which* devices are signed in; sessions can only be terminated blindly.
- **Session invalidation is immediate but not retroactive for tokens already issued to third parties** — Sanctum personal access tokens are unaffected by `logoutOtherDevices()` and must be revoked separately.
- **The user is not asked to re-authenticate** beyond the current-password field, and no re-authentication is required to *view* the security settings.
- No password-change history or notification email is sent to the account address.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `settings.edit` (`GET /settings`), `settings.password` (`PUT /settings/password`), `settings.logout-others` (`POST /settings/logout-others`) |
| Controller | `app/Http/Controllers/SettingsController.php::edit()`, `updatePassword()`, `logoutOtherDevices()` |
| Role-based view | `commuter.settings` for the `commuter` role, otherwise `settings` |
| Views | `resources/views/commuter/settings.blade.php` (Security tab), `resources/views/settings.blade.php` |
| Middleware | `auth`, `verified` |
| Related | UCN_SC_E001 (Login), UCN_SC_E002 (Create Account — initial password) |
**Divergence Note:** Commuter vs staff password change paths diverge at the view layer (`commuter/settings` vs `settings`) but share the same endpoint (`PUT /settings/password`) and validation rules. No divergence in logic.

---

## UCN_SC_E009 — Clock In

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E009                                                                                                                                                           |
| **Use Case Name**     | Clock In                                                                                                                                                              |
| **Primary Actor**     | Driver                                                                                                                                                                |
| **Secondary Actor**   | System                                                                                                                                                                |
| **Goal**               | Allow the driver to clock in to the system and start their shift.                                                                                                     |
| **Trigger**           | Driver clicks the **"Clock In"** button on the **Today's Shift** card in the map's left sidebar.                                                                        |
| **Preconditions**     | 1. Driver is authenticated and registered to the system.<br>2. The driver has no timekeeping record for today yet.<br>3. A vehicle is assigned to the driver and, if it is shared by two drivers, today is the driver's duty day on the roster.<br>4. The System is operational.                |
| **Supporting Actors** | Timekeeping ledger, Driver record                                                                                                                                     |

> **A vehicle — and, on a shared one, your duty day — is a precondition.** A driver can only go on shift while a vehicle is assigned to them (staff assign it from the Vehicles page), and when two drivers share it, only on a day the Maintenance Manager's duty roster gives to them (UCN_SC_E015 A6): otherwise *"Today is [co-driver]'s day on this vehicle…"* or, with no roster line, *"No duty roster is set…"*. Without one, the **Clock In** button is disabled, the Availability toggle cannot be switched on, and the map's left sidebar says so; a replayed request is refused with *"You have no vehicle assigned yet, so you cannot clock in or set your status to active. Please contact the Maintenance Manager."* Going **inactive** never needs a vehicle.

---

### MAIN FLOW

| #   | Actor's Action                                                       | System Response                                                                                                                                                                        |
| --- | -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver opens the map's left sidebar.                                | System shows the driver left sidebar: the **Availability** card (Active / Inactive toggle), the **Today's Shift** card, and the **Weekly Log** link to the full timekeeping page.             |
| 2   | Driver clicks **"Clock In"** on the Today's Shift card.             | System creates a **timekeeping record** for today with `time_in` set to the current time (**Asia/Manila**, `h:i A`).                                                                |
| 3   | Driver waits for the process to complete.                            | System sets the driver status to **`active`** (visible to commuters), refreshes the UI — the card switches to **"In Progress"** and shows *Time In* — and notifies *"Clocked in at HH:MM AM/PM."* |

---

### Alternate Flow

**A1 – Driver has already clocked in today**
1. The driver presses **"Clock In"** again (the button is hidden once a record exists, so this is only reachable by a replayed request).
2. System detects an existing record for today, performs **no** write, and responds with *"You have already clocked in today."*

**A2 – Driver profile missing**
1. The authenticated user has the `driver` role but no `Driver` row.
2. System aborts with *"Driver profile not found."* instead of crashing.

---

### Postconditions

1. The driver has successfully clocked in (a `TimeKeeping` row exists for today with a `time_in`).
2. The timekeeping record has been saved to the database.
3. The driver is set to **`active`** and is therefore advertised as available to commuters.
4. The system notifies the driver that they are clocked in, and the UI shows **"In Progress"**.

---

### Exceptions
- Suspension exceptions apply to suspended accounts (see UCN_SC_E012 A5).

**E1 – Database / System Error**
The timekeeping record cannot be created. System responds with *"Clock-in failed. Please try again later."*, performs **no** write and does **not** change the driver status.

**E2 – Driver Profile Not Found**
See A2 — the driver has no `Driver` record. System responds with *"Driver profile not found."*

**E3 – Already Clocked In**
See A1 — *"You have already clocked in today."*

**E4 – No Vehicle Assigned**
The driver has no vehicle. System performs **no** write, leaves the status unchanged and responds with the *no vehicle assigned* message above. The same rule applies to switching the Availability toggle to **active** (`POST /status`).

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **One shift per day, enforced.** `clockIn` rejects a second record for the same date, so a driver **cannot** clock in twice (e.g. an AM and a PM shift). Multi-split shifts would need a different uniqueness rule.
- **No GPS / location verification.** The original precondition *"the driver's device GPS is enabled and permissions are granted"* is **not checked** — clock-in works from any device, anywhere.
- **The vehicle check is "has one assigned"**, not "has an active one": a driver whose only vehicle is under maintenance can still clock in (the map then reads *Vehicle not active* and GPS broadcasting stays off). Clock-in is not tied to which vehicle the driver takes out.
- **No manager approval or overtime validation** — a driver may clock in at any hour, and a shift longer than 8 hours is only flagged as overtime at clock-out (UCN_SC_E010).
- **Clock-in and status are two separate writes.** If the record is created but the `drivers.status` update fails, the driver has a shift but stays offline.
- Time is stored as a formatted **string** (`h:i A`) in a `date` column, while clock-out re-parses it with `Carbon::parse(..., 'Asia/Manila')` — string time storage is fragile across locales/DST-free zones.
- The clock-in/out buttons live on the **map**; the `/timekeeping` page is read-only for drivers (staff can add entries via the driver-manager screen).

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `driver.timekeeping.clock-in` (`POST /timekeeping/clock-in`), inside `role:driver` + `auth` + `verified` |
| Controller | `app/Http/Controllers/DriverController.php::clockIn()` |
| Sidebar UI | `resources/views/map.blade.php` — Availability card, **Today's Shift** card, Clock In / Clock Out forms, Weekly Log link |
| Weekly log | `resources/views/driver/time-keeping.blade.php` — `DriverController::timekeeping()` |
| Models | `app/Models/TimeKeeping.php`, `app/Models/Driver.php` |
| Related | UCN_SC_E010 (Clock Out), driver registration/approval |

---

## UCN_SC_E010 — Clock Out

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E010                                                                                                                                                           |
| **Use Case Name**     | Clock Out                                                                                                                                                             |
| **Primary Actor**     | Driver                                                                                                                                                                |
| **Secondary Actor**   | System                                                                                                                                                                |
| **Goal**               | Allow the driver to clock out of the system and end their shift.                                                                                                        |
| **Trigger**           | Driver clicks the **"Clock Out"** button on the **Today's Shift** card in the map's left sidebar.                                                                       |
| **Preconditions**     | 1. Driver is authenticated and registered to the system.<br>2. The driver is already clocked in today and that record has **no** `time_out` yet.<br>3. The System is operational. |
| **Supporting Actors** | Timekeeping ledger, Driver record                                                                                                                                     |

> **Vehicles are intentionally not a precondition.** A driver's vehicle is assigned by staff **at registration/approval time**, so there is no per-shift assignment to check at clock-out.

---

### MAIN FLOW

| #   | Actor's Action                                                     | System Response                                                                                                                                                                       |
| --- | ------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver opens the map's left sidebar.                              | System shows the driver left sidebar: the **Availability** card, the **Today's Shift** card (now **"In Progress"** with *Time In*), and the **Weekly Log** link.                          |
| 2   | Driver clicks **"Clock Out"** on the Today's Shift card.          | System finds today's open record and computes the worked duration from `time_in` to now (Asia/Manila), rounding to 2 decimals, and splits it into **regular** (capped at 8 h) and **overtime** hours. |
| 3   | Driver waits for the process to complete.                          | System writes `time_out`, `hours_worked` and `overtime_hours`; sets the driver status to **`inactive`** so they are no longer advertised as available; refreshes the UI to **"Shift Complete"** with the total; and notifies *"Clocked out at HH:MM AM/PM. Total: X hrs."* |

> Clocking out **takes the driver offline**. Previously the status stayed `active` after clock-out, which left a finished driver visible to commuters.

---

### Alternate Flow

**A1 – No active shift (driver is not clocked in)**
1. The driver presses **"Clock Out"** without an open shift (the button is hidden in this state, so this is mainly reachable by a replayed request or a stale page).
2. System finds no open record for today, performs **no** write, and responds with *"No active shift found to clock out."*

**A2 – Shift longer than 8 hours (overtime)**
1. The shift duration exceeds 8 hours.
2. System records `hours_worked` as the full duration and `overtime_hours` as `hours_worked − 8`, and the card additionally shows *"+N.N OT"*.

**A3 – Driver profile missing**
1. The authenticated user has the `driver` role but no `Driver` row.
2. System aborts with *"Driver profile not found."* instead of crashing.

---

### Postconditions

1. The driver has successfully clocked out — today's record now has a `time_out`.
2. The timekeeping record has been updated with `time_out`, `hours_worked` and `overtime_hours`.
3. The driver is set to **`inactive`** (offline) and is no longer shown to commuters as available.
4. The system notifies the driver, and the UI shows **"Shift Complete"** plus the total hours (and overtime, if any).
5. The closed shift appears in the **Weekly Log** for that week.

---

### Exceptions

**E1 – Database / System Error**
The record cannot be updated. System responds with *"Clock-out failed. Please try again later."*, performs **no** write and leaves the shift open.

**E2 – No Active Shift Found**
See A1 — *"No active shift found to clock out."*

**E3 – Driver Profile Not Found**
See A3 — *"Driver profile not found."*

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **A forgotten clock-out is closed automatically.** `php artisan shifts:auto-close` (every 15 minutes) closes any shift with no `time_out` that has been open longer than `TIMEKEEPING_AUTO_CLOSE_AFTER_HOURS` (default 14) or that is still open after midnight (`TIMEKEEPING_AUTO_CLOSE_AT_MIDNIGHT`). Nobody knows when the driver really stopped, so the entry is credited `TIMEKEEPING_AUTO_CLOSE_CREDIT_HOURS` (default 8, never more than has elapsed) with **no overtime**, stamped `auto_closed_at`, and left for the Driver Manager to review (UCN_SC_E013). The driver is set inactive (unless a newer shift is open), the vehicle drops off the live map and is marked offline (its GPS history is kept), and the driver's phone can no longer broadcast the vehicle's position until their next shift.
- **No GPS / location verification** — a driver can clock out from anywhere, and nothing confirms the vehicle actually stopped.
- **Duration is measured from `time_in` parsed out of a formatted string** (`Carbon::parse($record->date . ' ' . $record->time_in, 'Asia/Manila')`). A `time_in` written by the driver-manager screen in a different format/timezone will skew the hours; storing real datetimes would remove this fragility.
- **`hours_worked` is rounded to 2 decimals**, and breaks are not considered — the value is a raw difference between clock-in and clock-out.
- **The `regularHours` local is computed but never used** (dead code in `clockOut()`); only `hours_worked` and `overtime_hours` are persisted.
- **No manager confirmation / adjustment flow for drivers** — corrections must be made by a driver manager, who can add whole entries (`DriverManagerController::timeKeepingStore()`) but cannot edit a driver's self-recorded shift.
- **Status and timekeeping are two separate writes** — if the `drivers.status` update fails after the record is closed, the driver remains advertised as active.
- **The Availability toggle can contradict the shift state.** `driver.status.update` lets a driver flip Active/Inactive at any time, so a driver who has clocked out can be switched back to `active` by hand (and vice-versa). The shift record is the source of truth for the clock; the toggle is not validated against it.
- Drivers can only hold **one shift per day** (see UCN_SC_E009), so a re-clock-in on the same day is refused and the driver must be handled by a manager.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `driver.timekeeping.clock-out` (`POST /timekeeping/clock-out`), inside `role:driver` + `auth` + `verified` |
| Controller | `app/Http/Controllers/DriverController.php::clockOut()` |
| Sidebar UI | `resources/views/map.blade.php` — Today's Shift card, Clock Out form, Shift Complete state |
| Weekly log | `resources/views/driver/time-keeping.blade.php` — `DriverController::timekeeping()` |
| Manager corrections | `app/Http/Controllers/DriverManagerController.php::timeKeepingStore()` |
| Models | `app/Models/TimeKeeping.php`, `app/Models/Driver.php` |
| Related | UCN_SC_E009 (Clock In) |

---

## UCN_SC_E011 — Reset Password

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

### MAIN FLOW

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

### Alternate Flow

**A1 – Toggle Password Visibility / Watch the Strength Meter**
1. User clicks the eye icon inside a password field.
2. System reveals the typed value in plain text and swaps the icon to *eye-slash*; clicking again re-masks it. Purely client-side.
3. The strength meter re-evaluates on every keystroke and the confirm field shows whether the two values match.

**A2 – Return to Login**
1. User decides not to reset and clicks *"Back to login"* (available on both the request and the reset view).
2. System redirects to `/login`; no token is consumed and no record is changed.

---

### Postconditions

1. The user's password has been changed and stored **hashed**.
2. The remember token has been regenerated, invalidating persistent logins issued before the reset.
3. The `PasswordReset` event has been dispatched, so any registered listener can invalidate other sessions.
4. A `password_reset` audit-trail entry has been recorded against the user.
5. The password reset token has been consumed and can no longer be replayed.
6. The user is redirected to the login page and must authenticate with the new password.

---

### Exceptions

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

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **No rate limiting on the reset *request***. `POST /forgot-password` has no throttle, so an attacker can enumerate registered emails by timing the response (the response message differs between `INVALID_USER` and `RESET_LINK_SENT`).
- **No notification to the account owner** that the password was changed — only the audit trail (`password_reset` entry) records it.
- **The reset view requires the email as a query parameter** alongside the token; a user opening the link from a different client without `?email=` is asked to supply it again.
- No password-history or reuse prevention (see UCN_SC_E008 for the shared policy notes).

---

### Implementation References

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

---

## UCN_SC_E012 — Manage Drivers

| Field                 | Value                                                                                          |
| --------------------- | ---------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E012                                                                                    |
| **Use Case Name**     | Manage Drivers                                                                                 |
| **Primary Actor**     | Driver Manager / Admin (`role:admin|driver_manager`)                                           |
| **Secondary Actor**   | System, Email Service                                                                          |
| **Goal**               | Allow the Driver Manager to review, add, edit, suspend, approve or reject driver accounts in the system. |
| **Trigger**           | Actor selects the **"PUJ Drivers"** (Admin) or **"Drivers"** (Driver Manager) item on the sidebar (`GET /drivers`). |
| **Preconditions**     | 1. Driver Manager / Admin is authenticated and registered to the system.<br>2. The actor holds a role permitted to manage driver data. |
| **Supporting Actors** | User record, Driver record, Vehicle record, Activity log                                        |

> **Corrections vs. the original narrative**
> - The sidebar item is **"PUJ Drivers"** for an Admin and **"Drivers"** for a Driver Manager (see `config/menu.php`); the original just said "Drivers".
> - The table columns are **`#`, `Driver`, `Status`, `Registered`, `Actions`** — the original said *"Driver ID, Name, Status and Action buttons"*, and there is no separate ID column.
> - The add button is **"Add Driver"**, not *"Create Driver"*; the commit button in that modal is **"Create Driver"**.
> - The `Driver ID` in exception E2 is the **license number** (the field is unique); the `driver_code` is also unique but is not what the original message described.
> - **Suspend / Reactivate (A4)** and the *"a driver holding a vehicle cannot be deleted"* guard (E8) were added after the original narrative was written.
> - The original main flow left rows 3–8 empty; those steps are the alternate flows below.

---

### MAIN FLOW

| #   | Actor's Action                                                          | System Response                                                                                                                                          |
| --- | ------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver Manager clicks the **"PUJ Drivers" / "Drivers"** sidebar item.   | System redirects to the driver management page (`admin.drivers.index`).                                                                                     |
| 2   |                                                                          | System displays the driver table with columns **`#`, `Driver`, `Status`, `Registered`, `Actions`**, a search box, status filter chips (*All / Pending / Approved / Rejected / Suspended*) and an **"Add Driver"** button. |

> Steps 3+ are alternate flows (A1–A6); the main flow is "open the list and read it".

---

### Alternate Flow

**A1 – Add a Driver**
1. In the list, the actor clicks **"Add Driver"**.
2. System displays the **Add Driver** modal: *Name, Email, Password, Confirm Password, License Number, License Code, Expiration Date, Contact Info, License Image*. The **driver code is not typed in**: it is generated automatically (`DRV-000001`, `DRV-000002`, …).
3. Driver Manager inputs all required fields and clicks **"Create Driver"**.
4. System validates: `name`, `license_number`, `license_code`, `expiration_date`, `contact_info`, `license_image` **required**; `email` **required, email, unique:users**; `password` **required, min 8, confirmed**; `license_image` **image, jpg / jpeg / png, max 2048 KB**; `contact_info` **a Philippine mobile number** (`9XX XXX XXXX`, optionally with `0` or `+63`); `license_number` **unique:drivers** (E2).
5. System stores the licence image (`storage/app/public/licenses`), creates the **User** (email verified immediately, role `driver`), creates the **Driver** profile with `is_approved = 1` and a generated driver code, and confirms *"Driver account created."* A validation failure keeps the modal open and shows each message under its own field.

**A2 – Edit a Driver**
1. In the list, the actor clicks the edit icon on a driver row.
2. System displays the editable **Driver** form (name, licence fields, contact; the driver code is shown read-only) together with the stored licence image. The edit icon is **not offered for a pending registration** — a pending driver can only be approved or rejected (A6), and the server refuses the update with *"This driver is still pending approval…"*.
3. Driver Manager updates the fields and clicks **"Save Changes"**.
4. System validates the same field set as A1 (ignoring the current row for uniqueness), optionally replaces the licence image, updates the record and confirms *"Driver updated."*

**A3 – Delete a Driver**
1. In the list, the actor clicks the delete icon on a driver row.
2. System displays a confirmation dialog ("Delete Driver?").
3. Driver Manager confirms with **"Delete"**.
4. System deletes the **User** and the **Driver** record and confirms *"Driver removed."* — unless E8 applies. A driver who also has payments or top-ups is **anonymised** instead: the driver profile and licence image are removed, the user becomes an anonymised shell, and the payments and top-ups are kept (an `anonymised` audit entry is written instead of `deleted`). A driver who holds a vehicle shows a lock instead of the delete icon.

**A4 – View Driver Details**
1. In the list, the actor clicks the ID-card icon on a driver row.
2. System displays a read-only modal with the driver's identity, licence data and expiry, followed by the driver's **Timekeeping** (latest 10 days, with total days / hours / overtime) and **Violations** (latest 10, with fine, **Paid / Unpaid** status and the outstanding total). Both are fetched when the modal opens (`GET /drivers/{id}/records`), so the list itself stays light.
3. From that modal the actor may jump straight into A2 (**"Edit"**).
4. The list has a **License** column: a badge reads *Valid*, *Expires in N days* (30 days or fewer) or *Expired*, with the expiry date below it, and the **License Expiring** / **License Expired** filter chips narrow the list. The **View** modal shows the same state on its *Expires* card (red date + *Expired* badge, or amber *Expires in N days*). The driver sees the warning on their own dashboard and at every sign-in. The Add / Edit modals check the same things the registration form does while typing: e-mail format, password length (8) and confirmation, a Philippine mobile number (`+63`, `9XX XXX XXXX`, formatted as you type) and a JPG / PNG licence image of at most 2 MB; the server repeats every check.

**A5 – Suspend / Reactivate a Driver**
1. In the list, the actor clicks the suspend icon (or *reactivate* when the driver is already suspended). Only an **approved** driver can be suspended — the icon is not shown for a pending or rejected registration and the server refuses it. A suspension also takes effect on a session that is already open: the driver's next request signs them out.
2. System displays a confirmation dialog: suspending states that the driver *"will not be able to sign in, clock in or broadcast a location, and is detached from any assigned vehicle"*; an optional **Reason** field is provided.
3. Driver Manager confirms.
4. System sets `is_suspended`, records `suspended_at` and the reason, moves the driver `status` to **suspended**, **detaches any assigned vehicle**, writes a `suspended` audit-trail entry (with the reason) and confirms *"Driver suspended. They can no longer sign in."*
5. Reactiving reverses all of it (`status` back to **inactive**) and confirms *"Driver reactivated."*

**A6 – Approve or Reject a Driver Application**
1. A **pending** driver row shows a **"Review"** button instead of the ID-card icon.
2. Driver Manager opens the review modal (identity fields next to the licence image) and chooses **"Approve"** or **"Reject"**.
3. System validates the approve payload (`license_number` unique, `license_code`, `expiration_date`), assigns the generated driver code when the driver has none yet, sets `is_approved = 1` and confirms *"Driver approved. They can sign in now."*, or sets `is_rejected = 1` and confirms *"Driver registration rejected. They cannot sign in until you approve them again."*

---

### Postconditions

1. A driver record has been added, modified, suspended, reactivated or removed from the drivers list.
2. The driver record in the database has been updated accordingly (`drivers` + `users`).
3. The system notifies the actor with a success message.
4. On suspension: the account is locked out of sign-in, clock-in and location broadcast, and any assigned vehicle is released.
5. On deletion: the driver no longer appears in the list, in driver pickers, or in the driver's own sign-in.

---

### Exceptions

**E1 – Connection Error**
The list or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Duplicate License Number**
The license number entered already exists on another driver. System rejects the request with *"The license number has already been taken."* (enforced by a `unique` rule **and** a unique index on `drivers.license_number`); no record is written.

**E3 – Missing Required Fields**
Any required field is empty or fails its format rule. System lists the offending fields and performs no write.

**E4 – Database Error**
The insert/update/delete fails at the database level. System cancels the operation and notifies the actor.

**E5 – Duplicate Email**
The email address is already registered. System rejects the request on the *email* field (`unique:users,email`).

**E6 – Weak Password / Passwords Do Not Match**
The password is under 8 characters or the confirmation differs (`password` **required, min 8, confirmed**); no account is created.

**E7 – Invalid Licence Image**
The uploaded file is not an image or exceeds 2 MB (`image, max:2048`).

**E8 – Cannot Delete a Driver With a Vehicle**
The driver is still assigned to a vehicle. System refuses the deletion with *"This driver is still assigned to a vehicle. Unassign the vehicle first."* so the vehicle is not orphaned.

**E9 – Already Approved / Already Rejected**
The driver is already in that state. System refuses with *"This driver is already approved."* / *"This driver is already rejected."* On approve, the licence fields are validated first, so a missing or duplicate field is reported (**E2** / **E3**) before the already-approved check.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **Drivers added by an admin are approved immediately** (`is_approved = true`), so the approve/reject flow effectively applies only to self-registered drivers (UCN_SC_E002).
- **Suspension has no expiry and no auto-reactivation** — it must be lifted manually.
- **A suspended driver is signed out on their next request**: the `account.active` middleware re-checks the account on every authenticated request, ends the session and sends the driver to the login page (JSON calls get a 403 with the redirect). Sign-in, clock-in, status changes and location broadcast are all refused.
- **There is no "un-approve"** — the reverse of approve is commented out in the controller.
- Deleting a driver does not remove the uploaded licence image from storage when it was stored on disk.
- The list is filtered in the browser and shown **10 drivers per page** with a pager (the server still loads every driver).

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `drivers.index`, `drivers.store`, `drivers.create`, `drivers.edit`, `drivers.update`, `drivers.destroy`, `drivers.approve`, `drivers.reject`, `drivers.suspension` (all inside `role:admin|driver_manager`) |
| Controller | `app/Http/Controllers/Admin/DriverApprovalController.php::index()`, `store()`, `edit()`, `update()`, `destroy()`, `toggleSuspension()`, `approve()`, `reject()`, `showLicense()` |
| Model | `app/Models/Driver.php` (`isSuspended()`, `suspend()`, `unsuspend()`), `app/Models/User.php::driver()` |
| View | `resources/views/admin/drivers/index.blade.php` |
| Menu | `config/menu.php` — *PUJ Drivers* (admin), *Drivers* (driver_manager) |
| Middleware | `auth`, `verified`, `role:admin|driver_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php`, `tests/Feature/UcnSuspensionTest.php` |
| Related | UCN_SC_E002 (driver self-registration), UCN_SC_E009/E010 (clock in / out), UCN_SC_E013 (timekeeping), UCN_SC_E014 (violations), UCN_SC_E015 (vehicles) |

---

## UCN_SC_E013 — Manage Timekeeping

| Field                 | Value                                                                                                  |
| --------------------- | ------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN_SC_E013                                                                                            |
| **Use Case Name**     | Manage Timekeeping                                                                                     |
| **Primary Actor**     | Driver Manager (`role:driver_manager`)                                                                 |
| **Secondary Actor**   | System / Admin (read-only role boundaries)                                                              |
| **Goal**               | Allow the Driver Manager to review the driver timekeeping ledger and add a time entry (shift or leave).   |
| **Trigger**           | Driver Manager selects the **"Time Keeping"** item on the sidebar (`GET /time-keeping`).                  |
| **Preconditions**     | 1. Driver Manager is authenticated.<br>2. The actor holds the `driver_manager` role, i.e. has administrative privileges over driver records.<br>3. Driver and timekeeping data exist in the system. |
| **Supporting Actors** | Driver record, Timekeeping record                                                                       |

> **Corrections vs. the original narrative**
> - The sidebar item is **"Time Keeping"** (two words), and the add button is **"New Entry"** — the original said *"Timekeeping button"* / *"add entry button"*.
> - The precondition text in the original was garbled (*"authenticated & 2. Timekeeping data already exists has administrative privileges"*); it is split into three conditions here.
> - **E4 (clock out before clock in)** and **E5 (overlapping shifts)** were not implemented in the original narrative and are now enforced by the controller.
> - A leave entry (sick / vacation) is a first-class alternative to a shift, which the original narrative did not model.

---

### MAIN FLOW

| #   | Actor's Action                                                             | System Response                                                                                                                                       |
| --- | --------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver Manager opens the dashboard / map view.                              | System displays the dashboard with the driver-manager analytics.                                                                                        |
| 2   | Driver Manager clicks **"Time Keeping"** on the sidebar.                    | System redirects to the timekeeping management page (`driver-manager.time-keeping`).                                                                     |
| 3   |                                                                             | System displays the time entry table with columns **`#`, `Driver & Date`, `Shift`, `Hours`, `Overtime`, `Type`**, the type filter chips and a **"New Entry"** button. |

> Steps 4+ are the alternate flows (A1–A2); the main flow is "open the ledger and read it".

---

### Alternate Flow

**A1 – Create a Time Entry (shift or leave)**
1. In the list, the Driver Manager clicks **"New Entry"**.
2. System displays the **New Entry** modal: *Driver*, *Date*, *Time In*, *Time Out* — or, when **Log Leave** is toggled, a *Leave Type* choice (*Sick Leave* / *Vacation Leave*).
3. Driver Manager selects a driver, a date and (for a shift) the clock-in / clock-out times, then clicks **"Save Entry"** (**"Log Leave"** for leave).
4. System validates `driver_id` **required, exists** and `date` **required, date**; `time_in` / `time_out` are **required for a shift** and must be `H:i`.
5. System computes `hours_worked` from the two timestamps and `overtime_hours` as `max(0, hours − 8)`, stores the row and confirms *"Time entry saved successfully."*
6. A leave entry stores `sick` or `vacation` with zero hours and no timestamps.

**A2 – Filter by Type**
1. Driver Manager clicks one of the chips *All*, *Regular*, *Sick*, *Vacation*, *Overtime*.
2. System filters the table client-side to the selected type; the entry counter updates. **Overtime** means `overtime_hours > 0`.

---

### Postconditions

1. The timekeeping ledger has been re-read, or a new time entry has been added to it.
2. The timekeeping record in the database has been written (`time_keepings`).
3. The system notifies the Driver Manager with a success message.
4. `hours_worked` and `overtime_hours` are derived server-side; they are never trusted from the client.

---

### Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Missing Required Fields**
No driver or no date was supplied. System lists the offending fields and stores nothing.

**E3 – Database Error**
The insert fails at the database level. System cancels the operation and notifies the Driver Manager.

**E4 – Clock Out Is Before Clock In**
The clock-out time is earlier than (or equal to) the clock-in time. System rejects the request on *time_out* with *"The clock-out time must be later than the clock-in time."* and stores nothing.

**E5 – Overlapping Shifts**
The shift being written shares part of the day with an entry that already exists for that driver on that date. System rejects the request on *time_in* with *"This shift overlaps an existing time entry for the driver on that date."* and stores nothing.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **Auto-closed shifts need a review.** Shifts closed by the system (UCN_SC_E010) show an *Auto-closed* badge, count toward the *Needs review* filter, and have a **Mark reviewed** button (`PATCH /time-keeping/{entry}/review`, recorded in the audit trail). Review only acknowledges the entry — the hours it carries are the credited regular shift, and there is still no way to edit them (see the note below).
- **Shifts cannot span midnight.** An overnight shift (e.g. 22:00 → 06:00) is rejected by E4; the manager must split it into two dated entries.
- **No overlap check against the driver's own clock-in** (UCN_SC_E009) — a manager entry and a self-clocked entry on the same day are only blocked when they genuinely overlap in time.
- **Entries are immutable**: there is no edit or delete for a manager-entered row; only the driver's own clock-out (E010) fills in `time_out`.
- `time_in` / `time_out` are stored as free-form strings (`"08:00"` from this form, `"08:00 AM"` from the driver's clock-in), so downstream consumers must parse rather than compare as time.
- The page loads the latest 1,000 entries and filters and pages them **in the browser, 10 rows per page**, so the type filter applies to the whole list.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `driver-manager.time-keeping` (`GET /time-keeping`), `driver-manager.time-keeping.store` (`POST /time-keeping`), both inside `role:driver_manager` |
| Controller | `app/Http/Controllers/DriverManagerController.php::timeKeeping()`, `timeKeepingStore()`, `shiftOverlaps()` |
| Model | `app/Models/TimeKeeping.php`, `app/Models/Driver.php::timeKeeping()` |
| View | `resources/views/driver-manager/time-keeping.blade.php` |
| Menu | `config/menu.php` — *Time Keeping* (driver_manager) |
| Middleware | `auth`, `verified`, `role:driver_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_time_entry_where_clock_out_precedes_clock_in_is_rejected`, `test_an_overlapping_shift_is_rejected`, `test_a_second_non_overlapping_shift_is_accepted`) |
| Related | UCN_SC_E009 (Clock In), UCN_SC_E010 (Clock Out), UCN_SC_E012 (Manage Drivers) |

---

## UCN_SC_E014 — Manage Violations

| Field                 | Value                                                                                                    |
| --------------------- | -------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E014                                                                                              |
| **Use Case Name**     | Manage Violations                                                                                        |
| **Primary Actor**     | Driver Manager (`role:driver_manager`)                                                                   |
| **Secondary Actor**   | System / Admin (read-only role boundaries)                                                               |
| **Goal**               | Allow the Driver Manager to maintain the violation code table and to log one or many violations against a driver. |
| **Trigger**           | Driver Manager selects **"Violation Codes"** or **"Violation Log"** on the sidebar (`GET /violation-codes`, `GET /violations-log`). |
| **Preconditions**     | 1. Driver Manager is authenticated and authorised to issue/manage violations.<br>2. Active violation codes are configured in the database. |
| **Supporting Actors** | User/Driver record, Violation code, Violation log                                                         |

> **Corrections vs. the original narrative**
> - **The identifier changed**: the original sheet was numbered `UCN_SC_E013`, which collided with *Manage Timekeeping*. Violations are now **UCN_SC_E014**.
> - The sidebar items are **"Violation Codes"** and **"Violation Log"** (singular), not *"Violation codes or Violation logs"*.
> - The codes table columns are **`Code`, `Violation Name`, `Actions`**; the log table columns are **`#`, `Driver`, `Violation`, `Date & Location`, `Fine`**.
> - **E2 (duplicate code) is now enforced on create**, not only on update — the original only caught it when editing.
> - **E5 (cannot delete an in-use code)** and **E6 (no future-dated violation)** were added after the original narrative was written.
> - **A7 (search the log)** is now a real server-side filter by driver name / licence number **and** date range; previously the page had no search at all.

---

### MAIN FLOW

| #   | Actor's Action                                                          | System Response                                                                                                            |
| --- | ------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver Manager opens the dashboard / map view.                          | System displays the dashboard with the driver-manager analytics.                                                            |
| 2   | Driver Manager clicks **"Violation Codes"** or **"Violation Log"**.    | System redirects to the matching management page (`driver-manager.violation-codes` / `driver-manager.violations-log`).      |
| 3   |                                                                            | System displays the table (codes, or the violation log with its **search bar**: driver name / licence, *from*, *to*, **Filter**, **Clear**). |

> Steps 4+ are the alternate flows (A1–A7); the main flow is "open a table and read it".

---

### Alternate Flow

**A1 – Create a Violation Code**
1. In the code list, the Driver Manager clicks **"Add Code"**.
2. System displays the code form: *Code*, *Violation Name*, *First*, *Second*, *Third*, *Fourth+* offense amounts.
3. Driver Manager fills the form and clicks **"Create Code"**.
4. System validates all fields **required** and `code` **unique** (E2), stores the row and confirms *"Violation code successfully added!"*

**A2 – Create Multiple Violation Codes (Bulk Add)**
1. In the code list, the Driver Manager clicks **"Bulk Add"**.
2. System displays the *Bulk Add Violation Codes* modal with repeatable rows.
3. Driver Manager fills one or more rows and clicks **"Create Code"**; rows without both a code and a name are skipped.
4. System validates and inserts every complete row, then confirms per-row.

**A3 – Edit a Violation Code**
1. In the code list, the Driver Manager clicks the edit icon on a row.
2. System displays the code form pre-filled with the stored values (the button reads **"Update Code"**).
3. Driver Manager changes the values and clicks **"Update Code"**.
4. System validates the fields (`code` **unique, ignoring the current row**) and confirms *"Violation code successfully updated!"*

**A4 – Delete a Violation Code**
1. In the code list, the Driver Manager clicks the delete icon.
2. System displays the *"Delete Code?"* confirmation.
3. Driver Manager clicks **"Delete"**.
4. System deletes the row and confirms *"Violation code successfully deleted!"* — unless E5 applies.

**A5 – Log a Violation**
1. In the log, the Driver Manager clicks **"New Entry"**.
2. System displays the violation form: *Driver*, *Violation Code*, *Offense Instance*, *Fine*, *Place of Violation*, *Date*, *Time*, *Remarks*.
3. Driver Manager fills the form and clicks **"Save Entry"**.
4. System validates `user_id`, `vc_id`, `violation_instance` **required, integer, min 1**, `violation_fine` **numeric, min 0**, `place_of_violation`, `date_of_violation` **date, not in the future** (E6), `time_of_violation`, stores the row and confirms *"Violation logged!"* `remarks` is **optional** (the column is nullable), in the single and in the bulk form. On a validation error the modal stays open and each message appears under its own input (for the bulk form, under the field of the failing entry); on success the modal closes, the page refreshes and a toast confirms.

**A6 – Log Multiple Violations (Bulk Add)**
1. In the log, the Driver Manager clicks **"Bulk Add"**.
2. System displays the *Bulk Add Violations* modal with repeatable rows sharing one driver.
3. Driver Manager fills the rows and clicks **"Save Entry"**.
4. System validates every row (including the future-date rule) and confirms *"N violations logged successfully."*

**A7 – Search the Log**
1. Driver Manager types a driver name or licence number into the search box and/or picks a *from* / *to* date, then clicks **"Filter"**.
2. System narrows the log server-side to matching rows (driver name or licence `LIKE`, and `date_of_violation` within the range) and updates the entry counter; **Clear** restores the full log.
3. The *Driver* picker used by the entry modals is **not** filtered — it always lists every driver.

**A8 – Mark a Fine Paid (or Unpaid)**
1. In the **Violation Log**, each row shows a **Paid** / **Unpaid** badge and a **Mark Paid** button (**Mark Unpaid** once paid).
2. Driver Manager clicks **Mark Paid** (`PATCH /violations-log/{id}/payment`, `payment_status` = `paid`).
3. System sets `payment_status = paid` and stamps `paid_at`, confirms *"Fine marked as paid. The outstanding balance was reduced."* and writes an audit entry. The original fine stays on record (struck through) but the row now counts **₱0** and the driver's **outstanding** totals — their dashboard, the violations page and the Driver Manager dashboard — drop by that amount.
4. **Mark Unpaid** reverses it (an entry marked the wrong way is refused: *"This fine is already marked as paid."* / *"…unpaid."*). Only the Driver Manager can do this; drivers and the Admin see the status read-only.

---

### Postconditions

1. A violation code has been added, modified or removed, and/or one or many violations have been logged.
2. The `violation_codes` and `violation_logs` records in the database have been updated.
3. The system notifies the Driver Manager with a success message.
4. A logged violation is permanently attached to the driver's profile (UCN_SC_E012 A4 shows the licence and expiry alongside it).

---

### Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Duplicate Violation Code**
The code already exists. System rejects the request on *code* with *"The code has already been taken."* — on create **and** on update (the update ignores the row being edited).

**E3 – Database Error**
The insert/update/delete fails at the database level. System cancels the operation and notifies the Driver Manager.

**E4 – No Logs Found**
The log is empty (or the active filter matches nothing). System renders an empty state — *"No violations recorded yet"* / *"No violations recorded"* — instead of an empty table; it does not push the actor to add an entry.

**E5 – Cannot Delete In-Use Code**
At least one violation log references the code. System refuses the deletion with *"This violation code is in use by existing violation logs and cannot be deleted."*

**E6 – Future Incident Date**
`date_of_violation` is in the future. System rejects the request with *"The date of violation must be a date before or equal to today."* (the date inputs are also capped at today in the browser). In the bulk flow the same rule names the row, e.g. *"The violations.0.date_of_violation must be a date before or equal to today."* Nothing is stored — not in the single-entry flow and not in the bulk flow.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **No unique index on `violation_codes.code`** — uniqueness is a validation rule only, so a concurrent double submit could still slip a duplicate through.
- **The log is paginated on the server, 10 entries per page**; the driver / date filters travel with the page links (`withQueryString`). There is no ordering option, only *latest first*.
- **Rejected applications cannot be re-approved** — the reject action is one-way in the UI (`is_rejected` has no counterpart action on the drivers page).
- **The offense instance is pre-selected, not enforced.** When the Driver Manager picks a driver and a violation type, the form selects the next offense (1st, 2nd, 3rd, 4th+) from how many times that code was already logged against the driver — and, in the bulk form, from the entries above it in the same batch — and fills in the matching fine. The manager can still change it, and the server accepts 1–4 (`min:1` in the single form), so a manual override is stored as chosen.
- **The driver is emailed** when a violation is logged (one email per entry in a bulk log) and when a fine is marked paid. A driver with 3 or more violations in 30 days is flagged to the Driver Managers once per window (`php artisan drivers:flag-repeat-violators`, daily 09:00; `REPEAT_VIOLATOR_COUNT`, `REPEAT_VIOLATOR_DAYS`) and in the audit trail.
- Additional penalties are stored as free text; nothing derives them from the offense level.
- **Payment is a flag, not a ledger**: there is no partial payment, no payment date entry, no receipt — a fine is paid or it is not.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `driver-manager.violation-codes`, `violation-codes.store`, `violation-codes.update`, `violation-codes.destroy`, `driver-manager.violations-log`, `violations-log.store`, `violations-log.store-bulk` (all inside `role:driver_manager`) |
| Controller | `app/Http/Controllers/DriverManagerController.php::violationCodes()`, `storeViolationCode()`, `updateViolationCode()`, `destroyViolationCode()`, `violationsLog()`, `storeViolationLog()`, `storeViolationLogBulk()`, `getPenaltyColor()` |
| Models | `app/Models/ViolationCode.php`, `app/Models/ViolationLog.php` (`user()`, `violationCode()`) |
| Views | `resources/views/driver-manager/violation-codes.blade.php`, `resources/views/driver-manager/violations-log.blade.php` |
| Menu | `config/menu.php` — *Violation Codes*, *Violation Log* (driver_manager) |
| Middleware | `auth`, `verified`, `role:driver_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_duplicate_violation_code_is_rejected_on_create`, `test_an_in_use_violation_code_cannot_be_deleted`, `test_a_future_violation_date_is_rejected`, `test_the_violation_log_can_be_searched_by_driver_name`) |
| Related | UCN_SC_E012 (Manage Drivers), UCN_SC_E013 (Manage Timekeeping) |

---

## UCN_SC_E015 — Manage Vehicles

| Field                 | Value                                                                                                     |
| --------------------- | ----------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E015                                                                                               |
| **Use Case Name**     | Manage Vehicles                                                                                           |
| **Primary Actor**     | Maintenance Manager (`role:maintenance_manager`)                                                          |
| **Secondary Actor**   | Admin (`role:admin`) — may use the same page; System                                                       |
| **Goal**               | Allow the Maintenance Manager to review, add, edit or delete the vehicles in the fleet.                    |
| **Trigger**           | Maintenance Manager selects the **"Vehicles"** item on the sidebar (`GET /vehicles`).                       |
| **Preconditions**     | 1. Maintenance Manager is authenticated and has fleet-management permissions.<br>2. Vehicle records exist in the system. |
| **Supporting Actors** | Vehicle record, Driver record, Timekeeping record, Live map                                              |

> **Corrections vs. the original narrative**
> - **The vehicle page is restricted to the `maintenance_manager` and `admin` roles.** It used to sit in the shared authenticated group, so any signed-in role (commuter, driver, …) could open it; the routes now carry `role:maintenance_manager|admin` and the admin menu has a *Vehicles* item.
> - **Only an `active` vehicle can carry a driver.** *Inactive*, *maintenance* and *disposed* units cannot be assigned (the driver select is disabled in the form), and moving a vehicle to any of them releases its driver.
> - **An assigned vehicle cannot be deleted** (the delete icon is locked and the server answers 409): unassign the drivers first.
> - **Two drivers can share a vehicle** and take turns day by day. The form has **two driver selects** (the second optional; never the same person twice; each driver holds one vehicle only). The assignment lives in `driver_vehicle` (it replaced `vehicles.driver_id`). Who drives on which day is the **duty roster** (A6 below).
> - **E3 (cannot dispose an active vehicle)** and **E4 (cannot assign an under-maintenance vehicle)** were not enforced in the original narrative and are now blocked by the controller.
> - The postcondition *"removed from the live tracking map"* is now implemented: the map endpoint filters on vehicle status, so a unit set to **maintenance** or **disposed** drops off immediately instead of lingering until its last GPS ping ages out.
> - The postcondition *"marked Disposed, historical logs preserved"* is enforced by refusing to hard-delete a vehicle that has service history (E6).
> - A vehicle taken out of service now **loses its assigned driver** automatically.

---

### MAIN FLOW

| #   | Actor's Action                                                      | System Response                                                                                          |
| --- | -------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| 1   | Maintenance Manager opens the dashboard / map view.                  | System displays the dashboard with the maintenance-manager analytics.                                      |
| 2   | Maintenance Manager clicks **"Vehicles"** on the sidebar.            | System redirects to the vehicle management page (`vehicles.index`), showing a summary strip and the fleet table. |

> Steps 3+ are the alternate flows (A1–A5); the main flow is "open the list and read it".

---

### Alternate Flow

**A1 – Add a Vehicle**
1. In the list, the Maintenance Manager clicks **"Add Vehicle"**.
2. System displays the **Add Vehicle** modal: *Year, Brand, Model, Plate Number, VIN, Fuel Type, Tank Capacity, Assigned Driver, Location, Status, Acquisition Date, Expected Disposal Date*.
3. Maintenance Manager fills the required fields and clicks **"Add Vehicle"**.
4. System validates: `year` **required, integer, 1990–2030**; `brand`, `model`, `plate_number`, `status`, `acquisition_date` **required**; `plate_number` **unique** (E2); `status` **in `active, maintenance, inactive, disposed`**; `exp_disposal_date` **after `acquisition_date`**; `driver_id` **exists** and assignable (E4).
5. System stores the vehicle — detaching `driver_id` when the status is not roadworthy — and confirms *"Vehicle successfully added."*

**A2 – Edit a Vehicle**
1. In the list (or from the detail modal), the Maintenance Manager clicks the edit icon.
2. System displays the **Edit Vehicle** modal pre-filled with the record.
3. Maintenance Manager changes the values and clicks **"Save Changes"**.
4. System applies the same validation as A1 (uniqueness ignores the current row), applies the disposal and assignment guards (E3, E4), updates the record and confirms *"Vehicle successfully updated."*

**A3 – Delete a Vehicle**
1. In the list, the Maintenance Manager clicks the delete icon.
2. System displays the *"Delete Vehicle?"* confirmation.
3. Maintenance Manager clicks **"Delete"**.
4. System deletes the vehicle and confirms *"Vehicle successfully deleted."* — unless E6 applies, in which case the record is kept.

**A4 – Review a Specific Vehicle**
1. In the list, the Maintenance Manager clicks the view icon.
2. System displays a read-only modal with the full vehicle record.
3. The modal offers **"Edit"** to jump straight into A2.

**A6 – Keep the Duty Roster of a Shared Vehicle**
1. For a vehicle with two drivers, the Maintenance Manager opens **Duty Roster** (`GET /duty-roster`, Maintenance Manager only) and picks the vehicle; the page shows *On duty today* and 28 days at a time (previous / next / today).
2. **Generate an alternating schedule**: start date (not in the past), *Drives first*, number of days (2–120). The two drivers take turns; it replaces whatever those days held.
3. **Swap or clear a day**: the *Swap* button hands a day to the other driver, the dropdown sets or clears it by hand; **Save roster** stores the change (past days are history and are ignored). Every change is audited (*Duty roster updated*).
4. **Effect:** a driver can only clock in, switch to *Active* or broadcast the vehicle's location on a day the roster gives to them. On the co-driver's day the map says *"Today is [name]'s day on this vehicle"*; with no roster line for today: *"No duty roster is set…"*. A vehicle with one driver needs no roster. Removing a driver (or suspending / deleting them) drops them from the roster from today on and leaves the co-driver on the vehicle; each shift records the vehicle it was worked on (`time_keepings.vehicle_id`).

**A5 – Search / Filter the Fleet**
1. Maintenance Manager uses the search box, the status chips (*Active / Maintenance / Inactive*), the *All Fuel Types* and *All Vehicles* (assigned / unassigned) selects, or the sort select (*Newest First / Oldest First / Brand A–Z*).
2. System filters and re-orders the table client-side and updates the row counter.

---

### Postconditions

1. The vehicle list has been re-read, or a vehicle record has been added, modified or removed.
2. The vehicle record in the database has been updated (`vehicles`).
3. The system notifies the Maintenance Manager with a success message.
4. If the status is set to **maintenance** or **disposed**: the vehicle is **removed from the live tracking map immediately** and **any assigned driver is detached**.
5. A vehicle with preventive-maintenance records is never erased: it is kept with status **disposed**, and its maintenance records remain available for auditing.
6. A vehicle that still has a driver cannot be deleted: the driver must be unassigned first.
7. The *Assigned Driver* list offers only **approved, non-suspended** drivers who do **not already hold another vehicle** (the vehicle being edited keeps its own driver). The server enforces the same: one vehicle per driver.

---

### Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Duplicate Plate Number**
The plate number already exists. System rejects the request with *"The plate number has already been taken."* and stores nothing.

**E3 – Cannot Dispose Active Vehicle**
The Maintenance Manager tries to set the status to **disposed** while the assigned driver has an open timekeeping row for today (i.e. they are still clocked into the unit). System rejects the request on *status* with *"Cannot dispose a vehicle that a driver is currently clocked into. Clock the driver out first."* The vehicle keeps its previous status and driver.

**E4 – Assigning an Out-of-Service Vehicle**
The Maintenance Manager tries to assign a driver to a vehicle whose status is **inactive**, **maintenance** or **disposed**. System rejects the request on *driver_id* with *"A driver cannot be assigned to a vehicle with a … status."* Keeping the vehicle's *existing* driver while changing the status out of service is allowed — the driver is simply detached when the status is saved.

**E5 – Missing Required Fields**
Any required field is empty or out of range (year, status, acquisition date). System lists the offending fields and performs no write.

**E6 – Cannot Delete a Vehicle With Service History**
The vehicle already has maintenance or preventive-maintenance records. System refuses the deletion with *"This vehicle has maintenance records and cannot be deleted. Mark its status as Disposed instead."*

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **"Active vehicle" in E3 means "clocked in right now", not "on the road"** — a vehicle assigned to a driver who is not clocked in can be disposed with no further check.
- **Hard deletion is still available** for a vehicle with no maintenance history; its location history rows are removed with it (foreign keys cascade).
- **There is no restore/undelete**: a deleted vehicle's plate number is free to reuse immediately.
- **Map removal is status-driven, not history-driven**: a vehicle under maintenance disappears from the map at once, while a vehicle marked *inactive* stays visible if it is still broadcasting.
- The **"Newest First" sort is by creation date**, not by last service or last location ping.
- The vehicle list is filtered and sorted in the browser and shown **10 vehicles per page** with a pager.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `vehicles.index`, `vehicles.store`, `vehicles.update`, `vehicles.destroy`, inside `role:maintenance_manager` |
| Controller | `app/Http/Controllers/VehicleController.php::index()`, `store()`, `update()`, `destroy()`, `assertDriverAssignable()`, `assertNotDisposedWhileInUse()` |
| Models | `app/Models/Vehicle.php`, `app/Models/TimeKeeping.php` |
| Live map | `app/Http/Controllers/VehicleTrackingController.php::getActiveVehicles()` — filters on `vehicles.status` |
| View | `resources/views/maintenance-manager/vehicles.blade.php` |
| Menu | `config/menu.php` — *Vehicles* (maintenance_manager) |
| Middleware | `auth`, `verified`, `role:maintenance_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_driver_cannot_be_assigned_to_a_vehicle_under_maintenance`, `test_a_vehicle_with_a_clocked_in_driver_cannot_be_disposed`, `test_setting_a_vehicle_to_maintenance_detaches_its_driver`, `test_a_vehicle_under_maintenance_is_not_shown_as_active`, `test_a_commuter_cannot_reach_the_vehicle_management_page`, `test_a_vehicle_with_maintenance_records_cannot_be_deleted`) |
| Related | UCN_SC_E016 (Maintenance Schedule), UCN_SC_E017 (Maintenance Tasks), UCN_SC_E009/E010 (clock in / out), UCN_SC_E003 (Track PUJ) |
--- Update Note ---
Vehicle issues (E3/E4 map removal, driver detachment, disposal guards) are now enforced and working; document reflects current working state.

---

## UCN_SC_E016 — Manage Maintenance Schedule

| Field                 | Value                                                                                                        |
| --------------------- | -------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E016                                                                                                  |
| **Use Case Name**     | Manage Maintenance Schedule (Preventive Maintenance)                                                         |
| **Primary Actor**     | Maintenance Manager (`role:maintenance_manager`)                                                              |
| **Secondary Actor**   | System / Admin (read-only role boundaries)                                                                    |
| **Goal**               | Allow the Maintenance Manager to review the preventive-maintenance schedule of a vehicle and log a service against it. |
| **Trigger**           | Maintenance Manager selects the **"Preventive Maintenance"** item on the sidebar (`GET /preventive-maintenance`). |
| **Preconditions**     | 1. Maintenance Manager is authenticated and registered to the system.<br>2. Vehicle records exist in the system.<br>3. Maintenance task data exists in the database. |
| **Supporting Actors** | Vehicle, Maintenance task, Preventive maintenance record                                                       |

> **Corrections vs. the original narrative**
> - **The identifier changed**: the original sheet was numbered `UCN_SC_E015`, which collided with *Manage Vehicles*. The maintenance schedule is now **UCN_SC_E016**.
> - The page does **not** show a "list of maintenance services to be logged"; it is a per-vehicle schedule board: a vehicle picker plus one card per maintenance task showing *last serviced*, *odometer*, *cost* and the computed **next due date / next due odometer**.
> - The buttons are **"Log"** (or **"Update"** once logged) per task card and **"Save Log"** in the modal — not *"Log button"* / *"Save Log button"* on a list row.
> - **E2 (invalid odometer reading)** is now enforced: a reading lower than the last recorded one for that vehicle is rejected.
> - The *vehicle maintenance log* (formerly its own use case, numbered E025 until the October 2026 renumbering — see the UCN README) is **back, as a page of its own** (**"Maintenance Logs"** on the Maintenance Manager's sidebar, `GET /maintenance-logs`, Maintenance Manager only — the Admin and every other role get 403) rather than a use case of its own: the schedule keeps only the latest service per task, so every service logged is also written to `vehicle_maintenance_logs` and listed there per vehicle (newest first, 10 per page). The Preventive Maintenance page itself no longer shows the log. The fleet cost/kilometre analytics and the reports still read the schedule rows.

---

### MAIN FLOW

| #   | Actor's Action                                                                   | System Response                                                                                                       |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| 1   | Maintenance Manager opens the dashboard / map view.                               | System displays the dashboard with the maintenance-manager analytics.                                                   |
| 2   | Maintenance Manager clicks **"Preventive Maintenance"** on the sidebar.           | System redirects to the preventive-maintenance page, showing a summary strip and the schedule of the selected vehicle.   |
| 3   |                                                                                   | System lists one card per maintenance task with *task name*, *interval*, *last service date*, *last service odometer*, *last service cost*, *comments*, and the computed **next due date / next due odometer**, each with a **"Log"** / **"Update"** button. |

> Steps 4+ are the alternate flows (A1–A3); the main flow is "read the schedule".

---

### Alternate Flow

**A1 – Log a Scheduled Service**
1. In the schedule, the Maintenance Manager clicks **"Log"** (or **"Update"**) on a task card.
2. System displays the **Log Service** modal: *Odometer (km)*, *Date*, *Cost*, *Performed by* and *Invoice no.* (both optional), *Comments*.
3. Maintenance Manager fills the fields and clicks **"Save Log"**.
4. System validates `vehicle_id` **required, exists**, `task_id` **required, exists**, `last_service_odo` **required, integer, min 0**, `last_service_date` **required, date**, `last_service_cost` **required, numeric, min 0**, `comments` **nullable**, `performed_by` **nullable, max 255**, `invoice_number` **nullable, max 100**.
5. System checks the odometer against the last reading recorded for that vehicle (E2), then — in one transaction — **upserts** the `preventive_maintenances` row for *(vehicle, task)* **and appends a `vehicle_maintenance_logs` row** for the service.
6. System confirms *"Preventive maintenance logged successfully!"*

**A2 – Log a Service for a Different Vehicle**
1. The Maintenance Manager uses the **vehicle picker** at the top of the page.
2. System reloads the schedule for the newly selected vehicle, keeping the same table rows and picking that vehicle's own logged values.
3. Steps 4–6 of A1 then apply to the newly selected vehicle.

---

### Postconditions

1. The Maintenance Manager has reviewed the schedule and/or logged a service.
2. The service record in the database has been written (`preventive_maintenances`).
3. The system notifies the Maintenance Manager with a success message.
4. The task card now shows the new last-service values and recalculates the next due date and odometer from the task's `miles_between_service` / `months_between_service`.
5. The service is immediately visible in the dashboard's *Service History* tab and in the fleet cost-per-kilometre analytics.

---

### Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Invalid Odometer Reading**
The entered odometer is lower than the last reading recorded for that vehicle (across its scheduled services). System rejects the request on *last_service_odo* with *"The odometer reading cannot be lower than the last recorded reading of N km."* and changes nothing.

**E3 – Missing Required Service Details**
Any required field is empty or malformed. System lists the offending fields and stores nothing.

**E4 – Database Error**
The insert or update fails at the database level. System cancels the operation and notifies the Maintenance Manager.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **The summary is per vehicle, per calendar year** — there is no cross-vehicle roll-up page and no year picker (the current year is always used).
- **The schedule is per task, not per service**: logging a task again **overwrites** that task's last-service row. The history is kept in the **Maintenance Logs** page (`vehicle_maintenance_logs`), which is append-only: there is no edit or delete for a log entry, and a log entry outlives the schedule row it came from (`maintenance_id` is cleared, not cascaded). Services that were on the schedule when the log was re-introduced were copied in as its first entries; earlier overwritten services cannot be recovered.
- **Next-due values are computed on the fly** in the view; nothing is persisted, so changing a task's interval retroactively changes every vehicle's projected due date.
- The vehicle picker falls back to the first vehicle when the requested one no longer exists, and renders an empty board when the fleet is empty.
- **Overdue and due-soon services are pushed, not just displayed.** A service is *due soon* within `MAINTENANCE_DUE_SOON_DAYS` (7) days or `MAINTENANCE_DUE_SOON_KM` (500) km, and *overdue* once its date has passed or the vehicle's latest logged odometer reading has reached its next-due reading (the system has no live odometer, so a later service on the same vehicle supplies the reading). Tasks with no interval are never due. `php artisan maintenance:due-alerts` (daily, 07:00) emails every Maintenance Manager a digest, and overdue vehicles are flagged on the Vehicles page and the dashboard. With `MAINTENANCE_BLOCK_ACTIVE_WHEN_OVERDUE=true` (off by default) a vehicle with an overdue service cannot be set back to Active until the service is logged. Emails need the scheduler running (`schedule:run` from cron).

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `maintenance-manager.preventive-maintenance` (`GET /preventive-maintenance`), `maintenance-manager.preventive-maintenance.store` (`POST /preventive-maintenance`), all inside `role:maintenance_manager` |
| Controller | `app/Http/Controllers/MaintenanceManagerController.php::preventiveMaintenance()`, `preventiveMaintenanceStore()` |
| Models | `app/Models/PreventiveMaintenance.php`, `app/Models/MaintenanceTask.php` |
| View | `maintenance-manager/preventive-maintenance.blade.php` |
| Menu | `config/menu.php` — *Preventive Maintenance* (maintenance_manager) |
| Middleware | `auth`, `verified`, `role:maintenance_manager` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_an_odometer_reading_below_the_last_recorded_one_is_rejected`, `test_an_odometer_reading_above_the_last_recorded_one_is_accepted`), `tests/Feature/BugFixRegressionTest.php` (`test_logging_a_preventive_service_feeds_the_fleet_summary`) |
| Related | UCN_SC_E015 (Manage Vehicles), UCN_SC_E017 (Manage Maintenance Tasks), UCN_SC_E020 (System Reports — maintenance report) |

---

## UCN_SC_E017 — Manage Maintenance Tasks

| Field                 | Value                                                                                        |
| --------------------- | ---------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E017                                                                                  |
| **Use Case Name**     | Manage Maintenance Tasks                                                                     |
| **Primary Actor**     | Maintenance Manager (`role:maintenance_manager`)                                              |
| **Secondary Actor**   | System / Admin (read-only role boundaries)                                                    |
| **Goal**               | Allow the Maintenance Manager to add, edit or delete the maintenance tasks / services used across the fleet. |
| **Trigger**           | Maintenance Manager selects the **"Maintenance Tasks"** item on the sidebar (`GET /maintenance-tasks`). |
| **Preconditions**     | 1. Maintenance Manager is authenticated and registered to the system.<br>2. Maintenance task data exists in the database. |
| **Supporting Actors** | Maintenance task, Preventive maintenance                                                       |

> **Corrections vs. the original narrative**
> - The sidebar item is **"Maintenance Tasks"** (plural), not *"Maintenance Task"*.
> - **A2 and A3 were copy/paste errors in the original** — they were titled *"wants to edit a vehicle"* / *"wants to delete a vehicle"* in a use case about tasks. They are corrected here to **edit** and **delete a task**.
> - The commit buttons are **"Create Task"** (add) and **"Update Task"** (edit), not *"Save Changes"*.
> - The table columns are **`Task Performed`, `Km Interval`, `Month Interval`, `Actions`**.

---

### MAIN FLOW

| #   | Actor's Action                                                        | System Response                                                                                       |
| --- | ---------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| 1   | Maintenance Manager opens the dashboard / map view.                    | System displays the dashboard with the maintenance-manager analytics.                                    |
| 2   | Maintenance Manager clicks **"Maintenance Tasks"** on the sidebar.     | System redirects to the maintenance tasks page (`maintenance-manager.maintenance-tasks`), showing a summary strip and the task table. |
| 3   |                                                                            | System displays the task table with columns **`Task Performed`, `Km Interval`, `Month Interval`, `Actions`** and an **"Add Task"** button. |

> Steps 4+ are the alternate flows (A1–A3); the main flow is "open the list and read it".

---

### Alternate Flow

**A1 – Add a Maintenance Task to the List**
1. In the list, the Maintenance Manager clicks **"Add Task"**.
2. System displays the task modal: *Task Performed*, *Km Interval*, *Month Interval*.
3. Maintenance Manager inputs the required fields and clicks **"Create Task"**.
4. System validates `tasks_performed` **required, string, max 255**; `miles_between_service` and `months_between_service` **nullable, integer**.
5. System stores the task and confirms *"Task successfully added!"*

**A2 – Edit a Maintenance Task**
1. In the list, the Maintenance Manager clicks the edit icon on a task row.
2. System displays the **Edit Task** modal pre-filled with the stored values.
3. Maintenance Manager updates the fields and clicks **"Update Task"**.
4. System applies the same validation as A1, updates the record and confirms *"Task successfully updated!"*

**A3 – Delete a Maintenance Task**
1. In the list, the Maintenance Manager clicks the delete icon on a task row.
2. System displays the *"Delete Task?"* confirmation.
3. Maintenance Manager clicks **"Delete"**.
4. System deletes the task and confirms *"Task successfully deleted!"*

---

### Postconditions

1. The Maintenance Manager has successfully added, edited or deleted a task or service.
2. The service record in the database has been updated (`maintenance_tasks`).
3. The system notifies the Maintenance Manager with a success message.
4. A task's `miles_between_service` / `months_between_service` drive the projected next-due odometer and date on the preventive-maintenance board (UCN_SC_E016).

---

### Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Missing Required Task Details**
`tasks_performed` is empty or over 255 characters. System lists the offending fields and stores nothing.

**E3 – Database Error**
The insert/update/delete fails at the database level. System cancels the operation and notifies the Maintenance Manager.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **Task names are not unique** — the same task can be added twice and will then appear twice on every vehicle's schedule.
- **Deleting a task cascades** to its preventive-maintenance rows, so the cost/km figures for that task disappear from the fleet analytics.
- **Intervals are optional**, so a task without `miles_between_service` / `months_between_service` produces no projected due date.
- There is no deactivation: a task in use must be deleted (with the data loss above) or left in place.
- The task list is paginated on the server, **10 tasks per page**; the summary cards still count the whole catalogue.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `maintenance-manager.maintenance-tasks` (`GET /maintenance-tasks`), `…tasks.store` (`POST`), `…tasks.update` (`PUT /maintenance-tasks/{task}`), `…tasks.destroy` (`DELETE`), all inside `role:maintenance_manager` |
| Controller | `app/Http/Controllers/MaintenanceManagerController.php::maintenanceTasks()`, `maintenanceTasksStore()`, `maintenanceTasksUpdate()`, `maintenanceTasksDestroy()` |
| Model | `app/Models/MaintenanceTask.php` |
| View | `resources/views/maintenance-manager/maintenance-tasks.blade.php` |
| Menu | `config/menu.php` — *Maintenance Tasks* (maintenance_manager) |
| Middleware | `auth`, `verified`, `role:maintenance_manager` |
| Related | UCN_SC_E015 (Manage Vehicles), UCN_SC_E016 (Manage Maintenance Schedule), UCN_SC_E020 (System Reports — maintenance report) |

---

## UCN_SC_E018 — Manage Commuters

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

### MAIN FLOW

| #   | Actor's Action                                                        | System Response                                                                                                  |
| --- | ---------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| 1   | Admin opens up the dashboard on the map.                              | System displays the dashboard with the admin analytics.                                                             |
| 2   | Admin clicks the **"PUJ Commuters"** button on the sidebar.           | System redirects to the commuter management page (`commuters.index`), showing the registered-account count.       |
| 3   |                                                                            | System displays the commuter table with columns **`#`, `Commuter`, `Status`, `Registered`, `Balance`, `Actions`**, a search box, filter chips (*All / Verified / Pending / Active / Suspended*) and an **"Add Commuter"** button. |

> Steps 4+ are the alternate flows (A1–A5); the main flow is "open the list and read it".

---

### Alternate Flow

**A1 – Add a Commuter**
> UI is modal-based; `create.blade.php` / `edit.blade.php` do not exist.
1. In the list, the Admin clicks the **"Add Commuter"** button.
2. System displays the **Add Commuter** modal: *Email*, *Password*, *Confirm Password*, and a **Mark email as verified** toggle.
3. Admin inputs all required fields and clicks **"Create Commuter"**.
4. The modal checks the input while typing (e-mail format, password of 8+ characters, matching confirmation) and sends nothing while a field is invalid. System validates `email` **required, email, unique:users** and `password` **required, min 8, confirmed** (a mismatch reads *"The password confirmation does not match."*); creates the User with role `commuter` (verified or pending per the toggle) and confirms *"Commuter account created."*

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
4. System removes the account and confirms *"Commuter removed."* — unless E8 applies. A commuter with **no payments or top-ups** is deleted. A commuter **with** them is **anonymised** instead (personal data, roles, tokens and sessions removed; the email becomes `deleted-<id>@deleted.invalid`; the wallet, payments and top-ups are kept) and the confirmation adds *"Their payments and top-ups were kept for the records."* The commuter disappears from this list either way, and an `anonymised` or `deleted` audit entry names the Admin.

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

### Postconditions

1. A commuter account has been added, edited, suspended, reactivated or removed.
2. The commuter record in the database has been updated (`users`).
3. The system notifies the Admin with a success message.
4. A **suspended** commuter cannot sign in; their wallet balance and receipts remain intact and auditable.
5. A commuter whose wallet still holds a balance is **never deleted or anonymised** by this flow.
6. A commuter's payments and top-ups are **never destroyed** by this flow: the database refuses it (`RESTRICT` foreign keys) and the application anonymises instead.

---

### Exceptions

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
The two fields differ. System displays *"The password confirmation does not match."* (rendered under the password field) and performs no write.

**E8 – Cannot Delete a Commuter With a Balance**
The commuter's wallet holds more than ₱0. System refuses the deletion with *"Cannot delete commuter with a balance of PHP N. Settle the wallet first."*

**E9 – Self-Referential Action**
The Admin deletes or suspends their own account. System refuses with *"You cannot delete/suspend your own account."*

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **Suspension has no expiry and no automatic reactivation**, and there is no reason field or note recorded (unlike the driver suspension, which takes an optional reason).
- **A suspended commuter is signed out on their next request** (`account.active` middleware), not only refused at sign-in.
- **There is no password reset from this screen** — the admin sets a password directly, and the commuter is not notified.
- The details page (A5) exposes fare and top-up totals but offers no link to the individual receipts.
- The commuter list is filtered in the browser and shown **10 commuters per page** with a pager. The detail page (A5) pages the commuter's trips and top-ups separately, 10 per page each.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `commuters.index`, `commuters.store`, `commuters.update` (`PUT /commuters/{user}`), `commuters.show` (`GET /commuters/{user}`), `commuters.suspension` (`PATCH /commuters/{user}/suspension`), `commuters.destroy`, all inside `role:admin` |
| Controller | `app/Http/Controllers/Admin/CommuterController.php::index()`, `store()`, `update()`, `show()`, `toggleSuspension()`, `destroy()`, `assertCommuter()` |
| Models | `app/Models/User.php` (`isSuspended()`, `suspend()`, `unsuspend()`), `app/Models/Wallet.php`, `app/Models/Payment.php` |
| Views | `resources/views/admin/commuters/index.blade.php`, `resources/views/admin/commuters/show.blade.php` (modal-based UI for add/edit; no separate `create.blade.php` / `edit.blade.php` views) |
| Menu | `config/menu.php` — *PUJ Commuters* (admin) |
| Middleware | `auth`, `verified`, `role:admin` |
| Schema | `2026_10_03_090000_add_suspension_to_users_and_unique_license.php` (`users.is_suspended`, `users.suspended_at`) |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_commuter_with_a_balance_cannot_be_deleted`, `test_a_suspended_commuter_cannot_sign_in`, `test_a_suspended_commuter_can_be_reactivated`, `test_the_commuter_details_page_renders`) |
| Related | UCN_SC_E001 (Login), UCN_SC_E002 (Create Account), UCN_SC_E005/E006/E007 (top-up, pay fare, payment history) |

---

## UCN_SC_E019 — Manage Fare Rates

| Field                 | Value                                                                                          |
| --------------------- | ----------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E019                                                                                    |
| **Use Case Name**     | Manage Fare Rates                                                                              |
| **Primary Actor**     | Admin (`role:admin`)                                                                           |
| **Secondary Actor**   | System (PDF parser, Python runtime)                                                             |
| **Goal**               | Allow the Admin to upload the official fare guide and to edit or upload the fare rates in the system. |
| **Trigger**           | Admin clicks the **"Fare Rates"** item on the sidebar (`GET /fares`).                            |
| **Preconditions**     | 1. Admin is authenticated and registered to the system.<br>2. Fare rate data already exists in the database. |
| **Supporting Actors** | Fare, Fare rate, PDF extraction script                                                         |

> **Corrections vs. the original narrative**
> - The sidebar item is **"Fare Rates"**; the upload control is a **"Select File"** file input next to an **"Upload"** button (a `PUT` multipart form), not a plain "select file button".
> - The edit button is **"Edit Rates"** and the commit button is **"Save Changes"**, both in the fares toolbar; the original called the entry point *"Edit Rates button"* and left the save button unnamed.
> - **E2 (rates empty) and E4 (rate cannot be zero or negative)** were not implemented in the original narrative and are now validated before any write.
> - The uploaded document must be a **PDF** (`file, mimes:pdf`); the parser (`extractPdf.py`) is run through the project virtualenv and its JSON output is what populates the rate tiers.

---

### MAIN FLOW

| #   | Actor's Action                                                    | System Response                                                                                     |
| --- | ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| 1   | Admin opens up the dashboard on the map.                           | System displays the dashboard with the admin analytics.                                                |
| 2   | Admin clicks the **"Fare Rates"** item on the sidebar.             | System redirects to the Fare Rates page (`fares.index`), showing the tier count and the min/max fare summary strip. |
| 3   |                                                                            | System displays the rate table with columns **Distance (km)**, **Regular** and **Discount**, the **"Edit Rates"** button and the PDF upload form. |

> Steps 4+ are the alternate flows (A1–A2); the main flow is "open the table and read it".

---

### Alternate Flow

**A1 – Upload Fare Rates to the System**
1. On the page, the Admin selects a file with **"Select File"** — an official **LFTFRB fare guide (PDF)** — then presses **"Upload"**.
2. System validates the upload: `fare` **required, file, mimes:pdf**.
3. System stores the file and runs the extraction script (`resources/scripts/extractPdf.py`) inside the project virtualenv, then parses the returned JSON.
4. Inside a transaction, System creates the **Fare** record and inserts the parsed tiers (`fare_rates`: km, regular, discount) for the 1–25 km and 27–51 km bands, then confirms *"File uploaded successfully!"*

**A2 – Edit One or More Fare Rates**
1. On the page, the Admin clicks **"Edit Rates"**.
2. System switches the Regular and Discount cells into editable number inputs and reveals the *"Editing Mode"* save bar.
3. Admin edits one or multiple fare rates and clicks **"Save Changes"**.
4. System validates the whole payload (E2, E4) and, in one transaction, updates each `fare_rates` row, then confirms *"Rates updated successfully"*.

---

### Postconditions

1. The Admin has successfully uploaded or modified the fare rates.
2. The fare rate record in the database has been updated (`fares` and `fare_rates`).
3. The system notifies the Admin with a success message.
4. The current rate set is what fare pricing and the map's rate display read from.

---

### Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Rates Empty**
The save was submitted with no rate rows at all (nothing to persist). System rejects the submission with *"There are no rates to save."* and performs no write.

**E3 – Database Error**
The insert/update fails at the database level. System cancels the operation and notifies the Admin.

**E4 – Rate Cannot be Zero or Negative**
A submitted Regular or Discount value is `0` or below. System rejects the submission with *"A fare rate must be greater than zero."* / *"A discount rate must be greater than zero."*, leaving every previous value untouched.

**E5 – Document File Is Required**
No file was selected, or the selected file is not a PDF. System rejects the upload with the corresponding validation message and stores nothing.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **The extracted tiers are hard-coded to two bands** (rows 1–25 and 27–51 of the parser output). A fare guide with a different layout is rejected at parse time rather than supported.
- **Validation is all-or-nothing**: one invalid row blocks the entire save, and the admin must re-enter every value.
- **The extraction script runs synchronously** and shells out to the project virtualenv; a slow or missing interpreter fails the whole upload.
- Rates are decimals without rounding rules, and there is no per-tier effective date — uploading a new guide replaces the active rate set wholesale.
- Uploading does not remove the previous fare set; multiple `fares` rows can coexist and only the latest is used.

---

### Implementation References

| Element | Location |
|---|---|
| Controller | `app/Http/Controllers/FareController.php::index()`, `view()`, `upload()`, `bulkUpdate()` |
| Models | `app/Models/Fare.php`, `app/Models/FareRate.php` |
| Parser | `resources/scripts/extractPdf.py`, run via `venv/bin/python3` |
| View | `resources/views/admin/fares/index.blade.php` (validation banner added for E2/E4) |
| Menu | `config/menu.php` — *Fare Rates* (admin) |
| Middleware | `auth`, `verified`, `role:admin` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_zero_or_negative_fare_rate_is_rejected`, `test_an_empty_rate_submission_is_rejected`, `test_a_valid_rate_submission_is_saved`) |
| Related | UCN_SC_E006 (Pay Fare — prices a ride from the active rate set), UCN_SC_E020 (System Reports — financial report) |

---

## UCN_SC_E020 — View System Reports

| Field                 | Value                                                                                                 |
| --------------------- | ------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E020                                                                                           |
| **Use Case Name**     | View System Reports                                                                                    |
| **Primary Actor**     | Admin (`role:admin`)                                                                                  |
| **Secondary Actor**   | System (aggregation engine, CSV writer)                                                               |
| **Goal**               | Allow the Admin to review the system reports for a selected report type and date range, and to export the result. |
| **Trigger**           | Admin clicks the **"Reports & Analytics"** item on the sidebar (`GET /reports`).                        |
| **Preconditions**     | 1. Admin is authenticated and registered to the system.<br>2. Enough data has been generated throughout the system for the selected report type. |
| **Supporting Actors** | Payments, top-ups, drivers + timekeeping, preventive maintenance records                       |

> **Corrections vs. the original narrative**
> - The sidebar item is **"Reports & Analytics"**, not *"Reports & Analytics button"* phrased as *"the 'Reports & Analytics' button"* — the wording matched, but the item is only present on the Admin menu.
> - **The export produces a CSV file, not an Excel file.** The original narrative described an *"Export to excel"* button that "turns the data into an excel file"; the implemented endpoint streams `text/csv` with a `.csv` filename and the button reads **"Export CSV"**. The narrative is corrected here rather than the code.
> - The report type selector offers **three** report types — *Financial Overview*, *Driver Performance*, *Maintenance & Fleet* — which the original did not enumerate.
> - **E2 (aggregation timeout), E3 (no data found) and E4 (data sync delay)** were not implemented in the original narrative; all three now surface as notices on the report page.

---

### MAIN FLOW

| #   | Actor's Action                                                                | System Response                                                                                                        |
| --- | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------ |
| 1   | Admin opens up the dashboard on the map.                                     | System displays the dashboard with the admin analytics.                                                                   |
| 2   | Admin clicks the **"Reports & Analytics"** item on the sidebar.               | System redirects to the System Reports page (`reports.generate`), displaying the list of available reports, the report-type selector, the *from* / *to* date inputs, the **"Generate Report"** button and the **"Export CSV"** button. |
| 3   | Admin selects a report type & date range and clicks **"Generate Report"**.   | System aggregates the data for the selected type and range and renders the report: **Financial Overview** (revenue, top-ups, transaction count, daily revenue chart), **Driver Performance** (total/active drivers, top 5 performers by hours, status distribution) or **Maintenance & Fleet** (pending/overdue/completed counts, upcoming schedule table; *completed* counts the preventive-maintenance records saved in the selected range). Top-up totals count **completed** top-ups only. |

---

### Alternate Flow

**A1 – Switch to a Different Report Type or Date Range**
1. On the page, the Admin selects a different report type and/or date range and clicks **"Generate Report"**.
2. System re-runs the aggregation for the new selection and re-renders the report; the **"Export CSV"** button stays disabled until the new report has been generated.

**A2 – Export the Report**
1. After generating a report, the Admin clicks **"Export CSV"**.
2. System streams the report data for the current type and range as a **CSV file** (`Content-Type: text/csv`) named `smartcommute_<type>_report_<YYYY-MM-DD>.csv`.
3. The Admin's browser downloads the file. The CSV columns differ per type — e.g. financial: *Date, Type, Reference ID, Amount, User* (fares and wallet top-ups); driver: *Driver Name, Status, Total Hours Worked*; maintenance: *Vehicle Plate, Task, Due Date, Status*.

---

### Postconditions

1. The Admin has successfully reviewed the system analytics.
2. The system has successfully generated the reports based on the Admin's preference.
3. The system notifies the Admin with a success message.
4. An export downloads a CSV file that mirrors exactly what was rendered for the current type and range.

---

### Exceptions

**E1 – Connection Error**
The page or the aggregation fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Data Aggregation Timeout**
The aggregation cannot complete (the database takes too long / the query fails). System cancels the run and shows *"The database is taking too long to generate this report. Please try a shorter date range."* with no partial report.

**E3 – No Data Found**
The selected report type and date range contain nothing to report. System still renders the page and shows *"No data found for the selected report type and date range."* above zeroed figures.

**E4 – Data Sync Delay**
The newest transaction in the ledger is more than a day old, so the analytics are not fully up to date. System warns *"Some of the data may not be included in the analytics — the latest recorded transaction is N day(s) old."*

**E5 – Invalid Date Range**
The *from* date is later than the *to* date. System rejects the request with *"The start date must be before the end date."* and renders no report.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **The export is CSV only.** There is no `.xlsx` writer; opening the file in Excel works but loses column formatting, and a multi-sheet workbook is not produced.
- **Only the financial report is date-filtered end to end** — the driver report uses all-time totals for the driver cards and the maintenance report ignores the range for the pending/overdue buckets (they are computed against *now*, not against *end date*).
- **E2 is a catch-all**: any exception during aggregation (including a genuine bug) is reported to the log and shown to the Admin as an aggregation timeout, so a real error can be mistaken for slowness.
- **E4 is derived from the payments ledger only**; a system with no payments never raises the warning even if other data is stale.
- Reports are generated on every page load from live queries — there is no caching, snapshotting or scheduled export.
- **Exports are audited**: each CSV export writes a `report_exported` entry (who, which report, the date range) that the Admin can find on the Activity Log page (UCN_SC_E028). Generating a report on screen is a read and writes nothing.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `reports.generate` (`GET /reports`), `reports.export` (`GET /reports/export`), both inside `role:admin` |
| Controller | `app/Http/Controllers/ReportController.php::generate()`, `buildReport()`, `assertValidRange()`, `reportIsEmpty()`, `syncLagMinutes()`, `export()` |
| Models | `app/Models/Payment.php`, `app/Models/TopupHistory.php`, `app/Models/Driver.php`, `app/Models/PreventiveMaintenance.php` |
| View | `resources/views/admin/reports.blade.php` (E2 / E3 / E4 notice banners) |
| Menu | `config/menu.php` — *Reports & Analytics* (admin) |
| Middleware | `auth`, `verified`, `role:admin` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_report_over_an_empty_window_reports_no_data`, `test_an_inverted_date_range_is_rejected`) |
| Related | UCN_SC_E019 (Manage Fare Rates — financial figures), UCN_SC_E016 (Maintenance Schedule — maintenance figures), UCN_SC_E013 (Timekeeping — driver hours) |

---

## UCN_SC_E021 — Share Vehicle Location (Driver Broadcast)

| Field                 | Value                                                                                                                                                        |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E021                                                                                                                                                  |
| **Use Case Name**     | Share Vehicle Location (Driver Broadcast)                                                                                                                   |
| **Primary Actor**     | Driver (signed in, holding an approved `Driver` profile, assigned a vehicle)                                                                                 |
| **Secondary Actor**   | System, Push channel (Pusher/Echo)                                                                                                                          |
| **Goal**               | Publish the driving vehicle's position so guests and commuters can track it on the map (UCN_SC_E003), while keeping the exact coordinates private.          |
| **Trigger**           | The driver's map view receives a browser geolocation fix for their assigned vehicle.                                                                          |
| **Preconditions**     | 1. Driver is signed in with the `driver` role and has a `Driver` profile.<br>2. A vehicle is assigned to that driver (`vehicles.driver_id`).<br>3. The account is not suspended.<br>4. The driver's device has location permission and a network connection. |
| **Supporting Actors** | `vehicle_locations` (current position), `vehicle_location_histories` (breadcrumb), `vehicles.is_active`, Pusher channel `vehicle-locations`                        |

> **Why this document exists.** UCN_SC_E003 (*Track PUJ*) documents the **read** side of live tracking from the guest/commuter's point of view. This document covers the **write** side — the authenticated driver continuously publishing the assigned vehicle's position — which had no narrative of its own. The privacy rules referenced in E003 are implemented here and are summarised in step 8 below.

---

### MAIN FLOW

| #   | Actor's Action                                                          | System Response                                                                                                                                                                                     |
| --- | ------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver opens the map view with their vehicle.                            | System renders the map for a driver and exposes their assigned vehicle id; the browser starts watching `geolocation`.                                                                               |
| 2   | The browser obtains a position fix.                                     | *(no system response — the fix is read client-side)*                                                                                                                                               |
| 3   |                                                                          | The browser **throttles to one broadcast every 2 seconds** and POSTs `{ vehicle_id, user_id, latitude, longitude, speed, accuracy, timestamp }` to `/track/vehicle/broadcast` with the CSRF token.     |
| 4   |                                                                          | System validates the payload: `vehicle_id` **required, string**, `user_id` **required, integer**, `latitude` / `longitude` **required, numeric**, `speed` / `accuracy` **nullable, numeric**, `timestamp` **nullable, integer**. |
| 5   |                                                                          | System resolves the actor and refuses the request unless they are signed in (**E2**), hold the `driver` role and have a driver profile (**E2**), are not suspended (**E4**), and the vehicle is assigned to them **and is `active`** (**E3**). A driver with no vehicle, or whose vehicle is inactive / under maintenance, is refused with 403 and the browser stops sharing immediately. |
| 6   |                                                                          | System checks whether a row for `(vehicle_id, timestamp)` already exists. If it does, it makes no write and answers `{ success: true, duplicate: true }` (**A1**).              |
| 7   |                                                                          | System upserts the current position into `vehicle_locations` (the broadcast event also carries the driver's `driver_status`, which colours the marker on other users' maps) (`user_id`, `latitude`, `longitude`, `speed`, `accuracy`, `last_update = now`) and marks the vehicle `is_active = true`.       |
| 8   |                                                                          | System **fires `LocationUpdated`**, which obfuscates the coordinates to a random point within a **200 m radius** and publishes `{ vehicleId, lat, lng, privacy_radius, speed, accuracy, timestamp, user_id }` on the Pusher channel `vehicle-locations` as `vehicle-location-updated`. **The exact coordinates are never published.** |
| 9   |                                                                          | System appends a breadcrumb row to `vehicle_location_histories` and answers `200 { success: true, duplicate: false, location_id }`.                                                                       |
| 10  |                                                                          | Guests and commuters receive the push event and move that PUJ's marker on their map (UCN_SC_E003 step 3); the "active" freshness window is 5 minutes.                                             |

---

### Alternate Flow

**A1 – Duplicate Broadcast (Retry / Replay)**
1. The browser re-sends a payload whose `timestamp` has already been recorded for that vehicle.
2. System logs the duplicate, performs **no** write and no event, and answers `200 { success: true, duplicate: true, message: "Broadcast already recorded at this timestamp." }` — so a retried request is safe and idempotent.

**A2 – Temporary Failure (Retry Queued)**
1. The database write throws.
2. System logs the failure, dispatches `RetryBroadcastLocation` with a 5-second delay and answers `409 { success: false, retry: true, retry_after: 5, message: "Temporary failure – retrying in 5 seconds." }` and includes a `Retry-After` header.
3. The queued job re-publishes the `LocationUpdated` event **once** and then stops, so the driver's last known position still reaches the map.

**A3 – No Vehicle Assigned**
1. The driver opens the map without an assigned vehicle.
2. The browser never broadcasts (`isBroadcastingDriver` is false), and any manual call is refused (**E3**). The driver simply does not appear on the map.

**A4 – Developer Markers (local environment only)**
1. In the `local` environment only, an admin can add / toggle / remove synthetic map markers from the map view.
2. System stores them as `dev_markers` scoped to that admin and renders them alongside the live PUJs. Outside `local` the feature is compiled out entirely (the marker list is empty), so it cannot leak into production.

---

### Postconditions

1. The vehicle's current position in `vehicle_locations` has been refreshed (`last_update = now`).
2. The vehicle is flagged `is_active`, and the scheduled `vehicle:offline-check` (every minute) flips it back once no broadcast has arrived for 5 minutes.
3. A breadcrumb row has been appended to `vehicle_location_histories`.
4. Guests and commuters see the vehicle move on the map, at an **obfuscated** position (200 m radius) with `privacy_radius` included in the payload.
5. The driver account and the vehicle's trip history are untouched by this flow.

---

### Exceptions

**E1 – Validation Error**
A required field is missing or malformed (e.g. non-numeric latitude). System answers `422` with the field errors and writes nothing.

**E2 – Not Signed In / Not a Driver / No Driver Profile**
The request comes from a guest, a commuter, a manager, or a driver without a `Driver` row. System answers `403` (*"Sign in as a driver to broadcast your vehicle location."* / *"Only drivers can broadcast a vehicle location."*) and writes nothing. The payload is validated first, so a malformed request from any of these actors is answered with **E1** (`422`) instead.

**E3 – Vehicle Not Assigned**
The broadcast vehicle is not assigned to the broadcasting driver. System answers `403` (*"This vehicle is not assigned to you."*) and writes nothing — the endpoint cannot be used to track another driver's vehicle.

**E4 – Suspended Driver**
The driver's account has been suspended (UCN_SC_E012 A5). System answers `403` (*"Your account is suspended."*) and writes nothing, so a suspension takes effect on the map immediately, without waiting for the session to expire.

**E5 – Offline / Network Failure**
The browser cannot reach the endpoint (or the response is dropped). The `fetch` promise rejects, the client logs a console warning, and the position is **not** retried by the browser; the vehicle simply keeps its previous position until the 5-minute freshness window closes.

**E6 – Malformed Payload on Retry**
The queued retry payload is missing a required key. `RetryBroadcastLocation` returns without publishing.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **No server-side rate limiting.** The 2-second throttle is client-side only (`resources/views/map.blade.php`); a modified client could flood the endpoint.
- **The route carries no `auth` middleware** — authorization is performed inside the controller, which is why E2/E3/E4 are explicit checks rather than a middleware result.
- **The breadcrumb always stores `distance_from_last_pos = 0`.** The controller has a `haversineDistance()` helper but never computes the real delta, so historical distance travelled cannot be derived from the table.
- **Without a `timestamp` the upsert key is the vehicle alone**, so a driver whose browser clock never sends one collapses onto a single row; the duplicate guard (A1) also cannot apply.
- **`is_active` is only maintained by the scheduler**, not by the read path: `getActiveVehicles()` decides visibility from `last_update` + vehicle status, so the column can drift if the scheduler has not run.
- **`speed` and `accuracy` are stored but not used** for ETA (E003 computes it from a fixed 20 kph), and the obfuscated radius (200 m) is a constant, not per-user.
- **No history retention policy**: `vehicle_location_histories` grows indefinitely.
- The dev-marker endpoints are `local`-gated in the view but the **routes themselves are not role-gated**; they are simply unused in production.

---

### Implementation References

| Element | Location |
|---|---|
| Route | `routes/web.php` → `POST /track/vehicle/broadcast` (`vehicle.broadcast`), registered outside the `auth` groups |
| Controller | `app/Http/Controllers/VehicleTrackingController.php::broadcastLocation()` (authorization, dedupe, upsert, event, history) |
| Read side | `app/Http/Controllers/VehicleTrackingController.php::getActiveVehicles()` — 5-minute freshness window + vehicle status filter |
| Event | `app/Events/LocationUpdated.php` — channel `vehicle-locations`, event `vehicle-location-updated`, payload built in `broadcastWith()` |
| Retry | `app/Jobs/RetryBroadcastLocation.php` (queued 5 s after a failed write, republishes once) |
| Privacy | `app/Helpers/LocationPrivacy.php::obfuscate()` — random offset within a 200 m radius, applied to both the pushed event and `getActiveVehicles()` |
| Models | `app/Models/VehicleLocation.php`, `app/Models/VehicleLocationHistory.php`, `app/Models/DevMarker.php` |
| Scheduler | `app/Console/Commands/VehicleOfflineCheck.php` — `vehicle:offline-check`, every minute (`app/Console/Kernel.php`) |
| Dev markers | `app/Http/Controllers/DevMarkerController.php`, routes `driver.dev.*` (signed-in admin on a `local` install only: `auth`, `role:admin`, `env.local`; the `/pusher` test page and `/fire-event` are guarded the same way) |
| Client | `resources/views/map.blade.php` — `broadcastDriverLocation()`, 2-second throttle, broadcast only when the actor is a driver with an assigned vehicle |
| Tests | `tests/Feature/VehicleTrackingControllerTest.php`, `tests/Feature/UcnSuspensionTest.php::test_a_suspended_driver_cannot_broadcast_a_location` |
| Related | UCN_SC_E003 (Track PUJ — the read side), UCN_SC_E009/E010 (clock in / out), UCN_SC_E012 (Manage Drivers — suspension), UCN_SC_E015 (Manage Vehicles — status drives map visibility) |

---

## UCN_SC_E022 — Driver Registration

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E022                                                                                                                                      |
| **Use Case Name**     | Driver Registration                                                                                                                              |
| **Primary Actor**     | Guest (unauthenticated visitor)                                                                                                                  |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow a guest to register as a driver, submit required details and license documentation, and await admin approval before being able to sign in. |
| **Trigger**           | Guest clicks the "Register as Driver" link on the landing/registration page.                                                                     |
| **Preconditions**     | 1. Guest does not currently hold an active, authenticated session in the system.<br>2. The email address is not yet used by an existing account. |
| **Supporting Actors** | System (database, file storage), Mail service (verification link)                                                                                |

---

### MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Guest clicks the "Register as Driver" link.                                       | System displays the driver registration form (name, email, contact information, password, confirm password, Terms & Conditions checkbox, driver license image upload).                                   |
| 2   | Guest enters their full name, email address, contact information, and creates a password. | System masks both password inputs (eye toggle available for each).                                                                                                                                        |
| 3   | Guest uploads a clear image of their driver's license (JPG, JPEG, PNG, max 2 MB).  | System validates the file type and size client‑side; the image is stored temporarily for upload.                                                                                                        |
| 4   | Guest checks the "I agree to Terms & Conditions" box and clicks "Submit Registration". | System validates all inputs **server-side** (`required`, `email`, `unique`, `string`, `max:255` for name/contact, `min:8` for password, `same` for password confirmation, `accepted` for terms, `image|mimes:jpg,jpeg,png|max:2048` for license). |
| 5   |                                                                                   | System stores the uploaded license image on the `public` disk under `licenses/` (i.e. `storage/app/public/licenses`), generating a stored path.                                                        |
| 6   |                                                                                   | System creates a **User** record with the supplied email, a bcrypt‑hashed password, and sets `email_verified_at` to the current timestamp (email considered verified).                               |
| 7   |                                                                                   | System creates a linked **Driver** record associated with the new user, persisting the name, contact information, and the stored license‑image path.                                                   |
| 8   |                                                                                   | If both User and Driver records are created successfully, the System assigns the **driver** role to the user and fires a `Registered` event.                                                       |
| 9   |                                                                                   | System redirects the guest to the login page (`/login`), which opens a **"You're registered!"** dialog: *the driver account was submitted successfully and an administrator must approve it before the guest can sign in.* A driver code (`DRV-######`) is generated for the new profile. Validation of the contact number (Philippine mobile, `+63 9XX XXX XXXX`) and the licence image (JPG / PNG, max 2 MB) is the same in the admin Add / Edit Driver modals (UCN_SC_E012).                                    |

---

### Alternate Flow

**A1 – Guest wants to commute but has to register as driver first**
1. Guest attempts to access a driver‑only page (e.g., `/map` while unauthenticated).
2. System redirects the guest to the login page.
3. Guest follows the "Register as Driver" link from the login/registration page and proceeds with the main flow.

**A2 – Email Verification Skipped (no resend step)**
1. At step 6, the System creates the User record with `email_verified_at` set to the current timestamp.
2. System treats the email as already verified and sends no verification email.
3. System continues with step 7. Since no verification email exists, no resend‑verification step is offered.

**A3 – Duplicate License Image**
1. At step 3, the guest uploads a license image that is identical to one already uploaded (or re‑uploads the same or a different image).
2. At step 5, the System treats the submission as a new file and stores it under a unique name; no deduplication is performed.
3. System continues with step 6.

---

### Postconditions

1. A new **User** record exists in the `users` table with:
   - `email` as supplied,
   - `password` stored as a bcrypt hash,
   - `email_verified_at` set (email considered verified),
   - no roles assigned yet (role assigned at step 8).
2. A new **Driver** record exists in the `drivers` table linked to the user via `user_id`, containing:
   - `name`,
   - `contact_info`,
   - `license_image_path` (relative to the public disk),
   - `license_image_data` and `license_image_mime` set to `NULL`.
3. The user has been assigned the **driver** role (via `$user->assignRole('driver')`).
4. A `Registered` event has been fired (no listener sends mail for it). Separately, the controller emails every Admin and Driver Manager that a new application is waiting (`DriverApplicationReceived`), and the driver is emailed the decision when an administrator approves or rejects them.
5. The guest’s session remains unauthenticated; they are redirected to the login page and must sign in after admin approval.
6. The driver **cannot** sign in until an admin changes `is_approved` to `1` (see UCN_SC_E012 Manage Drivers). Attempting to sign in before approval results in a redirect with a “pending” flag and a message indicating awaiting approval.

---

### Exceptions

**E1 – Email Already Registered**
The email entered matches an existing account (`unique:users,email`). System displays *"The email has already been taken."* and the form retains the entered email address.

**E2 – Invalid Email Format**
The email is missing, malformed, or not a valid domain. System displays *"The email field must be a valid email address."*

**E3 – Weak Password**
The password does not meet the minimum security requirement (less than 8 characters). System displays *"The password field must be at least 8 characters."*

**E4 – Passwords Do Not Match**
The password and confirm‑password fields differ. System displays *"The password confirmation does not match."*

**E5 – Terms and Conditions Not Accepted**
The guest submits without checking the agreement box. System displays *"The terms field must be accepted."*

**E6 – Invalid License Image**
The uploaded file is not an image, or its MIME type is not JPG/JPEG/PNG, or its size exceeds 2 MB. System displays the corresponding validation message (e.g., *"The license image must be an image of type jpg, jpeg, png."*).

**E7 – Database Error**
The System fails to insert the user and/or driver record (e.g., due to a unique‑constraint violation on email after a race condition, or a general PDO exception). System displays *"A system error occurred while creating your driver account. Please try again later."* and cancels the registration (no partial records are left; the user, driver record and role are written inside an explicit `DB::transaction()`, which is rolled back, and the stored licence image is deleted).

**E8 – File‑Storage Error**
The license image cannot be written to the `public` disk (e.g., insufficient permissions, disk quota). System treats this as a database error and shows the generic message from **E7**.

**E9 – Too Many Attempts (Rate Limiting)**
The guest submits more than **6 registration attempts per minute** (same throttle as commuter registration). System blocks the request (HTTP 429).

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **Wallet creation:** Unlike commuter registration (UCN_SC_E002), the driver registration flow does **not** automatically create a `Wallet` record. In the current codebase a wallet appears to be created lazily when the driver first accesses payment‑related pages (via `Wallet::firstOrCreate`). This behaviour should be documented or, if desired, a wallet could be created at registration time for symmetry.
- **Email verification:** The driver’s email is marked as verified immediately (`email_verified_at = now`). Consequently, no verification email is sent, and the driver does not need to click a verification link to access the system (aside from awaiting admin approval).
- **Admin approval gate:** Although the driver can log in with their credentials after registration, the `map` method (and other driver‑guarded routes) checks the driver’s `is_approved` flag. Unapproved drivers are logged out and redirected to the login page with a warning, effectively preventing them from using driver‑only features until an admin approves the registration (see UCN_SC_E012).
- **License image handling:** The stored license image path is saved, but the raw image data and MIME type are left as `NULL`. If the system ever needs to display or process the license image, it relies solely on the stored file path.
- **Rate‑limiting shares the commuter registration throttle** (`throttle:6,1` inside the `guest` middleware group), which limits both `/register` and `/register/driver` endpoints combined.
- **Localization / validation messages:** Validation messages follow Laravel’s defaults; custom messages are not defined in the request validation (they rely on the default translations).

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /register/driver` (`driver.register.page`), `POST /register/driver` (`driver.register`) (both inside `guest` middleware; the `POST` carries `throttle:6,1`) |
| Controller | `app/Http/Controllers/DriverController.php::create()`, `store()` |
| View | `resources/views/auth/driver/register.blade.php` |
| File storage | `storage/app/public/licenses/` (the `public` disk, `licenses/` prefix — there is no separate `licenses` disk in `config/filesystems.php`) |
| Role assignment | `$user->assignRole('driver')` (uses Laravel‑permission/Spatie package) |
| Events | `Illuminate\Auth\Events\Registered` fired after successful creation |
| Redirect target | `route('login')` → `/login` |
| Related UCNs | UCN_SC_E002 (Commuter Create Account), UCN_SC_E012 (Manage Drivers – approval/reject flow), UCN_SC_E009/E010 (Clock In/Out), UCN_SC_E015 (Manage Vehicles – vehicle assignment) |

---

## UCN_SC_E023 — Driver Dashboard Views

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E023                                                                                                                                      |
| **Use Case Name**     | Driver Dashboard Views                                                                                                                           |
| **Primary Actor**     | Driver (authenticated user holding the `driver` role)                                                                                            |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow a driver to view their weekly timekeeping records, violation history, and to toggle their availability status (active/inactive).         |
| **Trigger**           | Driver opens the sidebar and selects **Timekeeping** or **Violations**, or toggles the status switch in the header.                              |
| **Preconditions**     | 1. Driver is authenticated and logged in.<br>2. Driver has a `Driver` profile (approved or pending – the views are accessible regardless of approval).<br>3. System is operational. |
| **Supporting Actors** | `TimeKeeping` model, `ViolationLog` model, `User` model, `Driver` model                                                                         |

---

### MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver clicks the **Timekeeping** item in the sidebar (or navigates to `/timekeeping`). | System authenticates the driver, fetches the driver’s `TimeKeeping` records for the current week (or the week selected via query param), calculates weekly totals, and returns the `driver.time-keeping` view with the data. |
| 2   |                                                                                   | The view renders a weekly summary card (hours, overtime, progress) and a table (or mobile cards) showing each day of the week with date, time‑in, time‑out, hours worked, overtime, and status (Complete/Active/Absent/etc.). |
| 3   | Driver can change the displayed week by clicking **Previous Week**, **Next Week**, or **Today** links. | System receives the `week` query parameter, repeats step 1 for the requested week, and updates the view accordingly.                           |
| 4   | Driver clicks the **Violations** item in the sidebar (or navigates to `/violations`). | System fetches the driver’s `ViolationLog` records (with eager‑loaded `violationCode`), computes total violations and total fines, and returns the `driver.violations` view. |
| 5   |                                                                                   | The view renders summary cards (total violations, total fines, latest violation) and a list/table of each violation with details: violation type, code, offense count, date, location, fine, penalty, and remarks. |
| 6   | Driver toggles the **status switch** in the header (or submits a form to `/status` with `status=active|inactive`). | System validates the `status` input (`required|in:active,inactive`), updates the `status` column on the driver’s `Driver` record (this availability also drives the colour and popup status of the driver's live marker on the map, UCN_SC_E003), and returns a success toast (“You have successfully set your driver status”). |
| 7   |                                                                                   | The page reloads (or the toast appears) and the driver’s current status is reflected in the header UI.                                          |

---

### Alternate Flow

**A1 – Unauthenticated access attempt**
1. An unauthenticated user tries to access `/timekeeping`, `/violations`, or submits to `/status`.
2. The `auth` middleware redirects the user to the login page (`/login`) with no data retrieved.

**A2 – Invalid status value**
1. Driver submits a status value other than `active` or `inactive` (e.g., `maintenance`).
2. Server‑side validation fails (`Rule::in`), and the request is rejected with a validation error (the view shows the error message or redirects back with `error` flash).

**A3 – Database error while fetching records**
1. A PDO exception occurs when querying `TimeKeeping` or `ViolationLog`.
2. System logs the error, returns a generic error view or flashes *“Time‑keeping failed. Please try again later.”* (or analogous for violations) and does not display partial data.

**A4 – Driver profile missing**
1. Despite being authenticated, the related `Driver` record is absent (e.g., manually deleted).
2. The controller methods (`timekeeping`, `violations`, `updateStatus`) return an error view or flash *“Driver profile not found.”* and stop further processing.

---

### Postconditions

- For **Timekeeping** view: the driver’s weekly time‑in/time‑out, hours, overtime, and daily status are correctly displayed; week navigation updates the shown week.
- For **Violations** view: the driver’s violation logs are listed with accurate totals and details; summary cards reflect counts and sums.
- For **Status update**: the driver’s `status` column in the `drivers` table is set to `active` or `inactive`; a success message is shown; subsequent checks (e.g., clock‑in/out) respect the new status.
- No side‑effects on other tables (e.g., `TimeKeeping` or `ViolationLog` are read‑only in these flows).

---

### Exceptions

**E1 – Driver profile not found**
The authenticated user lacks a linked `Driver` record. On the time-keeping and violations pages the System redirects to the map and flashes *“Driver profile not found.”*; on a status update it redirects back and flashes *“Driver not found.”* Nothing is written.

**E2 – Invalid status input**
The `status` parameter is missing or not one of `active`/`inactive`. System redirects back to the form (HTTP 422 only for JSON requests) with the validation message *“The status field must be either active or inactive.”* and does not update the record.

**E3 – Database query failure**
A query exception occurs while fetching `TimeKeeping` or `ViolationLog`. System logs the exception, returns a generic error view or flashes *“[Time‑keeping/Violations] failed. Please try again later.”* and shows no data.

**E4 – Unexpected server error**
Any other unhandled exception results in a 500 error page (or the Laravel default error page) and logs the incident.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- The time‑keeping view distinguishes between **Complete**, **Active**, **Absent**, **Sick**, **Vacation**, **Upcoming**, and **Waiting** states based on the presence of `time_in`, `time_out`, `sick`, `vacation` flags and whether the date is past/today/future.
- The violation view is **read‑only**; drivers cannot add, edit, or delete violations through this interface (those actions are reserved for admins/managers via UCN_SC_E014).
- The status toggle only affects the `status` column on the `driver` table; it does **not** automatically clock the driver in or out. Clock‑in/out actions are handled separately via UCN_SC_E009/E010.
- Weekly totals are calculated from the stored `hours_worked` and `overtime_hours` columns; they are not recomputed on the fly from raw time‑in/out.
- The view uses Alpine.js for client‑side interactivity (week navigation, card animations) but all data is sourced from server‑side queries.
- The time-keeping view is not paginated (it is bounded to one week); the **violations list is paginated 10 per page**, while the summary cards (total violations, total fines, latest violation) describe the whole record.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /timekeeping` (`driver.timekeeping`), `GET /violations` (`driver.violations`), `POST /status` (`driver.status.update`) (all inside `role:driver` middleware group) |
| Controller | `app/Http/Controllers/DriverController.php::timekeeping()`, `violations()`, `updateStatus()` |
| Views | `resources/views/driver/time-keeping.blade.php`, `resources/views/driver/violations.blade.php` |
| Models | `App\Models\TimeKeeping`, `App\Models\ViolationLog`, `App\Models\Driver` |
| Related UCNs | UCN_SC_E009 (Clock In), UCN_SC_E010 (Clock Out), UCN_SC_E012 (Manage Drivers – approval), UCN_SC_E014 (Manage Violations – admin view) |

---

## UCN_SC_E024 — Admin Violation Code Management

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

### MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User opens the sidebar and selects **Violation Codes** (or navigates to `/driver-manager/violation-codes`). | System authenticates the user, verifies the `driver_manager` role, fetches all `ViolationCode` records, and returns the `driver-manager.violation-codes` view. |
| 2   |                                                                                   | The view renders a table listing each violation code with columns: **Code**, **Violation Name**, **1st Offense (₱)**, **2nd Offense (₱)**, **3rd Offense (₱)**, **4th+ Offense (₱)**, and **Actions** (Edit / Delete). A form at the top allows adding a new code. |
| 3   | To add a new code, the user fills in the form fields (Code, Name, fines for each offense level) and clicks **Add Code**. | System validates the request: `code` required, string, max 255, unique; `name` required, string, max 255; each fine (`first`, `second`, `third`, `fourth_plus`) required, numeric, min 0. On success, a new `ViolationCode` record is created and the user is redirected back with a success flash (“Violation code successfully added!”). |
| 4   | To edit an existing code, the user clicks the **Edit** button for a row, which populates the form with that code’s current values. After modifying the desired fields and clicking **Update Code**, System validates (same rules as creation, with the `unique` rule ignoring the current ID), updates the record, and returns a success flash (“Violation code successfully updated!”). |
| 5   | To delete a code, the user clicks the **Delete** button for a row. System first checks whether the code is referenced by any `ViolationLog` (i.e., in use). If referenced, deletion is blocked and an error flash is shown (“This violation code is in use by existing violation logs and cannot be deleted.”). If not referenced, the `ViolationCode` record is deleted and a success flash is shown (“Violation code successfully deleted!”). |
| 6   | After any successful add/edit/delete operation, the page refreshes (or the list is updated via AJAX) to reflect the current set of violation codes. |

---

### Alternate Flow

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

### Postconditions

- After a successful **add**, the `violation_codes` table contains a new row with the submitted attributes.
- After a successful **edit**, the existing row’s columns are updated to the new values.
- After a successful **delete**, the row is removed from the `violation_codes` table (provided it had no dependent `ViolationLog` rows).
- The user sees appropriate success or error messages via session flash.
- No unintended side‑effects on other tables (e.g., `ViolationLog` remains unchanged unless a delete is blocked).

---

### Exceptions

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

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- Fine amounts are stored as plain numbers (no currency formatting) in the database; the view prefixes them with “₱” for display.
- Violation codes carry **no license-revocation flag** (the feature was removed together with the `is_revoked` column); the 4th+ offense is always a fine.
- The UI includes a **Bulk Add** modal that allows entering multiple codes at once; each row is validated individually before insertion.
- The `code` field is limited to 255 characters and is expected to be a short identifier (e.g., “UV01”, “Speeding”).
- Deletion is prohibited only when the code is referenced by `violation_logs`; there is no cascade‑delete or soft‑delete mechanism.
- The view uses Alpine.js for client‑side interactivity (modals, form handling) but all persistence is performed via standard POST/PUT/DELETE requests to the Laravel backend.
- The code table is filtered in the browser and shown **10 codes per page** with a pager. Add / edit / delete post in the background: a validation error keeps the modal open with the message under its field, and success closes it with a toast.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /driver-manager/violation-codes` (`driver-manager.violation-codes`), `POST /violation-codes/store` (`violation-codes.store`), `PUT /violation-codes/{id}/update` (`violation-codes.update`), `DELETE /violation-codes/{id}/delete` (`violation-codes.destroy`) (all inside `role:driver_manager` middleware group) |
| Controller | `app/Http/Controllers/DriverManagerController.php::violationCodes()`, `storeViolationCode()`, `updateViolationCode()`, `destroyViolationCode()` |
| View | `resources/views/driver-manager/violation-codes.blade.php` |
| Model | `App\Models\ViolationCode` |
| Related UCNs | UCN_SC_E014 (Manage Violations – view logs), UCN_SC_E012 (Manage Drivers – approval/reject) |

---

## UCN_SC_E025 — Profile and Dashboard Views

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E025                                                                                                                                      |
| **Use Case Name**     | Profile and Dashboard Views                                                                                                                      |
| **Primary Actor**     | Authenticated user (Commuter for profile; Admin, Driver, Driver Manager, Maintenance Manager for dashboard)                                      |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow a user to view their own profile information (commuter) and to see role‑specific summary statistics and recent activity on the dashboard. |
| **Trigger**           | User navigates to `/profile` to view their profile, or to `/dashboard` to view the role‑specific dashboard.                                    |
| **Preconditions**     | 1. User is authenticated and logged in.<br>2. For `/profile`: user must have the `commuter` role (the view is only defined for commuters).<br>3. For `/dashboard`: user must have one of the roles `admin`, `driver`, `driver_manager`, or `maintenance_manager`.<br>4. System is operational. |
| **Supporting Actors** | `User`, `Payment`, `TopupHistory`, `Wallet`, `TimeKeeping`, `Driver`, `Vehicle`, `ViolationLog`, `Fare`, `FareRate`, `FleetMaintenanceService` |

---

### MAIN FLOW

#### Profile View (Commuter)

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User clicks **Profile** in the sidebar (or navigates to `/profile`).              | System authenticates the user, checks that the role is `commuter`, fetches the user’s `Payment` records, `TopupHistory` records, and `Wallet` record, and returns the `commuter.profile` view. |
| 2   |                                                                                   | The view renders three sections: **Payments** (list of fare payments with amount, date, etc.), **Top‑ups** (list of top‑up transactions with amount, method, date), and **Wallet** (current balance). |
| 3   | User can scroll through the lists; each item shows relevant details (e.g., payment amount, top‑up method, wallet balance).                     | No further server interaction is required unless the user triggers another action (e.g., making a new top‑up via UCN_SC_E005).                 |

#### Dashboard View (Staff Roles)

The dashboard aggregates role‑specific widgets. The flow differs by role; each block below describes what the system returns for that role.

##### Admin Dashboard

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Authenticated admin navigates to `/dashboard`.                                   | System verifies the `admin` role, computes aggregates: total revenue (`Payment::sum('price')`), total funds added (`TopupHistory::sum('amount_added')`), active users count (distinct `paid_by` in payments), recent fares (latest 5 payments with user), recent top‑ups (latest 5 top‑ups with user), revenue by day (last 7 days), top‑ups by day (last 7 days). |
| 2   |                                                                                   | System returns the `admin.dashboard` view with the computed data.                                                                                                                                       |
| 3   |                                                                                   | The view displays panels: **Total Revenue**, **Total Funds Added**, **Active Users**, **Recent Fares** (table), **Recent Top‑ups** (table), **Revenue by Day** (chart or list), **Top‑ups by Day** (chart or list). |

##### Driver Dashboard

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Authenticated driver navigates to `/dashboard`.                                   | System verifies the `driver` role, loads the driver’s `Driver` record, today’s `TimeKeeping` record, recent time‑keeping (last 7 days), weekly hours/overtime, assigned `Vehicle`, recent violation logs (latest 5), and totals for violations. |
| 2   |                                                                                   | System returns the `driver.dashboard` view with the data.                                                                                                                                               |
| 3   |                                                                                   | The view shows: **Driver Info** (name, license, etc.), **Today’s Record** (time‑in/out, hours), **Weekly Summary** (hours, overtime), **Assigned Vehicle** (plate, brand/model), **Recent Time‑keeping** (table of last 7 days), **Recent Violations** (table with type, date, fine), **Total Violations** and **Total Fines**. |

##### Driver‑Manager Dashboard

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Authenticated driver‑manager navigates to `/dashboard`.                           | System verifies the `driver_manager` role, fetches all `Driver` records (with user), all `TimeKeeping` records (with driver), and all `ViolationLog` records (with user). |
| 2   |                                                                                   | System returns the `driver-manager.dashboard` view with the data (converted to arrays for display).                                                                                                   |
| 3   |                                                                                   | The view lists drivers (ID, name, code, license, expiration), time‑keeping logs (driver, date, in/out, hours, overtime, sick/vacation flags), and violation logs (user, instance, fine, date, time). |

##### Maintenance‑Manager Dashboard

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Authenticated maintenance‑manager navigates to `/dashboard`.                      | System verifies the `maintenance_manager` role, loads all `Vehicle` records (with driver), all `Driver` records, and the fleet maintenance summary for a selected vehicle (via query param `vehicle_id` or first vehicle). |
| 2   |                                                                                   | System returns the `maintenance-manager.dashboard` view with the data (vehicles, drivers, selected vehicle, and summary from `FleetMaintenanceService`). |
| 3   |                                                                                   | The view shows: **Fleet Information** (plate, year, brand/model, driver), **Cost Summary** (total cost, total services, avg cost/service, cost/km) and a **Service History** tab, all derived from the preventive-maintenance records (UCN_SC_E016). |

---

### Alternate Flow

**A1 – Unauthenticated access**
1. An unauthenticated user tries to access `/profile` or `/dashboard`.
2. The `auth` middleware redirects the user to the login page (`/login`).

**A2 – Wrong role for profile**
1. A non‑commuter authenticated user (e.g., driver, admin) attempts to access `/profile`.
2. The `profile` method lacks an `else` branch; Laravel falls through and returns `null`, resulting in a 500 error (or the default error page). In practice, the profile page is intended for commuters only; staff use the `/settings` route for profile edits.

**A3 – Wrong role for dashboard**
1. An authenticated user whose role is not `admin`, `driver`, `driver_manager`, or `maintenance_manager` (e.g., a commuter) accesses `/dashboard`.
2. None of the role conditionals match; the method ends without a `return` statement, leading to a 500 error. In the current application, commuters use the map (`/map`) as their primary dashboard (see UCN_SC_E003), so this scenario should not occur for normal users.

**A4 – Missing related data**
1. For any dashboard widget, a query returns an empty collection (e.g., no payments, no top‑ups, no time‑keeping records).
2. The view gracefully displays “—”, “0”, or empty lists, and the dashboard renders without error.

**A5 – Database error while computing aggregates**
1. A PDO exception occurs during any of the aggregate queries (sums, counts, latest records).
2. System logs the exception, returns a generic error view or flashes *“Dashboard could not be loaded. Please try again later.”* and shows no data.

---

### Postconditions

- **Profile view**: the user sees their payment history, top‑up history, and current wallet balance accurately reflected.
- **Admin dashboard**: the summarized financial and user‑activity metrics are correct as of the moment of the request.
- **Driver dashboard**: the driver’s personal statistics (time‑keeping, violations, vehicle assignment) are accurate.
- **Driver‑manager dashboard**: the lists of drivers, time‑keeping entries, and violation logs are up‑to‑date.
- **Maintenance‑manager dashboard**: the fleet overview and selected‑vehicle maintenance summary are correct.
- No data is persisted; all flows are read‑only.

---

### Exceptions

**E1 – Profile not commuter**
The authenticated user does not have the `commuter` role. System redirects to the map and flashes *“Profile page unavailable.”*

**E2 – Dashboard role mismatch**
The authenticated user’s role is not one of the permitted dashboard roles. System redirects to the map and flashes *“Dashboard unavailable.”*

**E3 – Database query failure**
Any query used to build the profile or dashboard throws a PDO exception. System logs the error and shows an error page or flash message indicating the dashboard could not be loaded.

**E4 – Unexpected server error**
Any other unhandled exception results in a 500 error page.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- The `/profile` endpoint currently only serves commuter users; staff profile information is viewable via the `/settings` route (see UCN_SC_E008 for password change, but other profile fields like email are edited there). A dedicated staff profile page does not exist in the current codebase.
- The `/dashboard` endpoint is strictly for staff roles; commuters are expected to use the map (`/map`) as their main interface (documented in UCN_SC_E003). This separation is intentional in the product design.
- Dashboard widgets are refreshed only on page load; there is no auto‑reload or polling for live updates.
- The maintenance‑manager dashboard summary is computed by `App\Services\FleetMaintenanceService` (`summaryFor()` / `emptySummary()`), reading each vehicle's preventive-maintenance records.
- All monetary values are stored as plain numbers in the database; views format them with the “₱” prefix and two decimal places where appropriate.
- The dashboard does not expose sensitive raw data (e.g., full payment details) beyond what is necessary for the summary widgets.
- Error handling for missing relationships (e.g., a driver without a vehicle) gracefully shows placeholders like “—” or “Unassigned”.

---

### Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /profile` (`profile`), `GET /dashboard` (`dashboard`) (both inside `auth` + `verified` middleware; `/profile` additionally lacks role middleware, `/dashboard` is accessed by staff via their role checks inside the controller) |
| Controller | `app/Http/Controllers/UserController.php::profile()`, `dashboard()` |
| Views | `resources/views/commuter/profile.blade.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/driver/dashboard.blade.php`, `resources/views/driver-manager/dashboard.blade.php`, `resources/views/maintenance-manager/dashboard.blade.php` |
| Models | `App\Models\User`, `App\Models\Payment`, `App\Models\TopupHistory`, `App\Models\Wallet`, `App\Models\TimeKeeping`, `App\Models\Driver`, `App\Models\Vehicle`, `App\Models\ViolationLog`, `App\Models\Fare`, `App\Models\FareRate` |
| Services | `App\Services\FleetMaintenanceService` (used in dashboard) |
| Related UCNs | UCN_SC_E003 (Track PUJ – commuter map/dashboard), UCN_SC_E005 (Topup Balance), UCN_SC_E006 (Pay Fare), UCN_SC_E009/E010 (Clock In/Out), UCN_SC_E012 (Manage Drivers), UCN_SC_E016 (Manage Maintenance Schedule) |

---

## UCN_SC_E026 — Account Deletion

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E026                                                                                                                                      |
| **Use Case Name**     | Account Deletion                                                                                                                                 |
| **Primary Actor**     | Authenticated user (any role)                                                                                                                    |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow a user to permanently delete their own account, logging them out and removing all associated data.                                         |
| **Trigger**           | User clicks the **Delete Account** button (typically found in the settings or profile page) and confirms the action.                             |
| **Preconditions**     | 1. User is authenticated and logged in.<br>2. System is operational.<br>3. User intends to delete their own account (no administrative deletion of other users). |
| **Supporting Actors** | `User` model, Session store                                                                                                                      |

---

### MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User clicks **Delete Account** and confirms the action in a prompt (JavaScript `confirm()`). | System sends a `DELETE` request to `/delete-account` with the CSRF token.                                                                      |
| 2   |                                                                                   | The `UserController::destroy` method writes a `deleted_self` audit-trail entry (keeping the email, because the account is about to go), logs the user out (`Auth::logout()`), invalidates the session (`$request->session()->invalidate()`), and regenerates the CSRF token. |
| 3   |                                                                                   | System calls `User::eraseAccount()`. An account with **no payments or top-ups** is deleted from `users` (its wallet, preferences and other rows go with it). An account **with** payments or top-ups is **anonymised** instead: its email becomes `deleted-<id>@deleted.invalid`, and its password, roles, API tokens, sessions, preferences, reset links, map markers and any driver profile are removed, so it can never sign in again — while the user row, wallet, payments and top-ups are kept.                               |
| 4   |                                                                                   | If the deletion succeeds, System redirects the user to the login page (`/login`) with a success toast: *“Account deleted successfully.”* An account whose **wallet still holds money** is refused first (*"Your wallet still holds PHP N. Use it up before deleting your account."*) and stays signed in. A driver who still holds a vehicle is refused first (*"You are still assigned to a vehicle…"*) and stays signed in. |
| 5   |                                                                                   | The login page is displayed, and the user can now create a new account or log in with different credentials.                                   |

---

### Alternate Flow

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

### Postconditions

- The user’s session is terminated and the session ID is regenerated.
- **Deleted** (no payments or top-ups): the user's row in `users` is removed, and with it, by cascade, the wallet, preferences, location rows, driver profile (and its timekeeping) and violation logs.
- **Anonymised** (payments or top-ups exist): the user row stays with a placeholder email and `anonymised_at` set; the account is suspended, has no roles, an unknown password, no tokens, sessions, preferences, reset links, map markers or driver profile (the licence image file is deleted). The wallet, **payments and top-ups are kept**, so the ledger and the Admin's audit stay whole. The original email address can be registered again.
- Rows linked by id with **no** foreign key — role assignments (`model_has_roles`) in the *deleted* case, `sessions`, API tokens, password-reset rows, `activity_log` entries — are not cascaded; the anonymise path removes the first four explicitly, and audit entries are kept for the retention period.
- The user sees a success message and is returned to the login page.
- No further action is required; the user may register a new account if desired.

---

### Exceptions

**E1 – Authentication missing**
The request lacks a valid authenticated session. System redirects to `/login` (handled by middleware) and does not attempt deletion.

**E2 – Database delete failure**
A query exception occurs during `User::destroy()`. It is not caught: the controller produces no friendly message and the user sees the default 500 error page.

**E3 – Unexpected controller error**
Any other exception in the `destroy` method results in a 500 error page.

---

### Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

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

### Implementation References

| Element | Location |
|---|---|
| Route | `routes/web.php` — `DELETE /delete-account` (`users.delete-account`) (inside `auth` middleware) |
| Controller | `app/Http/Controllers/UserController.php::destroy()` |
| Model | `App\Models\User` |
| Related UCNs | UCN_SC_E001 (Login – for post‑deletion login), UCN_SC_E002 (Create Account – for re‑registration), UCN_SC_E008 (Change Password – shows that account modification is possible) |

---

## UCN_SC_E027 — Manage Staff Accounts

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

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

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

---

## UCN_SC_E028 — View Activity Log

| Field                 | Value                                                                                                   |
| --------------------- | --------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E028                                                                                             |
| **Use Case Name**     | View Activity Log                                                                                       |
| **Primary Actor**     | Admin (`role:admin`)                                                                                    |
| **Secondary Actor**   | System (audit trail, CSV writer)                                                                        |
| **Goal**              | Allow the Admin to review the audit trail — who did what, to which record, when and from where — and to export what they find. |
| **Trigger**           | Admin clicks the **"Activity Log"** item on the sidebar (`GET /activity-log`).                           |
| **Preconditions**     | 1. Admin is authenticated and registered to the system.<br>2. The audit trail has been written by the system (UCN actions that create, change, delete, sign in or export — see `docs/AUDIT_TRAIL.md`). |
| **Supporting Actors** | Activity log (`activity_log`), User record                                                              |

> **Scope.** The page is **read-only**: no entry can be edited, deleted or added from it. Only the Admin may open it; every other role is refused (403) and a guest is sent to the login page. **Viewing the page writes nothing** to the trail; exporting does.

---

## MAIN FLOW

| #   | Actor's Action                                   | System Response                                                                                                                                                                                                                             |
| --- | ------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Admin clicks **"Activity Log"** on the sidebar.  | System verifies the `admin` role and lists the entries **newest first, 10 per page**, with the number of entries, how many were written today and — when there are any — how many **failed sign-ins** happened in the last 24 hours (a link to those entries). |
| 2   |                                                  | Each row shows **When** (Philippine time), **Who** (the signed-in user's email and role, or *Not signed in / system*), **What happened** (a category badge, an event badge and a readable description), **Record** (e.g. *Vehicle #12*) and the **IP address**. |
| 3   | Admin clicks a row.                              | System expands it: **What changed** — a *Field / Before / After* table of the business fields that changed (a fare-rate edit shows a *Distance / Regular / Discount* table instead) plus any extra facts (e.g. the suspension reason) — and **Where it came from**: entry number, exact time, IP address, device (e.g. *Chrome on Windows*) and the browser string. |

---

## Alternate Flow

**A1 – Search**
1. Admin types into the search box and presses **Filter**.
2. System narrows the list to entries whose description, **acting user's email** or — for failed sign-ins — the **email that was tried** contains the text. `%` and `_` are matched literally.

**A2 – Filter by category and event**
1. Admin picks a **category** (*Sign-in & security, Accounts, Drivers, Fleet & maintenance, Violations, Timekeeping, Payments, Fares, System*) and/or an **event** (e.g. *Failed sign-in, Suspended, Role changed, Fare rates updated*).
2. System applies it at once; the filters survive paging and combine with each other and with A1 / A3.

**A3 – Filter by date range**
1. Admin sets a **from** and/or **to** date and presses **Filter**.
2. System keeps the entries written from the start of the first day to the end of the last day, inclusive.

**A4 – Clear the filters**
1. Admin presses **Clear**. System restores the full list.

**A5 – Export to CSV**
1. Admin presses **Export CSV**.
2. System writes a UTF-8 CSV of **everything the current filters match** (newest first, at most **10,000 rows**) named `smartcommute_activity_log_<YYYY-MM-DD>.csv`, with the columns *Entry, Time, Category, Event, Actor email, Actor role, Description, Record, IP address, Device, Changes, Details*. A cell that would start with `=`, `+`, `-` or `@` is prefixed with `'` so a spreadsheet cannot run it as a formula.
3. System records **the export itself** as an entry (`activity_log_exported`, with the row count and the filters used) — so an export is always attributable.

**A6 – Jump to failed sign-ins**
1. Admin clicks the *failed sign-ins in the last 24 h* link in the page header.
2. System applies *Event = Failed sign-in* from yesterday onward.

---

## Postconditions

1. The Admin has reviewed the entries that match the chosen filters; nothing was changed.
2. **Viewing** the page and the list wrote nothing to the trail.
3. After an export, one `activity_log_exported` entry exists naming the Admin, the number of rows and the filters.
4. Entries written by the previous, noisy logging ("Action performed: …") are **hidden** from the list and the export; they are removed for good with `php artisan activitylog:purge-noise`.

---

## Exceptions

**E1 – Not an Admin**
A Driver Manager, Maintenance Manager, Driver or Commuter requests the page or the export. System answers 403; a guest is redirected to the login page. Nothing is shown or exported.

**E2 – End Date Before Start Date**
The *to* date is earlier than the *from* date. System rejects the request with *"The end date must be on or after the start date."*, shows no result and keeps the form.

**E3 – Unknown Category or Event**
The URL carries a category or event the system does not write. System rejects it with the framework validation message and runs no query on it.

**E4 – No Entries Found**
Nothing matches the filters (or nothing has been recorded yet). System shows *"No entries match your filters"* / *"Nothing has been recorded yet"* instead of an empty table.

**E5 – Export Larger Than the Cap**
More than 10,000 entries match. System exports the newest 10,000; the Admin narrows the dates to reach older ones.

---

## Known Gaps / Notes

- **Distance driven is recorded.** Each accepted broadcast stores `distance_from_last_pos`: the kilometres since the vehicle's previous ping (0 for the first ping of a trip, after a gap over 10 minutes, or for a jump over 20 km). It feeds the driver's *distance today* figure and Fleet Analytics. Before this change it was always 0.

- **Reports are also sent on a schedule.** `php artisan reports:send weekly` (Mondays 06:00) and `reports:send monthly` (the 1st, 06:30) email every Admin the previous full week or month — fare revenue by method, top-ups, failed payments, violations and fines collected, hours and overtime, distance, maintenance spend, overdue services — with the figures as a CSV and the period's audit trail as a second CSV (`REPORT_ATTACH_ACTIVITY_LOG`). Each send is audited (*report_sent*).
- **Fleet Analytics** (`GET /analytics`, Admin only) shows the same numbers per driver and per vehicle for the last 7, 30 or 90 days, an 8-week hours/overtime trend, repeat violators and commuters with unpaid scans, all computed from the stored records (`FleetAnalyticsService`).

- **Entries are kept 365 days** (`ACTIVITY_LOG_RETENTION_DAYS`); the daily `activitylog:clean` job deletes older ones, so the page cannot show history beyond that. The scheduler must be running.
- **Entries written before the audit trail was rebuilt** have a *Legacy* category, no record, no field values and no IP address. They are listed (except the "Action performed" noise) but carry little detail.
- **The record link is a label, not a link.** An entry names its record (*Vehicle #12*), but the page does not open that record, and the record may since have been deleted — the description and `Details` still say what it was.
- **Event and category lists are fixed** (`app/Support/AuditCatalog.php`). A new event type still appears in the list with its raw name, but is not offered in the *Event* filter until it is added there.
- **Page size is 10**, in line with every other list; the export is the way to read many entries at once.
- **No live refresh** — the list is as of the page load.
- **Search scans the table** (no full-text index); it is quick at the retention size used here.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `activity-log.index`, `activity-log.export`, inside `role:admin` |
| Controller | `app/Http/Controllers/Admin/ActivityLogController.php` — `index()`, `export()` |
| Presentation | `app/Support/AuditPresenter.php` (row → readable data, masks secrets), `app/Support/AuditCatalog.php` (names and colours) |
| View | `resources/views/admin/activity-log/index.blade.php` |
| Menu | `config/menu.php` — *Activity Log* (admin) |
| Writing the trail | `app/Support/Audit.php`, `app/Models/Concerns/Audited.php`; design in `docs/AUDIT_TRAIL.md` |
| Schema | `activity_log`; `database/migrations/2026_10_21_100000_add_created_at_index_to_activity_log.php` |
| Tests | `tests/Feature/ActivityLogPageTest.php`, `tests/Feature/AuditTrailTest.php` |
| Related | UCN_SC_E001 (Login — sign-in events), UCN_SC_E018 / E027 (suspensions, staff), UCN_SC_E020 (report export, also audited) |
