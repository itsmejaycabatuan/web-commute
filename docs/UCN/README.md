# Use Case Narratives (UCN) — Web Commute

Documentation of the implemented behaviour of the system's core guest, commuter, staff and driver journeys.

| Use Case ID | Name | Actor | Document |
|---|---|---|---|
| UCN_SC_E001 | Login | Guest | [UCN_SC_E001_Login.md](UCN_SC_E001_Login.md) |
| UCN_SC_E002 | Create Account | Guest | [UCN_SC_E002_Create_Account.md](UCN_SC_E002_Create_Account.md) |
| UCN_SC_E003 | Track PUJ | Guest | [UCN_SC_E003_Track_PUJ.md](UCN_SC_E003_Track_PUJ.md) |
| UCN_SC_E004 | Plan Trip | Guest / Commuter | [UCN_SC_E004_Plan_Trip.md](UCN_SC_E004_Plan_Trip.md) |
| UCN_SC_E005 | Topup Balance | Commuter | [UCN_SC_E005_Topup_Balance.md](UCN_SC_E005_Topup_Balance.md) |
| UCN_SC_E006 | Pay Fare | Commuter | [UCN_SC_E006_Pay_Fare.md](UCN_SC_E006_Pay_Fare.md) |
| UCN_SC_E007 | View Payment History | Commuter | [UCN_SC_E007_View_Payment_History.md](UCN_SC_E007_View_Payment_History.md) |
| UCN_SC_E008 | Change Password | All users except Guest | [UCN_SC_E008_Change_Password.md](UCN_SC_E008_Change_Password.md) |
| UCN_SC_E009 | Clock In | Driver | [UCN_SC_E009_Clock_In.md](UCN_SC_E009_Clock_In.md) |
| UCN_SC_E010 | Clock Out | Driver | [UCN_SC_E010_Clock_Out.md](UCN_SC_E010_Clock_Out.md) |
| UCN_SC_E011 | Reset Password | Guest | [UCN_SC_E011_Reset_Password.md](UCN_SC_E011_Reset_Password.md) |
| UCN_SC_E012 | Manage Drivers | Driver Manager / Admin | [UCN_SC_E012_Manage_Drivers.md](UCN_SC_E012_Manage_Drivers.md) |
| UCN_SC_E013 | Manage Timekeeping | Driver Manager | [UCN_SC_E013_Manage_Timekeeping.md](UCN_SC_E013_Manage_Timekeeping.md) |
| UCN_SC_E014 | Manage Violations | Driver Manager | [UCN_SC_E014_Manage_Violations.md](UCN_SC_E014_Manage_Violations.md) |
| UCN_SC_E015 | Manage Vehicles | Maintenance Manager | [UCN_SC_E015_Manage_Vehicles.md](UCN_SC_E015_Manage_Vehicles.md) |
| UCN_SC_E016 | Manage Maintenance Schedule | Maintenance Manager | [UCN_SC_E016_Manage_Maintenance_Schedule.md](UCN_SC_E016_Manage_Maintenance_Schedule.md) |
| UCN_SC_E017 | Manage Maintenance Tasks | Maintenance Manager | [UCN_SC_E017_Manage_Maintenance_Tasks.md](UCN_SC_E017_Manage_Maintenance_Tasks.md) |
| UCN_SC_E018 | Manage Commuters | Admin | [UCN_SC_E018_Manage_Commuters.md](UCN_SC_E018_Manage_Commuters.md) |
| UCN_SC_E019 | Manage Fare Rates | Admin | [UCN_SC_E019_Manage_Fare_Rates.md](UCN_SC_E019_Manage_Fare_Rates.md) |
| UCN_SC_E020 | View System Reports | Admin | [UCN_SC_E020_View_System_Reports.md](UCN_SC_E020_View_System_Reports.md) |
| UCN_SC_E021 | Share Vehicle Location (Driver Broadcast) | Driver | [UCN_SC_E021_Share_Vehicle_Location.md](UCN_SC_E021_Share_Vehicle_Location.md) |
| UCN_SC_E022 | Driver Registration | Guest | [UCN_SC_E022_Driver_Registration.md](UCN_SC_E022_Driver_Registration.md) |
| UCN_SC_E023 | Driver Dashboard Views | Driver | [UCN_SC_E023_Driver_Dashboard_Views.md](UCN_SC_E023_Driver_Dashboard_Views.md) |
| UCN_SC_E024 | Admin Violation Code Management | Driver Manager | [UCN_SC_E024_Admin_Violation_Code_Management.md](UCN_SC_E024_Admin_Violation_Code_Management.md) |
| UCN_SC_E025 | Profile and Dashboard Views | All authenticated roles | [UCN_SC_E025_Profile_and_Dashboard.md](UCN_SC_E025_Profile_and_Dashboard.md) |
| UCN_SC_E026 | Account Deletion | Authenticated user | [UCN_SC_E026_Account_Deletion.md](UCN_SC_E026_Account_Deletion.md) |
| UCN_SC_E027 | Manage Staff Accounts | Admin | [UCN_SC_E027_Manage_Staff_Accounts.md](UCN_SC_E027_Manage_Staff_Accounts.md) |
| UCN_SC_E028 | View Activity Log | Admin | [UCN_SC_E028_View_Activity_Log.md](UCN_SC_E028_View_Activity_Log.md) |

