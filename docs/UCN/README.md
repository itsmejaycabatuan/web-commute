577z# Use Case Narratives (UCN) — Web Commute

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
| **Payment record** | An immutable ledger row for one paid fare (transaction id `#SC-XXXXXXXX`), readable only by the commuter who paid it. Carries a lifecycle status: `pending` → `paid` / `failed` / `cancelled`. |
| **Gateway** | Optional PayMongo integration for GCash/Maya fares. Disabled by default; enabled with sandbox test keys — see `docs/PAYMONGO_SETUP.md`. |
| **Timekeeping record** | One row per driver per day, holding `time_in`, `time_out`, `hours_worked` and `overtime_hours`. |
| **Suspension** | A flag that locks an account out of signing in without deleting it. Commuters carry `users.is_suspended` (UCN_SC_E018 A4); drivers carry `drivers.is_suspended` (UCN_SC_E012 A5), which additionally blocks clock-in and location broadcast and releases any assigned vehicle. |
| **Vehicle status** | `active` (roadworthy, may carry a driver), `inactive`, `maintenance` (out of service, off the live map) or `disposed` (retired, kept for auditing). Only `active` / `inactive` may hold a driver — UCN_SC_E015. |
| **Preventive maintenance** | The *schedule* record: the last service performed for a (vehicle, task) pair and the interval used to project the next due date/odometer. UCN_SC_E016. |
| **Service log** | The *history* record: one row per service actually performed, holding vehicle, task, service date, odometer, cost, invoice and remarks. Written both by the vehicle-log page and by logging a scheduled service. UCN_SC_E016. |
| **PUJ** | Public Utility Vehicle. On the map, a PUJ marker is a **roadworthy** vehicle (status `active`/`inactive`) with a location reported in the last 5 minutes. |
| **Privacy radius** | 200 m. Guest/commuter coordinates are randomly obfuscated within this radius. |

## Document conventions

- Each document follows the classic UCN layout: header, preconditions, **Main Flow**, **Alternate Flow**, **Postconditions**, **Exceptions**, then implementation references.
- Every behaviour described is traceable to the code path listed in the *Implementation References* table.
- Documents reconciled against the implementation (E011–E020) open with a **"Corrections vs. the original narrative"** note listing what the narrative said, what the system actually does, and why the narrative was changed rather than the code.
- Items marked **Known Gaps / Notes** describe deliberate limitations or behaviours that do **not** yet match the original narrative — they are candidates for the next iteration, not bugs.
- Error/warning UI uses a single status banner (`#map-alert`) with the priority stack:
  `offline` → `map-service` → `routing-service` → `location-denied` → `no-puj`.