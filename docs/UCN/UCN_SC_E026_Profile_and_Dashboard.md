# UCN_SC_E026 — Profile and Dashboard Views

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E026                                                                                                                                      |
| **Use Case Name**     | Profile and Dashboard Views                                                                                                                      |
| **Primary Actor**     | Authenticated user (Commuter for profile; Admin, Driver, Driver Manager, Maintenance Manager for dashboard)                                      |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow a user to view their own profile information (commuter) and to see role‑specific summary statistics and recent activity on the dashboard. |
| **Trigger**           | User navigates to `/profile` to view their profile, or to `/dashboard` to view the role‑specific dashboard.                                    |
| **Preconditions**     | 1. User is authenticated and logged in.<br>2. For `/profile`: user must have the `commuter` role (the view is only defined for commuters).<br>3. For `/dashboard`: user must have one of the roles `admin`, `driver`, `driver_manager`, or `maintenance_manager`.<br>4. System is operational. |
| **Supporting Actors** | `User`, `Payment`, `TopupHistory`, `Wallet`, `TimeKeeping`, `Driver`, `Vehicle`, `ViolationLog`, `Fare`, `FareRate`, `FleetMaintenanceService` |

---

## MAIN FLOW

### Profile View (Commuter)

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User clicks **Profile** in the sidebar (or navigates to `/profile`).              | System authenticates the user, checks that the role is `commuter`, fetches the user’s `Payment` records, `TopupHistory` records, and `Wallet` record, and returns the `commuter.profile` view. |
| 2   |                                                                                   | The view renders three sections: **Payments** (list of fare payments with amount, date, etc.), **Top‑ups** (list of top‑up transactions with amount, method, date), and **Wallet** (current balance). |
| 3   | User can scroll through the lists; each item shows relevant details (e.g., payment amount, top‑up method, wallet balance).                     | No further server interaction is required unless the user triggers another action (e.g., making a new top‑up via UCN_SC_E005).                 |

### Dashboard View (Staff Roles)

The dashboard aggregates role‑specific widgets. The flow differs by role; each block below describes what the system returns for that role.

#### Admin Dashboard

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Authenticated admin navigates to `/dashboard`.                                   | System verifies the `admin` role, computes aggregates: total revenue (`Payment::sum('price')`), total funds added (`TopupHistory::sum('amount_added')`), active users count (distinct `paid_by` in payments), recent fares (latest 5 payments with user), recent top‑ups (latest 5 top‑ups with user), revenue by day (last 7 days), top‑ups by day (last 7 days). |
| 2   |                                                                                   | System returns the `admin.dashboard` view with the computed data.                                                                                                                                       |
| 3   |                                                                                   | The view displays panels: **Total Revenue**, **Total Funds Added**, **Active Users**, **Recent Fares** (table), **Recent Top‑ups** (table), **Revenue by Day** (chart or list), **Top‑ups by Day** (chart or list). |

#### Driver Dashboard

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Authenticated driver navigates to `/dashboard`.                                   | System verifies the `driver` role, loads the driver’s `Driver` record, today’s `TimeKeeping` record, recent time‑keeping (last 7 days), weekly hours/overtime, assigned `Vehicle`, recent violation logs (latest 5), and totals for violations. |
| 2   |                                                                                   | System returns the `driver.dashboard` view with the data.                                                                                                                                               |
| 3   |                                                                                   | The view shows: **Driver Info** (name, license, etc.), **Today’s Record** (time‑in/out, hours), **Weekly Summary** (hours, overtime), **Assigned Vehicle** (plate, brand/model), **Recent Time‑keeping** (table of last 7 days), **Recent Violations** (table with type, date, fine), **Total Violations** and **Total Fines**. |

#### Driver‑Manager Dashboard

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Authenticated driver‑manager navigates to `/dashboard`.                           | System verifies the `driver_manager` role, fetches all `Driver` records (with user), all `TimeKeeping` records (with driver), and all `ViolationLog` records (with user). |
| 2   |                                                                                   | System returns the `driver-manager.dashboard` view with the data (converted to arrays for display).                                                                                                   |
| 3   |                                                                                   | The view lists drivers (ID, name, code, license, expiration), time‑keeping logs (driver, date, in/out, hours, overtime, sick/vacation flags), and violation logs (user, instance, fine, date, time). |

