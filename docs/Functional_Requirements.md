# Functional Requirements — SmartCommute

What the system **shall do**, one requirement per use case. Each requirement is derived from, and numbered after, its
[Use Case Narrative](UCN/README.md): **`FR-E006` is the requirement for `UCN_SC_E006` (Pay Fare)**. See also the
[use-case diagram](Use_Case_Diagram.md). Quality attributes (speed, security, usability…) are in
[Non-Functional Requirements](Non_Functional_Requirements.md).

## How to read this document

- **One requirement per UCN.** There are **28** — one for each narrative `UCN_SC_E001` – `E028` — and each takes its narrative's number. A new use case gets the next free number and, with it, its own requirement carrying that same number.
- **Satisfied when** — the bullets under each requirement are its acceptance criteria: the observable rules that make the requirement true. They are not separate requirements; the full flows, exceptions and messages stay in the UCN.
- **Priority** — MoSCoW: **M**ust (core journey; the product does not work without it) or **S**hould (important, a workaround exists). The UCNs carry no priorities, so these are a proposal for the team to confirm.
- Every requirement describes behaviour the UCNs record as **implemented**. What they record as **missing** is in [Known gaps](#known-gaps-candidate-changes) — those are *not* requirements and are not counted.
- Who may use a feature is part of each requirement. Roles: *Guest*, *Commuter*, *Driver*, *Driver Manager*, *Maintenance Manager*, *Admin* — see [Use_Case_Diagram.md](Use_Case_Diagram.md#1-actors).
- Message wording, field names and route names are left to the UCNs; this document states the rule, not the copy.

## Summary

| ID | Use case | Primary actor | Pri |
|---|---|---|:-:|
| [FR-E001](#fr-e001--login) | Login | Guest | M |
| [FR-E002](#fr-e002--create-account) | Create Account | Guest | M |
| [FR-E003](#fr-e003--track-puj) | Track PUJ | Guest / Commuter | M |
| [FR-E004](#fr-e004--plan-trip) | Plan Trip | Guest / Commuter | M |
| [FR-E005](#fr-e005--topup-balance) | Topup Balance | Commuter | M |
| [FR-E006](#fr-e006--pay-fare) | Pay Fare | Commuter | M |
| [FR-E007](#fr-e007--view-payment-history) | View Payment History | Commuter | S |
| [FR-E008](#fr-e008--change-password) | Change Password | Any signed-in user | M |
| [FR-E009](#fr-e009--clock-in) | Clock In | Driver | M |
| [FR-E010](#fr-e010--clock-out) | Clock Out | Driver | M |
| [FR-E011](#fr-e011--reset-password) | Reset Password | Guest | M |
| [FR-E012](#fr-e012--manage-drivers) | Manage Drivers | Driver Manager / Admin | M |
| [FR-E013](#fr-e013--manage-timekeeping) | Manage Timekeeping | Driver Manager | M |
| [FR-E014](#fr-e014--manage-violations) | Manage Violations | Driver Manager | M |
| [FR-E015](#fr-e015--manage-vehicles) | Manage Vehicles | Maintenance Manager / Admin | M |
| [FR-E016](#fr-e016--manage-maintenance-schedule) | Manage Maintenance Schedule | Maintenance Manager | M |
| [FR-E017](#fr-e017--manage-maintenance-tasks) | Manage Maintenance Tasks | Maintenance Manager | S |
| [FR-E018](#fr-e018--manage-commuters) | Manage Commuters | Admin | M |
| [FR-E019](#fr-e019--manage-fare-rates) | Manage Fare Rates | Admin | M |
| [FR-E020](#fr-e020--view-system-reports) | View System Reports | Admin | S |
| [FR-E021](#fr-e021--share-vehicle-location-driver-broadcast) | Share Vehicle Location (Driver Broadcast) | Driver | M |
| [FR-E022](#fr-e022--driver-registration) | Driver Registration | Guest | M |
| [FR-E023](#fr-e023--driver-dashboard-views) | Driver Dashboard Views | Driver | S |
| [FR-E024](#fr-e024--admin-violation-code-management) | Admin Violation Code Management | Driver Manager | M |
| [FR-E025](#fr-e025--profile-and-dashboard-views) | Profile and Dashboard Views | Any signed-in user | S |
| [FR-E026](#fr-e026--account-deletion) | Account Deletion | Any signed-in user | S |
| [FR-E027](#fr-e027--manage-staff-accounts) | Manage Staff Accounts | Admin | M |
| [FR-E028](#fr-e028--view-activity-log) | View Activity Log | Admin | M |

---

## FR-E001 — Login

**Source:** [UCN_SC_E001](UCN/UCN_SC_E001_Login.md) · **Actor:** Guest · **Priority:** Must

**The system shall let a registered user sign in with their email and password, start a fresh session, and take them to the map.**

Satisfied when:
- a wrong email and a wrong password get the **same** message, and empty or malformed fields are rejected before the database is queried;
- a suspended account is refused, and an unverified user is held on the email-verification page (which can resend the link and code, or take the code);
- a user can ask to be remembered on a device; a visitor opening a protected page is sent to login, and a signed-in user opening login is sent to the map;
- a driver whose licence expires within 30 days, or has expired, is warned at **every** sign-in;
- every sign-in, failed sign-in (with the email tried, never the password) and blocked sign-in is recorded in the audit trail (see FR-E028).

## FR-E002 — Create Account

**Source:** [UCN_SC_E002](UCN/UCN_SC_E002_Create_Account.md) · **Actor:** Guest · **Priority:** Must

**The system shall let a Guest register a commuter account with an email address, a password and acceptance of the terms.**

Satisfied when:
- the email is unique and well-formed, the password is at least 8 characters and confirmed, and the terms are accepted — each failure gets its own message and creates nothing;
- on success the user (unverified, `commuter` role) and an empty wallet are created together or not at all, the user is signed in, and an email carrying a signed verification link and a 6-digit code (valid 60 minutes, hashed in storage, burned after 5 wrong guesses) is sent — re-sendable and throttled, a new email replaces the old code, and either the link or the code verifies the address;
- a Guest who pressed *Buy a Ride* on the map is taken to registration with the chosen pick-up and destination kept;
- registration is rate-limited and recorded in the audit trail.

## FR-E003 — Track PUJ

**Source:** [UCN_SC_E003](UCN/UCN_SC_E003_Track_PUJ.md) · **Actors:** Guest, Commuter · **Priority:** Must

**The system shall show an interactive map, open to Guests, with the live position of every available PUJ.**

Satisfied when:
- the map centres on the user's position when permission is granted, and a locate control re-centres it;
- a marker appears for every roadworthy vehicle (`active` or `inactive`) that has reported within the last 5 minutes, coloured by its driver's availability (green available, red unavailable, blue unknown), updating in real time with a polling fallback;
- tapping a marker shows plate / route, an **approximate** location (never exact — see FR-E021), the driver's availability and an ETA; ETA badges show within 5 km and the nearest PUJ within 10 km is offered as a shortcut;
- a vehicle set to `maintenance` or `disposed` leaves the map immediately;
- one status banner at a time explains offline, map-service, routing, location-denied and no-PUJ conditions, in that priority; a Guest is limited to 3 fare lookups per day;
- a vehicle whose driver's shift the system closed automatically (FR-E010) leaves the map at once, while its GPS history is kept.

## FR-E004 — Plan Trip

**Source:** [UCN_SC_E004](UCN/UCN_SC_E004_Plan_Trip.md) · **Actors:** Guest, Commuter · **Priority:** Must

**The system shall let a user choose a pick-up and a destination and shall show the route, the distance and the fare.**

Satisfied when:
- points are set by tapping the map or searching a place (from 2 characters, up to 6 matches, inside the service area); an address that cannot be resolved falls back to its coordinates;
- the recommended driving route and its distance in kilometres are drawn, and the regular and discounted fare are shown from the active fare table (highest distance tier not above the distance, rounded up to whole pesos);
- a reset action clears the points, route and fare; a routing failure clears distance and fare with an explanation; with no fare rates the distance still shows and the fare reads 0;
- *Buy a Ride* takes a Commuter to payment and a Guest to registration, and is rejected while either point is missing.

## FR-E005 — Topup Balance

**Source:** [UCN_SC_E005](UCN/UCN_SC_E005_Topup_Balance.md) · **Actor:** Commuter (Admin monitors) · **Priority:** Must

**The system shall let a Commuter add money to their wallet.**

Satisfied when:
- the amount is a preset (₱50 / 100 / 200 / 500) or a custom ₱10 – ₱100,000, paid by a self-service method (GCash or Maya);
- success credits exactly that amount and writes a top-up history record; a repeat of the same top-up within 2 minutes is credited only once;
- a Commuter can search and filter their own top-up history and see the total added;
- an Admin can list every top-up with its status and filter by search, status, method, date and amount, with the "funds added" total counting completed top-ups only;
- the Commuter is emailed a receipt for each completed top-up (amount, method, new balance); a failed email never fails the top-up. Top-ups are credited at once and are not gateway payments, so none is left pending.

## FR-E006 — Pay Fare

**Source:** [UCN_SC_E006](UCN/UCN_SC_E006_Pay_Fare.md) · **Actor:** Commuter · **Priority:** Must

**The system shall let a Commuter pay for a planned trip by Wallet, GCash or Maya, on board, at a fare the server prices itself.**

Satisfied when:
- before the checkout opens, the Commuter has **scanned a QR code** the Admin generated (inside the PUJ, in the app or with the phone camera); a scan lasts 15 minutes and covers one fare, and a payment without a fresh scan is refused (`REQUIRE_QR_SCAN`, on by default);
- the fare is **re-priced on the server** from the published rate table and shown with the wallet balance and the three methods; an unknown method, or an empty fare table, is refused;
- a **Wallet** payment deducts the fare and records a paid payment, or is refused with no change when the balance is short;
- a **GCash / Maya** payment opens a hosted checkout, is recorded as `pending` without touching the wallet, and is settled by a signed webhook, by reconciling with the gateway on return, or by a scheduled reconciliation every 10 minutes that catches a missed webhook; it ends `paid`, `failed` or `cancelled` (also after 12 hours open) and never charges the wallet;
- with the gateway disabled a GCash / Maya payment is recorded as declared and paid, with no external call;
- every payment has a unique transaction id and a replay never charges twice; the receipt is rendered from the stored record, so refreshing creates no second payment;
- the Commuter is **emailed one receipt** when a fare is paid (by any route) and an email when a gateway payment fails; a failed email never changes the payment;
- the checkout sends the Commuter's position so the server can match it to a PUJ that is broadcasting within about 150 m, and the payment then records that **vehicle and its driver on duty**; the position itself is never stored. When `QR_REQUIRE_NEARBY_VEHICLE` is on (off by default) a scan that was not matched to a vehicle cannot pay;
- repeated payment-gateway errors (3 in 15 minutes) email the Admins once, and a Commuter with 3 or more scans never followed by a fare in 7 days is flagged to the Admins.

## FR-E007 — View Payment History

**Source:** [UCN_SC_E007](UCN/UCN_SC_E007_View_Payment_History.md) · **Actor:** Commuter (Admin audits) · **Priority:** Should

**The system shall let a Commuter review their own past fare payments.**

Satisfied when:
- only that Commuter's payments are listed, newest first, 10 per page, with the wallet balance and the total spent on settled fares;
- they can search by transaction id or destination and filter by a quick range (7 / 30 / 90 days) or explicit dates, kept across pages;
- opening a receipt shows one payment and can be downloaded as an image; a receipt that does not exist or belongs to someone else returns *not found*;
- an Admin can open any payment's receipt through the fare-transactions ledger, whatever its status.

## FR-E008 — Change Password

**Source:** [UCN_SC_E008](UCN/UCN_SC_E008_Change_Password.md) · **Actor:** any signed-in user · **Priority:** Must

**The system shall let a signed-in user change their own password.**

Satisfied when:
- the current password is required and must be correct; the new one is at least 8 characters, confirmed, and different from the current one;
- a change ends the user's other sessions, and the user can also sign out all other devices without changing the password;
- both are recorded in the audit trail without the password.

## FR-E009 — Clock In

**Source:** [UCN_SC_E009](UCN/UCN_SC_E009_Clock_In.md) · **Actor:** Driver · **Priority:** Must

**The system shall let a Driver start their shift once per day.**

Satisfied when:
- clocking in creates today's timekeeping record with the current time (Asia/Manila) and sets the driver `active`;
- a second clock-in on the same day, or one by a user with no driver profile, is refused; a suspended or unapproved driver cannot clock in;
- a driver can only clock in on a day they may drive: they must have a vehicle assigned and, when two drivers share it, today must be their day on the duty roster (FR-E015); otherwise they are told why;
- a shift the system has already closed automatically today (FR-E010) counts as the day's shift, so a second clock-in is refused;
- clocking in clears an inactivity warning (FR-E012).

## FR-E010 — Clock Out

**Source:** [UCN_SC_E010](UCN/UCN_SC_E010_Clock_Out.md) · **Actor:** Driver · **Priority:** Must

**The system shall let a Driver end their shift and shall calculate the hours worked.**

Satisfied when:
- clocking out stores the time-out, the hours worked (2 decimals) and the overtime beyond 8 hours, and sets the driver `inactive`;
- a clock-out with no open shift, or with no driver profile, is refused;
- a shift left open is **closed automatically**: after 14 hours, or once its date has passed, the system credits a regular 8-hour shift with no overtime, flags it as auto-closed for review (FR-E013), sets the driver inactive unless a newer shift is open, takes the vehicle off the map (keeping its history) and stops the driver's phone broadcasting until the next shift; the close is audited.

## FR-E011 — Reset Password

**Source:** [UCN_SC_E011](UCN/UCN_SC_E011_Reset_Password.md) · **Actor:** Guest · **Priority:** Must

**The system shall let a user who forgot their password set a new one through a link sent by email.**

Satisfied when:
- the link is time-limited (60 minutes) and single-use, and the new password is at least 8 characters and confirmed;
- an unknown email, an expired or used link, and a mail failure each fail with a message and change nothing;
- a successful reset invalidates remembered logins and is recorded in the audit trail.

## FR-E012 — Manage Drivers

**Source:** [UCN_SC_E012](UCN/UCN_SC_E012_Manage_Drivers.md) · **Actors:** Driver Manager, Admin · **Priority:** Must

**The system shall let a Driver Manager or Admin review, add, edit, approve, reject, suspend and remove driver accounts.**

Satisfied when:
- the list searches and filters by status (all, pending, approved, rejected, suspended) and licence state (expiring, expired), 10 per page, and shows each driver's licence validity;
- an added driver (name, email, password, licence number / code / expiry, Philippine mobile number, licence image) gets a generated driver code and is approved at once; duplicate emails and licence numbers are refused; a **pending** registration can only be approved or rejected, not edited;
- an approved driver can be **suspended** (optional reason) and reactivated — suspension blocks sign-in, clock-in and broadcasting, takes effect on an open session, and detaches the driver's vehicle;
- a driver can be deleted unless a vehicle is still assigned; one who also has payments or top-ups is anonymised instead (see FR-E026);
- every change is recorded in the audit trail;
- an Admin and every Driver Manager are **emailed when a driver applies**, and the driver is emailed when the application is approved or rejected;
- a driver who does not clock in is **warned by email after 3 days** (to contact HR) and, if they stay away more than 7 days and a day after the warning, **suspended automatically**; clocking in clears the warning and reactivating a driver restarts the count;
- a driver who leaves a vehicle because they were **suspended or their licence expired** (an expired-licence driver is taken off the vehicle, not suspended) leaves a **vacancy**, which the system fills with the best free driver — approved, not suspended, licence valid, no vehicle, no inactivity warning, fewest recent violations and overtime — rebuilding a 30-day alternating roster when the vehicle is shared again; the replacement and the Maintenance Managers are emailed, and a vacancy nobody can fill stays open, is retried hourly and is reported daily.

## FR-E013 — Manage Timekeeping

**Source:** [UCN_SC_E013](UCN/UCN_SC_E013_Manage_Timekeeping.md) · **Actor:** Driver Manager · **Priority:** Must

**The system shall let a Driver Manager read the timekeeping ledger and add shift and leave entries.**

Satisfied when:
- the ledger shows driver, date, shift, hours, overtime and type, and filters by All / Regular / Sick / Vacation / Overtime;
- a shift or leave (sick / vacation) entry can be added, with hours and overtime derived by the server;
- a clock-out not after the clock-in, and a shift overlapping an existing entry for that driver and date, are refused;
- a shift the system closed automatically (FR-E010) shows an *Auto-closed* badge, is listed under a *Needs review* filter with a count, and can be marked reviewed by a Driver Manager, which is audited; reviewing only acknowledges the entry and does not change its hours.

## FR-E014 — Manage Violations

**Source:** [UCN_SC_E014](UCN/UCN_SC_E014_Manage_Violations.md) · **Actor:** Driver Manager · **Priority:** Must

**The system shall let a Driver Manager maintain the violation codes and record violations against drivers.**

Satisfied when:
- violation codes (code, name, fines for the 1st, 2nd, 3rd and 4th+ offence) can be created singly or in bulk, edited and deleted; codes are unique and one in use cannot be deleted;
- one or many violations can be logged against a driver (code, offence instance, fine, place, date, time, optional remarks), and the incident date cannot be in the future;
- the log searches by driver name or licence number and by date range, 10 entries per page;
- choosing a driver and a violation code **selects the offense number** from how many times that code was already logged against the driver (and, in a bulk entry, earlier in the same batch) and fills in the matching fine; the manager can override it;
- the driver is **emailed** about each logged violation and each fine marked paid, and a driver with 3 or more violations in 30 days is flagged to the Driver Managers once per window.

## FR-E015 — Manage Vehicles

**Source:** [UCN_SC_E015](UCN/UCN_SC_E015_Manage_Vehicles.md) · **Actors:** Maintenance Manager, Admin · **Priority:** Must

**The system shall let a Maintenance Manager or Admin review, add, edit and remove the fleet's vehicles.**

Satisfied when:
- the fleet lists, searches, filters (status, fuel type, assigned / unassigned) and sorts, 10 per page, and each vehicle can be viewed in full;
- a vehicle has a unique plate number, a year from 1990 to 2030, a status of `active`, `inactive`, `maintenance` or `disposed`, and an expected disposal date after its acquisition date;
- a driver may be assigned only to an `active` vehicle, only if approved and not suspended, and only one vehicle each; **up to two drivers may share** a vehicle and take turns on a duty roster the Maintenance Manager generates (alternating, 2–120 days) and swaps by day, and a driver can only clock in, go active or broadcast on their own duty day; moving a vehicle to any other status detaches its drivers and, for `maintenance` / `disposed`, removes it from the live map;
- a vehicle cannot be disposed of while its driver is clocked in, and one with maintenance history or a driver cannot be deleted (it is marked `disposed` instead);
- a vehicle can be **added by scanning its papers with a phone**: the form shows a QR code, the phone photographs the Certificate of Registration (and the Official Receipt), the server reads them locally and fills in plate, chassis/VIN, make, model, year and fuel, marking each value *read clearly* or *check carefully* and flagging disagreement between the two papers; **the vehicle cannot be saved until the manager confirms they checked the details against the document**, the server enforces it, and the photos are deleted when the vehicle is saved, the form is closed or 15 minutes pass;
- a vehicle with an overdue or due-soon service is flagged on the list and the dashboard and, when switched on, cannot be set back to `active` while a service is overdue (FR-E016);
- a vehicle that loses a driver to suspension or an expired licence gets a replacement automatically (FR-E012), and one still waiting for a driver is listed on the dashboard.

## FR-E016 — Manage Maintenance Schedule

**Source:** [UCN_SC_E016](UCN/UCN_SC_E016_Manage_Maintenance_Schedule.md) · **Actor:** Maintenance Manager · **Priority:** Must

**The system shall show a vehicle's preventive-maintenance schedule and let a Maintenance Manager log a service against it.**

Satisfied when:
- each task shows its interval, last service date, odometer, cost, comments and the **computed** next due date and odometer, and the schedule can be switched between vehicles;
- a logged service (odometer ≥ 0 and not lower than the vehicle's last reading, date, cost ≥ 0, optional comments) replaces that task's previous record and feeds the dashboard history and cost figures;
- a service **due soon** (within 7 days or 500 km) or **overdue** is flagged on the schedule, the vehicle list and the dashboard and is emailed to the Maintenance Managers in a daily digest; the vehicle's current reading is the highest odometer logged for it, and a task with no interval is never due.

## FR-E017 — Manage Maintenance Tasks

**Source:** [UCN_SC_E017](UCN/UCN_SC_E017_Manage_Maintenance_Tasks.md) · **Actor:** Maintenance Manager · **Priority:** Should

**The system shall let a Maintenance Manager add, edit and delete the maintenance tasks used across the fleet.**

Satisfied when:
- a task has a name of up to 255 characters and optional kilometre and month intervals, and the list is paged 10 per page;
- the intervals drive the projected next-due values on the maintenance schedule (FR-E016).

## FR-E018 — Manage Commuters

**Source:** [UCN_SC_E018](UCN/UCN_SC_E018_Manage_Commuters.md) · **Actor:** Admin · **Priority:** Must

**The system shall let an Admin review, add, edit, suspend and remove commuter accounts.**

Satisfied when:
- the list searches and filters (all, verified, pending, active, suspended) 10 per page and shows registration date and wallet balance; a detail page shows balance, fares paid, total spent, last fare and top-up count;
- an Admin can add a commuter (optionally marked verified) and edit their email, password and verification; a commuter can be suspended and reactivated, keeping their wallet and receipts;
- removal is refused while the wallet holds money, and an Admin cannot delete or suspend their own account;
- a commuter with payments or top-ups is **anonymised**, not deleted, and the confirmation says their records were kept (see FR-E026).

## FR-E019 — Manage Fare Rates

**Source:** [UCN_SC_E019](UCN/UCN_SC_E019_Manage_Fare_Rates.md) · **Actor:** Admin · **Priority:** Must

**The system shall let an Admin load the official fare guide and edit the fare rates.**

Satisfied when:
- an uploaded fare guide (PDF) is parsed into distance tiers and stored as a new fare set;
- regular and discount rates can be edited in bulk; the whole save is refused if it is empty or any rate is zero or negative;
- fare pricing and the map's rate display always use the most recent fare set;
- an import and each bulk edit (per-tier old → new) are recorded in the audit trail.

## FR-E020 — View System Reports

**Source:** [UCN_SC_E020](UCN/UCN_SC_E020_View_System_Reports.md) · **Actor:** Admin · **Priority:** Should

**The system shall let an Admin generate a report for a date range and export it.**

Satisfied when:
- a **Financial Overview**, **Driver Performance** or **Maintenance & Fleet** report can be generated for a chosen range;
- a start date after the end date is refused; an empty result says so with zeroed figures; a ledger whose newest transaction is over a day old shows a staleness warning;
- the report can be exported as a CSV named by type and date, and the export is recorded in the audit trail;
- the Admin is **emailed a weekly and a monthly report** (revenue by method, top-ups, failed payments, violations and fines, hours and overtime, distance, maintenance spend, overdue services, repeat violators, unpaid scans) with the figures — and optionally the period's audit trail — as CSV attachments, and each send is audited;
- **Fleet Analytics** shows, for the last 7, 30 or 90 days, hours, overtime, distance, fare revenue, violations and maintenance cost per driver and per vehicle, an 8-week hours-and-overtime trend, repeat violators and commuters with unpaid scans, computed from the stored records; it is for the Admin only.

## FR-E021 — Share Vehicle Location (Driver Broadcast)

**Source:** [UCN_SC_E021](UCN/UCN_SC_E021_Share_Vehicle_Location.md) · **Actor:** Driver · **Priority:** Must

**The system shall let a Driver publish their vehicle's position so riders can track it, without revealing the exact position.**

Satisfied when:
- the Driver's browser sends the position at most once every 2 seconds, and the server accepts it only from a signed-in, non-suspended driver for the vehicle assigned to them (and `active`);
- the latest position is stored, a history entry is kept and the vehicle is marked active; a vehicle with no broadcast for 5 minutes is marked inactive;
- riders receive only a position moved to a random point within **200 m** of the real one — the exact coordinates are never published;
- a repeated timestamp is accepted as a no-op, and a temporary storage failure is retried once after 5 seconds;
- each stored position records the **kilometres driven** since the vehicle's previous ping (0 after a 10-minute gap or an implausible jump), which feeds the driver's distance today and Fleet Analytics;
- a broadcast is refused for a driver whose shift the system closed automatically (FR-E010) until their next shift.

## FR-E022 — Driver Registration

**Source:** [UCN_SC_E022](UCN/UCN_SC_E022_Driver_Registration.md) · **Actor:** Guest · **Priority:** Must

**The system shall let a Guest apply to become a driver and wait for approval.**

Satisfied when:
- the application takes name, email, Philippine contact number, password (≥ 8, confirmed), accepted terms and a licence image (JPG / PNG, up to 2 MB);
- the user, the driver profile (with a generated driver code), the `driver` role and the stored image are created together or not at all, and the applicant is sent to the login page;
- the applicant cannot sign in to driver features until a Driver Manager approves (FR-E012); registration is rate-limited and recorded in the audit trail;
- every Admin and Driver Manager is emailed when an application is submitted.

## FR-E023 — Driver Dashboard Views

**Source:** [UCN_SC_E023](UCN/UCN_SC_E023_Driver_Dashboard_Views.md) · **Actor:** Driver · **Priority:** Should

**The system shall let a Driver see their own timekeeping and violations and set their availability.**

Satisfied when:
- the weekly timekeeping view shows hours, overtime and each day's status, and moves between weeks;
- the violations view shows the driver's own violations, read-only, with totals and the latest violation, 10 per page;
- the Driver can switch availability between `active` and `inactive`; any other value is rejected;
- a day whose shift was closed automatically is tagged as such, and the dashboard's distance today shows the kilometres recorded from the driver's broadcasts.

## FR-E024 — Admin Violation Code Management

**Source:** [UCN_SC_E024](UCN/UCN_SC_E024_Admin_Violation_Code_Management.md) · **Actor:** Driver Manager · **Priority:** Must

**The system shall let a Driver Manager create, view, edit and delete the violation codes that set the fines for each offence level.**

Satisfied when:
- a code has a unique code, a name and a fine (≥ 0) for each of the 1st, 2nd, 3rd and 4th+ offence, and can be added in bulk;
- a code referenced by any violation log cannot be deleted;
- the pages are available to the Driver Manager role only (others get 403, guests are sent to login);
- a fresh installation is seeded with the **violations most often written up against PUJ drivers** — 14 codes, `UV01`–`UV14`, each with four fine tiers — which the Driver Manager can edit; reseeding replaces the whole table (and the logs that belong to it).

## FR-E025 — Profile and Dashboard Views

**Source:** [UCN_SC_E025](UCN/UCN_SC_E025_Profile_and_Dashboard.md) · **Actor:** any signed-in user · **Priority:** Should

**The system shall show each user their own profile or a dashboard suited to their role.**

Satisfied when:
- a Commuter's profile shows their fare payments, top-ups and wallet balance;
- an **Admin** dashboard shows total revenue, funds added, active users, the five latest fares and top-ups and 7-day series; a **Driver** dashboard shows their details, today's record, weekly hours and overtime, assigned vehicle and recent violations; a **Driver Manager** dashboard lists drivers, timekeeping and violation logs; a **Maintenance Manager** dashboard shows fleet information, a cost summary and service history;
- a user who opens a page not meant for their role is returned to the map with a message;
- the **Maintenance Manager** dashboard also lists vehicles waiting for a driver and vehicles with overdue or due-soon services, each panel shown only when it has something to show.

## FR-E026 — Account Deletion

**Source:** [UCN_SC_E026](UCN/UCN_SC_E026_Account_Deletion.md) · **Actor:** any signed-in user · **Priority:** Should

**The system shall let a user delete their own account without destroying the payment records the business must keep.**

Satisfied when:
- the user confirms, is signed out and the session is invalidated;
- deletion is refused, leaving the user signed in, while the wallet holds money (there is no refund flow) or, for a driver, while a vehicle is still assigned;
- an account with **no payments or top-ups is deleted**; one **with** them is **anonymised** — email replaced by a placeholder, password, roles, tokens, sessions, preferences, reset links, map markers and any driver profile removed, the account suspended — and its wallet, payments and top-ups are kept;
- an anonymised account can never sign in and its original email can be registered again; the action is recorded once in the audit trail with its outcome.

## FR-E027 — Manage Staff Accounts

**Source:** [UCN_SC_E027](UCN/UCN_SC_E027_Manage_Staff_Accounts.md) · **Actor:** Admin · **Priority:** Must

**The system shall let an Admin create, review, edit, suspend and delete the back-office accounts of Driver Managers and Maintenance Managers.**

Satisfied when:
- the list searches by email and filters by role and status, 10 per page;
- an account is created with an email, a role and a password (the email is marked verified); edits can change the email, the role (replacing the old one) and the password; accounts can be suspended and reactivated;
- an Admin cannot suspend or delete their own account, a target that is not a staff account is refused, and the screen is for Admins only (admin accounts cannot be created or removed here);
- creation, role changes, suspension and deletion are recorded in the audit trail.

## FR-E028 — View Activity Log

**Source:** [UCN_SC_E028](UCN/UCN_SC_E028_View_Activity_Log.md) · **Actor:** Admin · **Priority:** Must

**The system shall record who did what to which record, and shall let an Admin review that audit trail.**

Satisfied when — **recording** (design and event list: [AUDIT_TRAIL.md](AUDIT_TRAIL.md)):
- after it happens, the system records every sign-in, failed and blocked sign-in, sign-out, password change and reset, account registration, creation, suspension, reactivation, role change and removal (deleted or anonymised), driver approval and rejection, and every create / update / delete of vehicles, maintenance tasks and service records, violation codes and logs, time entries, fare guides and fare rates, payments and top-ups — naming the record and the acting user, with the old and new value of each field that changed;
- the **automatic** jobs write to the same trail, with no signed-in actor: a shift closed or reviewed, an inactivity warning and an automatic suspension, a driver flagged as a repeat violator, a commuter flagged for unpaid scans, a vacancy opened and a replacement assigned, a driver released for an expired licence, a vehicle added from scanned papers (which fields were read and which the manager corrected, never the values), and each scheduled report sent;
- every entry carries the IP address, the user agent and a snapshot of the actor's email and role; passwords, tokens and licence-image data are never stored;
- page views, map polling, location broadcasts and availability toggles are never recorded; a failure to record never stops the action; entries older than 365 days are deleted daily.

and **reviewing**:
- the Admin sees the trail newest first, 10 per page, each entry showing when, who, what happened, the record and the IP, expandable to the before → after values and the device;
- it can be searched by text (description, the actor's email, or the email a failed sign-in tried) and filtered by category, event and date range, combined and kept across pages; an end date before the start date and an unknown category or event are refused;
- the entries the filters match can be exported as a CSV (newest 10,000, formulas neutralised), and the export is itself recorded;
- the page is read-only and for the Admin role only; viewing writes nothing, secret-looking values are masked and all content is escaped.

---

## Role capability summary

| Role | Requirements they take part in |
|---|---|
| **Guest** | FR-E001 sign in · FR-E002 register · FR-E003 track PUJs · FR-E004 plan a trip · FR-E011 reset a password · FR-E022 apply as a driver |
| **Commuter** | FR-E003, FR-E004 as a Guest, plus FR-E005 top up · FR-E006 pay a fare · FR-E007 payment history · FR-E008 change password · FR-E025 profile · FR-E026 delete account |
| **Driver** | FR-E008 · FR-E009 clock in · FR-E010 clock out · FR-E021 share location · FR-E023 timekeeping, violations, availability · FR-E025 dashboard · FR-E026 |
| **Driver Manager** | FR-E008 · FR-E012 drivers · FR-E013 timekeeping · FR-E014 violations · FR-E024 violation codes · FR-E025 dashboard |
| **Maintenance Manager** | FR-E008 · FR-E015 vehicles, duty roster and phone scan · FR-E016 maintenance schedule and due alerts · FR-E017 maintenance tasks · FR-E025 dashboard |
| **Admin** | FR-E008 · FR-E012 and FR-E015 (shared pages) · FR-E018 commuters · FR-E019 fare rates · FR-E020 reports, scheduled reports and Fleet Analytics · FR-E027 staff · FR-E028 activity log · FR-E005 / FR-E007 ledgers · FR-E006 gateway alerts and QR codes · FR-E025 dashboard |
| **System (scheduler)** | Runs with no user: FR-E010 closes forgotten shifts · FR-E012 warns and suspends inactive drivers and replaces drivers who leave a vehicle · FR-E006 reconciles payments · FR-E016 emails due services · FR-E014 flags repeat violators · FR-E020 sends the weekly and monthly reports · all listed in [AUTOMATED_FEATURES.md](AUTOMATED_FEATURES.md) |

---

## Known gaps (candidate changes)

These are **not requirements** and are not counted above: they are what the UCNs' *Known Gaps / Notes* record as **missing**, written down so the team can accept
one (it then becomes part of the matching `FR-Exxx`), defer it or drop it.

| ID | Candidate change | Why it matters | Affects |
|---|---|---|---|
| GAP-01 | Paying a fare shall issue a boarding permission (ticket / one-time ride token) that a driver or vehicle can verify. | A fare is now attributed to a vehicle and driver and unpaid scans are flagged, but a driver still has no screen that shows who has paid. | FR-E006 |
| GAP-02 | The system shall price the trip's **distance** on the server instead of trusting the browser's routing call. | The fare can no longer be tampered with but the distance still can. | FR-E006 |
| GAP-03 | Discounted fares (Student / Elderly / PWD) shall be applied only after the rider's eligibility is verified. | The calculator shows a discount but only the regular fare is charged. | FR-E006 |
| GAP-04 | A Commuter shall be able to request a refund or dispute a fare; an Admin shall be able to settle it (and a wallet balance could be refunded on account deletion). | Paid payments are immutable and there is no recourse; deletion is refused while the wallet holds money. | FR-E006, FR-E026 |
| GAP-05 | A top-up shall go through the payment gateway (with a pending → settled state) rather than crediting the wallet on form submit. | No gateway, transaction id or signature check on top-ups. | FR-E005 |
| GAP-06 | An Admin shall be able to record a cash / admin-settlement top-up. | The commuter option was removed and no admin UI replaced it. | FR-E005 |
| GAP-07 | Receipts and top-up confirmations shall be sent by email. | **Done**: fare receipts and top-up receipts are emailed (see UCN_SC_E005 and E006). | FR-E005, FR-E006 |
| GAP-08 | The owner of an account shall be notified by email when their password is changed or reset. | Only the audit trail records it. | FR-E008, FR-E011 |
| GAP-09 | The system shall enforce a password policy beyond length (complexity, reuse prevention). | Length-only today. | FR-E008 |
| GAP-10 | A user shall be able to see and end individual signed-in sessions. | Sessions can only be ended all at once. | FR-E008 |
| GAP-11 | A Driver Manager shall be able to edit or delete a time entry, and to adjust a driver's self-recorded shift. | Entries are immutable; corrections are impossible, so the hours credited to an auto-closed shift can only be acknowledged, not corrected. | FR-E010, FR-E013 |
| GAP-12 | Shifts shall be allowed to span midnight and a driver to have more than one shift per day. | Overnight shifts are rejected; one shift per day is enforced; and a shift still open after midnight is closed automatically. | FR-E009, FR-E013 |
| GAP-13 | Clock-in and clock-out shall verify the driver's location and vehicle. | Any device, anywhere, can clock in or out. | FR-E009, FR-E010 |
| GAP-14 | The schedule shall keep service history rather than overwrite it. | Only the latest service per task survives in the schedule (the Maintenance Logs page keeps the history). Overdue alerts now exist — see UCN_SC_E016. | FR-E016 |
| GAP-15 | Maintenance tasks shall be deactivatable instead of deletable, and unique by name. | Deleting a task erases its cost history; duplicates are allowed. | FR-E017 |
| GAP-16 | Suspensions shall support an expiry or automatic reactivation, and commuter suspension shall record a reason. | Suspension is open-ended; commuters carry no reason. | FR-E012, FR-E018 |
| GAP-17 | A rejected driver application shall be re-approvable, and approval shall be reversible. | Reject is one-way in the UI; "un-approve" is commented out. | FR-E012, FR-E014 |
| GAP-18 | Account deletion shall handle a database failure with a friendly message. | A failure while deleting your own account shows the default 500 page, after the sign-out has already happened. | FR-E026 |
| GAP-19 | The Terms of Service and Privacy Policy shall exist as real pages. | The registration links are placeholders. | FR-E002 |
| GAP-20 | Reports shall be date-filtered end to end, and an `.xlsx` export shall be offered. | Driver and maintenance reports ignore parts of the range; CSV only. | FR-E020 |
| GAP-21 | The fare-guide importer shall accept guides with a different layout and shall retire the previous fare set. | Two fixed row bands; old sets accumulate. | FR-E019 |
| GAP-22 | A staff user shall have a profile page of their own. | Only commuters have `/profile`. | FR-E025 |
| GAP-23 | The history and aggregate pages shall show a distinct error state instead of an empty list when a query fails. | "No results" and "something broke" look identical. | FR-E007 |
| GAP-24 | A vehicle's **registration expiry** (from its CR / OR) shall be recorded and an alert sent before it lapses. | The phone scan reads the plate, chassis, make, model, year and fuel but nothing records when the registration expires, so a lapsed registration is only noticed on the road. | FR-E015, FR-E016 |
| GAP-25 | Notifications shall also reach people **inside the app** and by push or SMS. | Every automatic message is an email today (`NOTIFICATION_CHANNELS` is ready for more channels), so someone without mail access still finds out only by opening a page. | FR-E005, FR-E006, FR-E012, FR-E014 |
| GAP-26 | The phone scan shall check that the papers are **genuine** (against the LTO, or a second source). | It reads what is printed, so a forged paper reads exactly like a genuine one; the manager's confirmation is the only check. | FR-E015 |