> **Numbering note.** E011–E020 were reconciled against the implementation. Two sheets previously
> carried a duplicate identifier — *Manage Violations* shared `UCN_SC_E013` with *Manage Timekeeping*
> and *Manage Maintenance Schedule* shared `UCN_SC_E015` with *Manage Vehicles*. Violations are now
> **E014** and the maintenance schedule **E016**.

> **Renumbering (October 2026).** The set used to run E001 – E029 with a hole at **E025**, the separate *vehicle
> maintenance log* use case, which was retired when its data moved into the preventive-maintenance record (see
> UCN_SC_E016; the log itself has since returned as a panel on that page, but not as a use case). The four sheets after the hole were moved down by one so the numbers are contiguous and equal the
> number of sheets: **Profile and Dashboard Views E026→E025, Account Deletion E027→E026, Manage Staff Accounts
> E028→E027, View Activity Log E029→E028.** Anything written before this change (older documents, `UCN.zip`,
> commit messages) uses the old numbers for those four.

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
| **Audit trail** | The `activity_log` table: who did what to which record, when and from where, with before → after values. Written after the action succeeds; page views, map polling and location broadcasts are never recorded. Read by the Admin on the Activity Log page (UCN_SC_E028). Design and event list: `docs/AUDIT_TRAIL.md`. |

## Document conventions

- Each document follows the classic UCN layout: header, preconditions, **Main Flow**, **Alternate Flow**, **Postconditions**, **Exceptions**, then implementation references.
- Every behaviour described is traceable to the code path listed in the *Implementation References* table.
- Documents reconciled against the implementation (E011–E020) open with a **"Corrections vs. the original narrative"** note listing what the narrative said, what the system actually does, and why the narrative was changed rather than the code.
- Items marked **Known Gaps / Notes** describe deliberate limitations or behaviours that do **not** yet match the original narrative — they are candidates for the next iteration, not bugs.
- **Messages (all pages except the map banners):** success, error and warning messages are shown as toasts (`<x-toast />`). In add / edit / delete modals a validation error keeps the modal open and shows the message under its own input; on success the modal closes, the page refreshes and a toast confirms. Lists longer than 10 rows are paged 10 per page. Where the documents below say a "flash" or "banner", read "toast".
- Error/warning UI on the **map** uses a single status banner (`#map-alert`) with the priority stack:
  `offline` → `map-service` → `routing-service` → `location-denied` → `no-puj`.