#### Maintenance‑Manager Dashboard

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Authenticated maintenance‑manager navigates to `/dashboard`.                      | System verifies the `maintenance_manager` role, loads all `Vehicle` records (with driver), all `Driver` records, and the fleet maintenance summary for a selected vehicle (via query param `vehicle_id` or first vehicle). |
| 2   |                                                                                   | System returns the `maintenance-manager.dashboard` view with the data (vehicles, drivers, selected vehicle, and summary from `FleetMaintenanceService`). |
| 3   |                                                                                   | The view shows: **Fleet Information** (plate, year, brand/model, driver), **Cost Summary** (total cost, total services, avg cost/service, cost/km), **Maintenance Guide** (external link), and a button to log a new service (which opens the vehicle maintenance log form – see UCN_SC_E025). |

---

## Alternate Flow

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

## Postconditions

- **Profile view**: the user sees their payment history, top‑up history, and current wallet balance accurately reflected.
- **Admin dashboard**: the summarized financial and user‑activity metrics are correct as of the moment of the request.
- **Driver dashboard**: the driver’s personal statistics (time‑keeping, violations, vehicle assignment) are accurate.
- **Driver‑manager dashboard**: the lists of drivers, time‑keeping entries, and violation logs are up‑to‑date.
- **Maintenance‑manager dashboard**: the fleet overview and selected‑vehicle maintenance summary are correct.
- No data is persisted; all flows are read‑only.

---

## Exceptions

**E1 – Profile not commuter**
The authenticated user does not have the `commuter` role. System returns a 500 error (missing view) or, if caught, a generic *“Profile page unavailable.”* message.

**E2 – Dashboard role mismatch**
The authenticated user’s role is not one of the permitted dashboard roles. System returns a 500 error (missing return) or a generic *“Dashboard unavailable.”* message.

**E3 – Database query failure**
Any query used to build the profile or dashboard throws a PDO exception. System logs the error and shows an error page or flash message indicating the dashboard could not be loaded.

**E4 – Unexpected server error**
Any other unhandled exception results in a 500 error page.

---

## Known Gaps / Notes

- The `/profile` endpoint currently only serves commuter users; staff profile information is viewable via the `/settings` route (see UCN_SC_E008 for password change, but other profile fields like email are edited there). A dedicated staff profile page does not exist in the current codebase.
- The `/dashboard` endpoint is strictly for staff roles; commuters are expected to use the map (`/map`) as their main interface (documented in UCN_SC_E003). This separation is intentional in the product design.
- Dashboard widgets are refreshed only on page load; there is no auto‑reload or polling for live updates.
- The maintenance‑manager dashboard reuses the same summary logic as the full `/fleet-maintenance-log` page (UCN_SC_E016) to ensure consistency.
- All monetary values are stored as plain numbers in the database; views format them with the “₱” prefix and two decimal places where appropriate.
- The dashboard does not expose sensitive raw data (e.g., full payment details) beyond what is necessary for the summary widgets.
- Error handling for missing relationships (e.g., a driver without a vehicle) gracefully shows placeholders like “—” or “Unassigned”.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /profile` (`profile`), `GET /dashboard` (`dashboard`) (both inside `auth` + `verified` middleware; `/profile` additionally lacks role middleware, `/dashboard` is accessed by staff via their role checks inside the controller) |
| Controller | `app/Http/Controllers/UserController.php::profile()`, `dashboard()` |
| Views | `resources/views/commuter/profile.blade.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/driver/dashboard.blade.php`, `resources/views/driver-manager/dashboard.blade.php`, `resources/views/maintenance-manager/dashboard.blade.php` |
| Models | `App\Models\User`, `App\Models\Payment`, `App\Models\TopupHistory`, `App\Models\Wallet`, `App\Models\TimeKeeping`, `App\Models\Driver`, `App\Models\Vehicle`, `App\Models\ViolationLog`, `App\Models\Fare`, `App\Models\FareRate` |
| Services | `App\Services\FleetMaintenanceService` (used in dashboard) |
| Related UCNs | UCN_SC_E003 (Track PUJ – commuter map/dashboard), UCN_SC_E005 (Topup Balance), UCN_SC_E006 (Pay Fare), UCN_SC_E009/E010 (Clock In/Out), UCN_SC_E012 (Manage Drivers), UCN_SC_E016 (Manage Maintenance Schedule) |