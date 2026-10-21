# Functional Requirements — SmartCommute

What the system **shall do**, derived from the Use Case Narratives in [`UCN/`](UCN/README.md) and the
[use-case diagram](Use_Case_Diagram.md). Quality attributes (speed, security, usability…) are in
[Non-Functional Requirements](Non_Functional_Requirements.md).

## How to read this document

- **ID** — `FR-<area>-<nn>`. IDs are stable; new requirements take the next number in their area.
- **Priority** — MoSCoW: **M**ust (core journey, the product does not work without it), **S**hould (important, workaround exists),
  **C**ould (convenience). Priorities are a proposal for the team to confirm — the UCNs do not carry them.
- **Source** — the UCN the behaviour is documented in (`E006` = `UCN_SC_E006`). Every requirement in sections 1–12 describes
  behaviour the UCNs record as **implemented**; section 13 lists what they record as **missing**.
- Roles: *Guest*, *Commuter*, *Driver*, *Driver Manager*, *Maintenance Manager*, *Admin* — see [Use_Case_Diagram.md](Use_Case_Diagram.md#1-actors).
- Message wording, field names and route names are deliberately left to the UCNs; this document states the rule, not the copy.

## Contents

1. [Account & access](#1-account--access-fr-acc) · 2. [Live map & tracking](#2-live-map--tracking-fr-map) ·
3. [Trip planning](#3-trip-planning-fr-trp) · 4. [Wallet & payments](#4-wallet--payments-fr-pay) ·
5. [Driver operations](#5-driver-operations-fr-drv) · 6. [Driver management](#6-driver-management-fr-dmg) ·
7. [Fleet & maintenance](#7-fleet--maintenance-fr-flt) · 8. [Administration](#8-administration-fr-adm) ·
9. [Dashboards & profile](#9-dashboards--profile-fr-dsh) · 10. [Access control](#10-access-control-fr-rbac) ·
11. [Audit trail](#11-audit-trail-fr-aud) · 12. [Role summary](#12-role-capability-summary) ·
13. [Not yet implemented](#13-documented-gaps-candidate-requirements)

---

## 1. Account & access (FR-ACC)

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-ACC-01 | A Guest shall be able to register a commuter account with an email address, a password of at least 8 characters, a matching confirmation and acceptance of the Terms & Conditions. | M | E002 |
| FR-ACC-02 | On registration the system shall reject an email that is already in use, a malformed email, a short or unconfirmed password and unaccepted terms, with a message per failed rule, and shall create no account. | M | E002 |
| FR-ACC-03 | On successful registration the system shall create the user as **unverified**, assign the `commuter` role, create an empty wallet, sign the user in and send a signed email-verification link. | M | E002 |
| FR-ACC-04 | A user with an unverified email shall be held on the verification notice page until verified, and shall be able to request the verification email again. | M | E002, E001 |
| FR-ACC-05 | A Guest who pressed *Buy a Ride* on the public map shall be sent to registration with the chosen pick-up and destination kept. | S | E002, E004 |
| FR-ACC-06 | A registered user shall be able to sign in with email and password; on success the system shall start a new session and open the map. | M | E001 |
| FR-ACC-07 | The system shall answer a wrong email and a wrong password with the **same** message and shall reject empty or malformed fields before querying the database. | M | E001 |
| FR-ACC-08 | The system shall refuse sign-in to a suspended account with a message to contact the administrator. | M | E001, E012, E018, E028 |
| FR-ACC-09 | A user shall be able to ask to be remembered on a device so that the next visit signs in automatically. | C | E001 |
| FR-ACC-10 | A visitor who opens a protected page shall be redirected to the login page; an already signed-in user who opens the login page shall be redirected to the map. | M | E001, E008, E023 |
| FR-ACC-11 | At every sign-in the system shall warn a driver whose licence expires within 30 days or has expired. | S | E001, E012 |
| FR-ACC-12 | A user who forgot their password shall be able to request a time-limited reset link by email and set a new password (minimum 8 characters, confirmed). | M | E011 |
| FR-ACC-13 | A reset link shall be single-use and expire after 60 minutes; using the link shall also invalidate persistent (remember-me) logins and shall be recorded in the activity log. | M | E011 |
| FR-ACC-14 | A Guest shall be able to register as a driver by supplying name, email, contact number, password, accepted terms and a licence image (JPG/PNG, max 2 MB). | M | E022 |
| FR-ACC-15 | A driver registration shall be created as **pending**: the user, driver profile (with generated driver code), role and licence image are saved together or not at all, and the applicant cannot use driver features until a Driver Manager approves. | M | E022, E012 |
| FR-ACC-16 | A signed-in user shall be able to change their own password by giving the current password and a new one (≥ 8 characters, confirmed, different from the current one). | M | E008 |
| FR-ACC-17 | A password change shall end the user's other active sessions, and a user shall also be able to end all other sessions without changing the password. | S | E008 |
| FR-ACC-18 | A signed-in user shall be able to delete their own account after confirming; the system shall sign them out and invalidate the session, and shall delete or anonymise the account as FR-ACC-23 says. | S | E027 |
| FR-ACC-19 | The system shall refuse account deletion for a driver who still holds a vehicle, and shall tell the driver to ask the Maintenance Manager to unassign it. | S | E027 |
| FR-ACC-20 | The system shall record sign-in, sign-out, failed and blocked sign-in, password changes and resets, and account creation, suspension and deletion in the audit trail (see FR-AUD-01, FR-AUD-02). | M | E001, E008, E011, E028 |
| FR-ACC-21 | The system shall end an already-open session of a suspended user on that user's next request. | M | E012, E018, E028 |
| FR-ACC-22 | The system shall refuse to delete a user's own account while its wallet holds money, naming the balance and leaving the user signed in (there is no refund flow). | M | E027 |
| FR-ACC-23 | When an account is removed — by its owner or by an Admin — the system shall **delete** it if it has no payments or top-ups, and otherwise **anonymise** it: replace the email with a placeholder, remove the password, roles, API tokens, sessions, preferences, reset links, map markers and any driver profile (and licence image), suspend it, and keep the user row, wallet, payments and top-ups. | M | E027, E018, E012, E028 |
| FR-ACC-24 | An anonymised account shall be unable to sign in, hold no role and appear in no user list or user count, and its original email address shall be free to register again. | M | E027 |

## 2. Live map & tracking (FR-MAP)

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-MAP-01 | A Guest or Commuter shall be able to open an interactive map without signing in (Guest) and have it centre on their position when location permission is granted. | M | E003 |
| FR-MAP-02 | The map shall show a marker for every PUJ that is roadworthy (vehicle status `active` or `inactive`) and has reported a position within the last 5 minutes. | M | E003, E015 |
| FR-MAP-03 | A PUJ marker shall be coloured by the driver's availability — green available, red unavailable, blue unknown — and shall change within a few seconds of the driver toggling their status. | M | E003, E023 |
| FR-MAP-04 | Marker positions shall update in real time from the push channel, with a polling fallback when the push channel is unavailable. | M | E003, E021 |
| FR-MAP-05 | Selecting a marker shall open a popup with the plate/route, an approximate location, the driver's availability and an ETA to the user. | M | E003 |
| FR-MAP-06 | ETA badges shall be shown for PUJs within 5 km, and the nearest PUJ within 10 km shall be offered as a floating indicator that, when tapped, flies to that PUJ and opens its popup. | S | E003 |
| FR-MAP-07 | The user shall be able to re-centre the map on their location with a locate control, which also refreshes ETAs as the position changes. | S | E003 |
| FR-MAP-08 | The map shall show one status banner at a time, chosen by the priority *offline → map service → routing service → location denied → no PUJ*, and shall clear it automatically when the condition ends. | S | E003, E004 |
| FR-MAP-09 | A vehicle set to `maintenance` or `disposed` shall disappear from the live map immediately. | M | E015 |
| FR-MAP-10 | A Guest shall be limited to 3 fare/route lookups per day on the map. | C | E003, E004 |

## 3. Trip planning (FR-TRP)

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-TRP-01 | A user shall be able to set a pick-up and a destination by tapping the map or by searching for a place (from 2 characters, up to 6 matches, restricted to the service area). | M | E004 |
| FR-TRP-02 | The system shall resolve a tapped point to a readable address and, if that fails, show the raw coordinates and still place the marker. | S | E004 |
| FR-TRP-03 | Once both points are set the system shall draw the recommended driving route and show its distance in kilometres. | M | E004 |
| FR-TRP-04 | The system shall show the regular and discounted fare for that distance from the active fare table (highest distance tier not above the distance, rounded up to whole pesos). | M | E004, E019 |
| FR-TRP-05 | A user shall be able to clear both points, the route and the fare with a reset action. | S | E004 |
| FR-TRP-06 | If the routing service fails the system shall clear distance and fare and say the trip could not be calculated; if no fare rates exist it shall still show the distance with a fare of 0. | S | E004 |
| FR-TRP-07 | Pressing *Buy a Ride* shall take a Commuter to the payment page with the route and distance, and a Guest to registration; the system shall reject it when either point is missing. | M | E004, E006 |

## 4. Wallet & payments (FR-PAY)

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-PAY-01 | A Commuter shall be able to top up their wallet by choosing a preset amount (₱50 / 100 / 200 / 500) or a custom amount from ₱10 to ₱100,000, and a self-service method (GCash or Maya). | M | E005 |
| FR-PAY-02 | A successful top-up shall add the exact amount to the wallet balance and write a top-up history record (user, wallet, amount, method). | M | E005 |
| FR-PAY-03 | The system shall credit a repeated identical top-up (same user, amount, method) within 2 minutes only once. | S | E005 |
| FR-PAY-04 | A Commuter shall be able to search and filter their top-up history by ID, method and date range and see the total added. | S | E005 |
| FR-PAY-05 | An Admin shall be able to list every top-up with its status and filter by search, status, method, date range and amount range; the "funds added" total shall count completed top-ups only. | S | E005 |
| FR-PAY-06 | On *Buy a Ride* the system shall **re-price the fare on the server** from the published rate table and show a checkout page with the final fare, wallet balance and the methods GCash, Maya and Wallet. | M | E006 |
| FR-PAY-07 | Paying by **Wallet** shall deduct the fare from the balance, create a `paid` payment record and open the receipt; if the balance is lower than the fare the system shall refuse the payment and change nothing. | M | E006 |
| FR-PAY-08 | Paying by **GCash/Maya** with the gateway enabled shall create a hosted checkout session, record the payment as `pending` without touching the wallet, and redirect the Commuter to the gateway. | M | E006 |
| FR-PAY-09 | The system shall settle a gateway payment from a signed webhook, and on return from the gateway shall reconcile by asking the gateway directly when the webhook has not arrived. | M | E006 |
| FR-PAY-10 | A gateway payment shall end as `paid`, `failed` (declined / not completed) or `cancelled` (abandoned, expired or open for 12 hours) and shall never charge the wallet. | M | E006 |
| FR-PAY-11 | With the gateway disabled, a GCash/Maya payment shall be recorded as a declared method with status `paid` and no external call. | S | E006 |
| FR-PAY-12 | Every payment shall carry a unique transaction id; replaying a request with an id already used shall not charge twice and shall lead to the receipt, the pending screen or the history depending on that payment's state. | M | E006 |
| FR-PAY-13 | The system shall reject a payment method other than GCash, Maya or Wallet and shall refuse to price a fare when no fare rates are published. | M | E006 |
| FR-PAY-14 | After paying, the Commuter shall see a digital receipt (route, distance, method, transaction id, fare, paid-at) rendered from the stored record, so refreshing never creates a second payment. | M | E006, E007 |
| FR-PAY-15 | A Commuter shall be able to list **only their own** payments newest first, 10 per page, with the wallet balance and total spent on settled fares. | M | E007 |
| FR-PAY-16 | A Commuter shall be able to search payment history by transaction id or destination and filter by quick range (7/30/90 days) or explicit dates, with filters kept across pages. | S | E007 |
| FR-PAY-17 | A Commuter shall be able to open one receipt and download it as an image; a receipt that does not exist or belongs to someone else shall return *not found*. | S | E007 |
| FR-PAY-18 | An Admin shall be able to open any payment's receipt through the fare-transactions ledger, whatever its status. | S | E007 |
| FR-PAY-19 | The system shall close a gateway payment still `pending` after 12 hours as `cancelled`, on a schedule and whenever history, the ledger or checkout is opened. | S | E006 |

## 5. Driver operations (FR-DRV)

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-DRV-01 | A Driver shall be able to clock in once per day from the map; this creates today's timekeeping record with the current time (Asia/Manila) and sets the driver to `active`. | M | E009 |
| FR-DRV-02 | A Driver shall be able to clock out; the system shall store the time-out, hours worked (2 decimals) and overtime (hours beyond 8) and set the driver to `inactive`. | M | E010 |
| FR-DRV-03 | The system shall refuse a second clock-in on the same day, a clock-out with no open shift, and either action when the driver has no driver profile. | M | E009, E010 |
| FR-DRV-04 | A Driver shall be able to switch their availability between `active` and `inactive`; any other value shall be rejected. | M | E023 |
| FR-DRV-05 | A Driver with an assigned, `active` vehicle and a non-suspended account shall broadcast the vehicle's position from the browser, at most once every 2 seconds. | M | E021 |
| FR-DRV-06 | The system shall accept a broadcast only from a signed-in driver for the vehicle assigned to them, and shall refuse everyone else without writing anything. | M | E021 |
| FR-DRV-07 | The system shall store the latest position of the vehicle, keep a history breadcrumb of each accepted broadcast, and mark the vehicle active. | M | E021 |
| FR-DRV-08 | The system shall publish a broadcast to guests and commuters only after moving the position to a random point within **200 m** of the real one, and shall never publish the exact coordinates. | M | E021, E003 |
| FR-DRV-09 | A repeated broadcast with an already recorded timestamp shall be accepted as a no-op; a temporary storage failure shall be retried once after 5 seconds. | S | E021 |
| FR-DRV-10 | The system shall mark a vehicle inactive when no broadcast has arrived for 5 minutes. | M | E021 |
| FR-DRV-11 | A Driver shall be able to view their weekly timekeeping (hours, overtime, daily status) and move between weeks. | M | E023 |
| FR-DRV-12 | A Driver shall be able to view their own violation history with totals and the latest violation, read-only, 10 per page. | S | E023 |
| FR-DRV-13 | A suspended or unapproved driver shall be unable to sign in, clock in, change status or broadcast. | M | E012, E022, E021 |

## 6. Driver management (FR-DMG)

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-DMG-01 | A Driver Manager or Admin shall be able to list drivers with search and filters (all, pending, approved, rejected, suspended, licence expiring, licence expired), 10 per page. | M | E012 |
| FR-DMG-02 | They shall be able to add a driver (name, email, password, licence number/code/expiry, Philippine mobile number, licence image); the system generates the driver code, verifies the email, approves the driver immediately and rejects duplicate emails and licence numbers. | M | E012 |
| FR-DMG-03 | They shall be able to edit any driver except a pending registration, and view driver details; a **pending** registration can only be approved or rejected. | M | E012 |
| FR-DMG-04 | They shall be able to **approve** or **reject** a pending driver application; approval validates the licence fields and assigns the driver code. | M | E012 |
| FR-DMG-05 | They shall be able to suspend an approved driver (optional reason) and reactivate them; suspension blocks sign-in, clock-in and broadcast, detaches the driver's vehicle and is recorded in the audit trail (FR-AUD-02). | M | E012 |
| FR-DMG-06 | They shall be able to delete a driver, except one who is still assigned a vehicle; a driver who also has payments or top-ups is anonymised instead (FR-ACC-23). | S | E012 |
| FR-DMG-07 | The list shall show each driver's licence state (valid / expires in N days / expired). | S | E012 |
| FR-DMG-08 | A Driver Manager shall be able to read the timekeeping ledger (driver, date, shift, hours, overtime, type) and filter it by All / Regular / Sick / Vacation / Overtime. | M | E013 |
| FR-DMG-09 | A Driver Manager shall be able to add a shift entry or a leave entry (sick / vacation); the system derives hours and overtime itself. | M | E013 |
| FR-DMG-10 | The system shall reject a shift whose clock-out is not after its clock-in, and one that overlaps an existing entry for that driver and date. | M | E013 |
| FR-DMG-11 | A Driver Manager shall be able to create, edit and delete violation codes (code, name, fines for 1st, 2nd, 3rd and 4th+ offence), one at a time or in bulk; codes shall be unique. | M | E014, E024 |
| FR-DMG-12 | The system shall refuse to delete a violation code that existing violation logs reference. | M | E014, E024 |
| FR-DMG-13 | A Driver Manager shall be able to log one or many violations against a driver (code, offence instance, fine, place, date, time, optional remarks); the incident date cannot be in the future. | M | E014 |
| FR-DMG-14 | A Driver Manager shall be able to search the violation log by driver name or licence number and by date range, 10 entries per page. | S | E014 |

## 7. Fleet & maintenance (FR-FLT)

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-FLT-01 | A Maintenance Manager (or Admin) shall be able to list the fleet and search, filter (status, fuel type, assigned/unassigned) and sort it, 10 per page. | M | E015 |
| FR-FLT-02 | They shall be able to add and edit a vehicle (year 1990–2030, brand, model, unique plate number, status, acquisition date, optional VIN, fuel, tank, location, expected disposal date after acquisition, assigned driver) and view its full record. | M | E015 |
| FR-FLT-03 | A vehicle's status shall be one of `active`, `inactive`, `maintenance`, `disposed`; only `active` and `inactive` vehicles may carry a driver, and moving a vehicle to another status shall detach its driver. | M | E015 |
| FR-FLT-04 | The assignable-driver list shall offer only approved, non-suspended drivers who hold no other vehicle, and the server shall enforce one vehicle per driver. | M | E015 |
| FR-FLT-05 | The system shall refuse to dispose of a vehicle while its driver is clocked in. | S | E015 |
| FR-FLT-06 | The system shall refuse to delete a vehicle that has maintenance records (it must be marked `disposed` instead) or that still has a driver. | M | E015 |
| FR-FLT-07 | A Maintenance Manager shall be able to see, per vehicle, one card per maintenance task with last service date, odometer, cost, comments and the **computed** next due date and odometer. | M | E016 |
| FR-FLT-08 | They shall be able to log a service for a (vehicle, task) pair — odometer ≥ 0, date, cost ≥ 0, optional comments — which replaces that pair's previous record and feeds the dashboard history and cost analytics. | M | E016 |
| FR-FLT-09 | The system shall reject an odometer reading lower than the last reading recorded for that vehicle. | M | E016 |
| FR-FLT-10 | A Maintenance Manager shall be able to switch the schedule between vehicles. | S | E016 |
| FR-FLT-11 | A Maintenance Manager shall be able to add, edit and delete maintenance tasks (name up to 255 characters, optional kilometre and month intervals), 10 per page. | M | E017 |

## 8. Administration (FR-ADM)

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-ADM-01 | An Admin shall be able to list commuters with search and filters (all, verified, pending, active, suspended), showing registration date and wallet balance, 10 per page. | M | E018 |
| FR-ADM-02 | An Admin shall be able to add a commuter (email, password, optional "mark verified"), edit email/password/verification, and view details (balance, fares paid, total spent, last fare, top-up count). | M | E018 |
| FR-ADM-03 | An Admin shall be able to suspend and reactivate a commuter; wallet and receipts are kept. | M | E018 |
| FR-ADM-04 | The system shall refuse to remove a commuter whose wallet holds more than ₱0, and shall refuse an Admin deleting or suspending their own account. A commuter with payments or top-ups shall be anonymised, not deleted, and the confirmation shall say their records were kept (FR-ACC-23). | M | E018, E028 |
| FR-ADM-05 | An Admin shall be able to upload the official fare guide (PDF) and the system shall extract its distance tiers and create a new fare set. | M | E019 |
| FR-ADM-06 | An Admin shall be able to edit regular and discount rates in bulk; the whole save is rejected if it is empty or any rate is zero or negative. | M | E019 |
| FR-ADM-07 | Fare pricing and the map's rate display shall always use the most recent fare set. | M | E019, E004, E006 |
| FR-ADM-08 | An Admin shall be able to generate a **Financial Overview**, **Driver Performance** or **Maintenance & Fleet** report for a date range. | M | E020 |
| FR-ADM-09 | The system shall reject a date range whose start is after its end, show a *no data* message with zeroed figures when nothing matches, and warn when the newest transaction is over a day old. | S | E020 |
| FR-ADM-10 | An Admin shall be able to export the generated report as a CSV file named by type and date. | S | E020 |
| FR-ADM-11 | An Admin shall be able to list staff accounts (Driver Managers and Maintenance Managers) and filter by email, role and status, 10 per page. | M | E028 |
| FR-ADM-12 | An Admin shall be able to create a staff account (email, role, password), edit email/role/password (changing the role replaces the old one), suspend/reactivate and delete it. | M | E028 |
| FR-ADM-13 | The staff pages shall refuse any non-Admin and any target that is not a staff account. | M | E028 |
| FR-ADM-14 | An Admin shall be able to review the audit trail newest first, 10 entries per page; each entry shows when (Philippine time), who (email and role, or *not signed in / system*), what happened (category, event, description), the record concerned and the IP address, and can be expanded to show the before → after values, any extra facts (e.g. a suspension reason) and the device. | M | E029 |
| FR-ADM-15 | An Admin shall be able to search the trail by text (description, the acting user's email, or the email a failed sign-in tried) and filter it by category, event and date range; filters shall combine and survive paging, an end date before the start date shall be rejected, and an unknown category or event shall be refused. | M | E029 |
| FR-ADM-16 | An Admin shall be able to export the entries the current filters match as a CSV (newest 10,000 at most) in which spreadsheet formulas are neutralised; the export shall itself be recorded in the trail with the row count and filters. | S | E029 |
| FR-ADM-17 | The Activity Log shall be read-only and available to the Admin role only; viewing it shall write nothing, entries from the retired noisy logging shall be hidden, and secret-looking values shall be masked and all content escaped on screen. | M | E029 |
| FR-ADM-18 | Exporting a system report (E020) shall be recorded in the trail with who exported which report and for which date range. | S | E020, E029 |

## 9. Dashboards & profile (FR-DSH)

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-DSH-01 | A Commuter shall have a profile page showing their fare payments, top-ups and wallet balance. | S | E026 |
| FR-DSH-02 | An **Admin** dashboard shall show total revenue, total funds added, active users, the 5 latest fares and top-ups, and 7-day revenue and top-up series. | S | E026 |
| FR-DSH-03 | A **Driver** dashboard shall show driver info, today's record, weekly hours and overtime, the assigned vehicle, the last 7 days of timekeeping and the latest violations with totals. | S | E026 |
| FR-DSH-04 | A **Driver Manager** dashboard shall list drivers (with licence expiry), timekeeping logs and violation logs. | S | E026 |
| FR-DSH-05 | A **Maintenance Manager** dashboard shall show fleet information, a cost summary (total cost, services, average per service, cost per km) and service history for a selected vehicle. | S | E026 |
| FR-DSH-06 | The system shall redirect a user to the map with a message when they open the profile (non-commuter) or dashboard (commuter) page that is not meant for their role. | S | E026 |

## 10. Access control (FR-RBAC)

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-RBAC-01 | The system shall give every account a role (commuter, driver, driver manager, maintenance manager or admin) and restrict each page to the roles that may use it. | M | E012–E028 |
| FR-RBAC-02 | A request from a visitor who is not signed in shall be redirected to login; a request from the wrong role shall be refused (403). | M | E024, E028 |
| FR-RBAC-03 | Payment pages shall require a signed-in, email-verified user. | M | E006, E001 |
| FR-RBAC-04 | Staff and driver accounts shall be created only by staff action or the driver registration flow; the system shall not let a commuter obtain a staff role. | M | E002, E012, E028 |
| FR-RBAC-05 | Admin accounts shall not be creatable or removable from the staff screen. | M | E028 |

## 11. Audit trail (FR-AUD)

What the system records so that *who did what to which record, when and from where* can be answered. Reviewing it is FR-ADM-14 … 18. Design and event list:
[AUDIT_TRAIL.md](AUDIT_TRAIL.md).

| ID | Requirement | Pri | Source |
|---|---|:-:|---|
| FR-AUD-01 | After it happens, the system shall record every sign-in, failed sign-in (with the email tried, never the password), sign-in blocked because the account is suspended, sign-out, password change, password reset, password set by an admin, and "sign out other devices". | M | E001, E008, E011, E018, E028 |
| FR-AUD-02 | The system shall record account events with the acting user: registration (commuter and driver), account created by an admin, suspended, reactivated, role changed, deleted or anonymised by an admin, deleted or anonymised by its owner (with the outcome), and driver approved or rejected. | M | E002, E012, E018, E022, E027, E028 |
| FR-AUD-03 | The system shall record every create, update and delete of vehicles, maintenance tasks and service records, violation codes and violation logs, time entries, fare guides and payments and top-ups, naming the record and the acting user and storing the old and new value of each business field that changed; a bulk fare-rate edit and a fare-guide import shall each be one entry. | M | E005, E006, E009, E010, E013 – E017, E019, E024 |
| FR-AUD-04 | Every entry shall carry the IP address, the user agent and a snapshot of the acting user's email and role, so it still names the actor after that account is deleted. | M | E029 |
| FR-AUD-05 | The system shall never store a password, a hash, a token or licence-image data in an entry, and shall not record a change in which only unaudited fields moved. | M | E029 |
| FR-AUD-06 | The system shall not record page views, map polling, location broadcasts, driver availability toggles or clock-in status flips; database and gateway failures shall go to the application log instead. | M | E003, E009, E021 |
| FR-AUD-07 | A failure to write an entry shall never stop the action it describes, and shall be written to the application log. | S | E029 |
| FR-AUD-08 | Entries older than the retention period (365 days, `ACTIVITY_LOG_RETENTION_DAYS`) shall be deleted daily. | S | E029 |

---

## 12. Role capability summary

| Role | Can |
|---|---|
| **Guest** | Track PUJs (FR-MAP), plan a trip (FR-TRP), register as commuter or driver, sign in, reset password |
| **Commuter** | Everything a Guest can on the map, top up, pay fares, view payment and top-up history, profile, change password, delete account |
| **Driver** | Clock in/out, set availability, broadcast location, weekly timekeeping, violations, dashboard, change password, delete account (if no vehicle) |
| **Driver Manager** | Manage drivers (add, edit, approve/reject, suspend, delete), timekeeping ledger, violation codes and log, dashboard |
| **Maintenance Manager** | Manage vehicles, preventive maintenance schedule, maintenance tasks, dashboard |
| **Admin** | Manage commuters, fare rates, staff accounts; reports and CSV export; the Activity Log (audit trail) and its export; top-up and payment ledgers; may also use the driver and vehicle pages; dashboard |

---

## 13. Documented gaps (candidate requirements)

These are **not implemented**. They are taken from the *Known Gaps / Notes* of the UCNs and are written as requirements so the
team can accept, defer or drop each one. They are not counted in sections 1–12.

| ID | Candidate requirement | Why it matters | Source |
|---|---|---|---|
| FR-GAP-01 | Paying a fare shall issue a boarding permission (ticket / one-time ride token) that a driver or vehicle can verify. | Today a payment is a ledger entry only; nothing proves a rider paid. The UCN calls it the largest functional gap. | E006 |
| FR-GAP-02 | The system shall price the trip's **distance** on the server instead of trusting the browser's routing call. | The fare can no longer be tampered with but the distance still can. | E006 |
| FR-GAP-03 | Discounted fares (Student / Elderly / PWD) shall be applied only after the rider's eligibility is verified. | The calculator shows a discount but only the regular fare is charged. | E006 |
| FR-GAP-04 | A Commuter shall be able to request a refund or dispute a fare; an Admin shall be able to settle it. | Paid payments are immutable and there is no recourse. | E006 |
| FR-GAP-05 | A top-up shall go through the payment gateway (with a pending → settled state) rather than crediting the wallet on form submit. | No gateway, transaction id or signature check on top-ups. | E005 |
| FR-GAP-06 | An Admin shall be able to record a cash / admin-settlement top-up. | The commuter option was removed and no admin UI replaced it. | E005 |
| FR-GAP-07 | Receipts and top-up confirmations shall be sent by email. | Receipts are in-app only. | E005, E006 |
| FR-GAP-08 | The owner of an account shall be notified by email when their password is changed or reset. | Only the in-app activity log records it. | E008, E011 |
| FR-GAP-09 | The system shall enforce a password policy beyond length (complexity, reuse prevention). | Length-only today. | E008 |
| FR-GAP-10 | A user shall be able to see and end individual signed-in sessions. | Sessions can only be ended all at once. | E008 |
| FR-GAP-11 | A Driver Manager shall be able to edit or delete a time entry, and to adjust a driver's self-recorded shift. | Entries are immutable; corrections are impossible. | E010, E013 |
| FR-GAP-12 | Shifts shall be allowed to span midnight and a driver to have more than one shift per day. | Overnight shifts are rejected; one shift per day is enforced. | E009, E013 |
| FR-GAP-13 | Clock-in and clock-out shall verify the driver's location and vehicle. | Any device, anywhere, can clock in or out. | E009, E010 |
| FR-GAP-14 | The Maintenance Manager shall be notified when a task becomes overdue, and the schedule shall keep service history rather than overwrite it. | No overdue alert; only the latest service per task survives. | E016 |
| FR-GAP-15 | Maintenance tasks shall be deactivatable instead of deletable, and unique by name. | Deleting a task erases its cost history; duplicates are allowed. | E017 |
| FR-GAP-16 | Suspensions shall support an expiry or automatic reactivation, and commuter suspension shall record a reason. | Suspension is open-ended; commuters carry no reason. | E012, E018 |
| FR-GAP-17 | A rejected driver application shall be re-approvable, and approval shall be reversible. | Reject is one-way in the UI; "un-approve" is commented out. | E012, E014 |
| FR-GAP-18 | Account deletion shall handle a database failure with a friendly message. | A failure while deleting your own account shows the default 500 page, after the sign-out has already happened. | E027 |
| FR-GAP-19 | The Terms of Service and Privacy Policy shall exist as real pages. | The registration links are placeholders. | E002 |
| FR-GAP-20 | Reports shall be date-filtered end to end and exports shall be audited; an `.xlsx` export shall be offered. | Driver and maintenance reports ignore parts of the range; no audit trail; CSV only. | E020 |
| FR-GAP-21 | The fare-guide importer shall accept guides with a different layout and shall retire the previous fare set. | Two fixed row bands; old sets accumulate. | E019 |
| FR-GAP-22 | A staff user shall have a profile page of their own. | Only commuters have `/profile`. | E026 |
| FR-GAP-23 | The history and aggregate pages shall show a distinct error state instead of an empty list when a query fails. | "No results" and "something broke" look identical. | E007 |
