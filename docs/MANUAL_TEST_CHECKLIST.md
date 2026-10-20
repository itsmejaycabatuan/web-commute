# SmartCommute: Manual Test Checklist

Test data comes from `database/seeders/DevSeeder.php`.

```bash
/opt/lampp/bin/php artisan db:seed --class=DevSeeder   # load / reset the baseline
/opt/lampp/bin/php artisan serve                        # then browse http://127.0.0.1:8000
```

Use `127.0.0.1:8000`, not XAMPP Apache. Apache writes compiled views that `artisan serve` can't overwrite.

Re-running the seeder is safe: it never duplicates rows. It resets the seeded accounts (password, suspension, approval, today's shift) and leaves your other test changes alone.

**Login is throttled to 5 attempts per minute.** If you see "Too many attempts" (429), wait a minute.

## Test accounts (password for all: `admin123`)

| Role | Email | State to test with |
|---|---|---|
| Admin | `admin@gmail.com` | also `markjay.dev@proton.me`, `admin@smartcommute.local` |
| Driver manager | `drivermanager@gmail.com` | |
| Maintenance manager | `maintenancemanager@gmail.com` | |
| Commuter | `commuter@gmail.com` | wallet ₱623, 5 rides |
| Commuter | `maria.santos@example.com` | wallet ₱1,430, 6 rides |
| Commuter | `juan.delacruz@example.com` | **wallet ₱0**; has a pending, a failed and a cancelled GCash/Maya payment |
| Commuter | `ana.reyes@example.com` | wallet ₱4,896; **14 payments, 12 top-ups** (pagination) |
| Commuter | `pedro.lim@example.com` | **suspended**, wallet ₱85 |
| Commuter | `lara.gomez@example.com` | **email not verified**, wallet ₱0 |
| Driver | `driver@gmail.com` | approved, vehicle JKL-7890, **no shift today** (test clock-in), 4 violations |
| Driver | `ramon.delacruz@example.com` | approved, vehicle ABC-1234, **mid-shift today** (test clock-out), 2 violations |
| Driver | `liza.mercado@example.com` | approved, vehicle DEF-5678, licence expires in 20 days |
| Driver | `dante.cruz@example.com` | approved, **licence expired** 45 days ago, 1 violation |
| Driver | `carlos.bautista@example.com` | **suspended** (account + driver record), 2 violations |
| Driver | `elena.torres@example.com`, `mark.ocampo@example.com` | **pending approval** |
| Driver | `nico.tan@example.com` | **rejected** |

Fleet: ABC-1234 Coaster (active, Ramon) · DEF-5678 County (active, Liza) · JKL-7890 HiAce (active, driver@) · STU-1122 Toano (active, **unassigned**) · GHI-9012 NPR (maintenance) · MNO-3456 L300 (inactive) · PQR-7890 Urvan (disposed).

Fare payments by GCash/Maya go to PayMongo's hosted checkout (`PAYMONGO_ENABLED=true`, sandbox keys). Wallet payments and top-ups work fully offline.

---

## 1. Public pages and authentication

- [x] Landing page `/` loads; login and register links work.
- [x] `/map/guest` shows the map and fare calculator while logged out.
- [x] Visiting `/dashboard`, `/map` or `/payment/history` while logged out redirects to login.
- [x] **Register commuter:** valid data creates the account, lands on "verify your email", and creates a wallet.
- [x] Register rejects: duplicate email, password under 8 characters, mismatched confirmation, terms unchecked.
- [x] **Register driver** (`/register/driver`): valid data plus a JPG/PNG under 2 MB shows "wait for admin approval". Rejects a missing image, a PDF or GIF, and a file over 2 MB.
- [x] **Login** with a wrong password and with an unknown email shows the same generic message ("credentials do not match").
- [x] `pedro.lim@` and `carlos.bautista@` are refused with "This account has been suspended".
- [x] `lara.gomez@` is sent to the verify-email page, not the map.
- [x] `elena.torres@` shows the "pending approval" message and is logged out.
- [x] `nico.tan@` shows the "rejected" message and is logged out.
- [x] Each role lands on `/map` after login. The sidebar menu differs per role.
- [x] Forgot password: submitting a known email shows a status message; an invalid email shows a validation error.
- [ ] Logout returns to login, and the Back button doesn't reveal protected pages.

## 2. Commuter

Log in as `commuter@gmail.com` unless noted.

**Map and fare**
- [x] Map loads, and a pickup and destination give a distance and a fare.
- [x] The discounted fare is lower than the regular fare.
- [x] "Buy a Ride" without both a pickup and a destination shows "Pick-up point and destination is required."
- [x] Recent receipts (last 3) show on the map side panel.
- [x] Active drivers appear on the map. The seeded pings last only 5 minutes, so re-run the seeder or broadcast from a driver (§3).

**Pay a fare**
- [ ] Wallet payment succeeds, the balance drops by exactly the fare, and the receipt opens.
- [x] Refreshing the receipt does **not** charge again.
- [ ] Double-clicking Pay sends one request. Re-submitting a payment that already went through opens its receipt with "This fare has already been paid"; re-submitting a pending GCash/Maya payment opens the "Confirming your payment" screen with a warning; going Back from the PayMongo checkout shows a "payment already in progress" notice with a *Check payment status* link.
- [x] As `juan.delacruz@` (₱0), a Wallet payment shows "You don't have enough balance" and nothing is charged.
- [ ] GCash or Maya redirects to the PayMongo sandbox checkout. Complete it and you get a receipt. Cancel it and you get "Payment cancelled. Nothing was charged."
- [x] As `juan.delacruz@`, history shows paid, **pending**, **failed** and **cancelled** rows with distinct labels. Opening the pending one shows the "processing" screen and does not claim success.

**History and receipts**
- [x] `/payment/history` lists payments, newest first.
- [x] Search by transaction ID (`SEED-…`) works. The from/to date filters work, as does the method filter.
- [x] As `ana.reyes@`, the 14 payments paginate and the filters survive page changes.
- [x] A receipt shows the correct route, distance, price and method.
- [x] You cannot open another user's receipt by changing the id in the URL.

**Wallet top-up**
- [x] Top up ₱100 by GCash: success, and the balance goes up by ₱100.
- [x] Rejects ₱5 (min 10), ₱100001 (max 100000), non-numeric input, and a missing method.
- [x] Repeating the same amount and method within 2 minutes shows "already processed".
- [x] `/payment/topup/history` lists top-ups. As `ana.reyes@`, the 12 rows paginate. Filter by method and date.

**Profile and settings**
- [x] `/profile` shows balance, payments and top-ups.
- [x] Change password: wrong current password is rejected; a new password under 8 characters or with a mismatched confirmation is rejected; a valid change succeeds, and you can log in with the new password. Reset it to `admin123` afterwards.
- [x] Settings: change email, change theme (light/dark), change font size. The choice persists after a reload and across logins (`ana.reyes@` starts in dark, size 14).
- [x] Settings → Security → **Sign out of other devices**: needs the current password (wrong one is rejected under the field); on success every other session of the account is ended and this one stays.
- [x] Changing the password to the **same** password is rejected ("must be different from your current password").
- [x] Delete account: logs you out, the account is gone, and login fails. Test this on a throwaway account you registered.

## 3. Driver

**Shift**
- [x] As `driver@gmail.com`: dashboard shows the vehicle, this week's hours, and recent violations.
- [x] Clock in records the time and sets status to active; the status badge updates.
- [x] Clocking in a second time shows "You have already clocked in today."
- [x] Clock out computes hours, and overtime appears only beyond 8 hours. Status returns to inactive.
- [x] Clocking out with no open shift shows "No active shift found to clock out."
- [x] As `ramon.delacruz@`: an open shift is already in progress. Clock out works.
- [x] `/timekeeping`: week navigation (prev/next) works. Seeded rows include an overtime day, a sick day and a vacation day, and the week totals add up.
- [x] Manual status toggle (active/inactive) works and rejects any other value.

**Broadcasting**
- [x] On the map, as an assigned driver, allow location access and confirm your vehicle appears for a commuter in a second browser.
- [x] A driver with no vehicle, with an inactive / maintenance vehicle, or a pending or suspended driver, cannot broadcast (403) and the map shows *GPS: Not broadcasting*.

**Violations**
- [x] `/violations` lists violations with code, fine, date, place and penalty, and a correct total.

**Access control**
- [x] A driver cannot open `/commuters`, `/vehicles` or `/reports` (403).
- [x] Suspend `ramon.delacruz@` (see §4) while he is logged in: his next click signs him out and the login page says the account is suspended.
- [x] Settings shows the driver's email read-only (drivers cannot change it).
- [x] A licence that expires within 30 days (or has expired) shows a warning on the driver dashboard.

## 4. Admin

Log in as `admin@gmail.com`.

**Dashboard**
- [x] Revenue, funds added, active users, recent fares and recent top-ups show; the 7-day chart renders.
- [x] Total Revenue counts **paid** payments only. The seeded data has ₱634 paid and ₱83 pending/failed/cancelled, so the card should read ₱634.
- [x] "Active users" counts commuters with at least one paid fare, and the 7-day chart plots paid fares by the day they were paid.

**Commuters** (`/commuters`)
- [x] The list shows all 6 commuters with correct status (pedro = suspended, lara = unverified).
- [x] Create a commuter: valid works; duplicate email, password under 8 characters and unconfirmed password are rejected.
- [x] Edit email, and edit with the password left blank (password unchanged).
- [x] The commuter detail page shows wallet, payments and top-ups.
- [x] Suspend then reactivate `maria.santos@`; while suspended she cannot log in.
- [ ] Suspending or deleting **your own** account is refused.
- [x] Deleting `pedro.lim@` (₱85) is refused: "Cannot delete commuter with a balance…".
- [x] Deleting `lara.gomez@` (₱0) succeeds. Re-run the seeder to restore her.

**Drivers** (`/drivers`)
- [x] Pending drivers are listed first. The licence image opens for each (needs `storage:link`, already done).
- [x] Approve `elena.torres@`: the licence number, licence code, expiry and driver code are required. A licence number already used by another driver (e.g. `N01-23-000001`) is rejected. A valid approval then lets her log in.
- [x] Reject `mark.ocampo@`: she can't log in and sees the rejected message. Rejecting twice shows "already rejected"; approving twice shows "already approved".
- [x] Suspend and reactivate `liza.mercado@`: a suspended driver can't log in or clock in.
- [x] Create a driver manually, and edit one (including replacing the licence image). There is **no driver-code field**: the code is generated (`DRV-######`).
- [x] A **pending** driver (`mark.ocampo@`) offers only *Review* and delete: no edit, no suspend. Forcing the request is refused.
- [x] A driver who has a vehicle (`ramon.delacruz@`) shows a lock instead of the delete icon (and the server refuses "still assigned to a vehicle"). Delete one without a vehicle (`dante.cruz@`) and it works.
- [x] Expired and expiring licences (dante, liza) carry a red *Expired* / amber *Expires in N days* badge in the **License** column, and the *License Expiring* / *License Expired* chips filter the list.

**Fares** (`/fares`)
- [x] The current rate table shows 50 tiers.
- [x] Bulk edit: save valid changes, then check the commuter's fare changes. Rates of `0` or below are rejected.
- [x] Upload a non-PDF and get "must be a PDF". Upload without a file and get "A fare document file is required."

**Reports** (`/reports`)
- [x] Financial, Driver and Maintenance reports each render for a range covering the last 30 days.
- [x] A range with no data shows the "no data" state, not an error.
- [x] End date before start date shows a validation error.
- [x] Export downloads a CSV with the right filename and the same rows as the screen.

**Top-ups and transactions**
- [x] `/topups` lists all top-ups of every status with a Status column; the filter bar narrows by search, payment status, method, date range and amount range; 10 per page.
- [x] `/transactions` lists every payment attempt (all statuses) with the same filters, and receipts open from there.
- [x] On `/transactions`, Total Revenue is ₱634 (paid only), and the average fare is that revenue divided by the 28 paid fares, not by all 31 attempts.

**Map tools (local env)**
- [x] The seeded Dev Jeep markers show for admin. Add, toggle and remove a marker. Commuters see only the active ones, with obfuscated positions.

## 5. Driver manager

Log in as `drivermanager@gmail.com`.

- [x] Dashboard shows the driver list, time-keeping and violation counts.
- [x] **Time keeping:** save a normal shift (in 06:00, out 14:00) and check hours and overtime.
- [x] Rejects: out earlier than in; only one of in/out; a shift that overlaps an existing one that day.
- [x] Marking sick or vacation saves a zero-hours row without times.
- [x] **Violation codes:** 6 codes are listed. Add a code. A duplicate code is rejected. A negative fine and a blank tier are rejected. Edit a code, then delete one.
- [x] **Violations log:** log a single violation and a bulk set (2+ rows, remarks left empty on some). The modal closes as soon as it is saved; a future date or a missing field shows its message under that field.
- [x] A driver manager can't open admin pages (`/commuters`, `/reports`) or maintenance pages (403).

**Staff** (`/staff`)
- [x] The list shows the driver manager and the maintenance manager, 10 per page; search, role and status filters work.
- [x] Create a staff account (valid works; duplicate e-mail, short password, mismatched confirmation, wrong role are rejected under their fields), edit its e-mail/role, suspend and reactivate it, delete it.
- [x] A suspended staff member who is signed in is signed out on the next click.
- [x] You cannot suspend or delete your own account.

**Vehicles (admin)**
- [x] The admin menu has *Vehicles*; the admin can add, edit and delete vehicles with the same rules as the maintenance manager.

## 6. Maintenance manager

Log in as `maintenancemanager@gmail.com`.

- [x] Dashboard: choose each vehicle; cost summary, monthly totals, cost-per-km and the *Service History* tab update. A vehicle with no history (STU-1122) shows zeros, not an error.
- [x] **Vehicles:** add a vehicle. Rejected: duplicate plate, year outside 1990–2030, disposal date before acquisition date.
- [x] Assigning a driver to an inactive, maintenance or disposed vehicle is refused (the driver select is disabled unless the status is *Active*).
- [x] Set ABC-1234 to *disposed* while Ramon is clocked in: refused until he clocks out.
- [x] Deleting a vehicle that is assigned to a driver is refused (the delete icon is locked). Deleting one with maintenance records is refused ("mark it Disposed instead"). Deleting an empty, unassigned one (STU-1122, or one you just added) works.
- [x] **Preventive maintenance:** the schedule shows a mix of OK and Overdue items. Log a service with an odometer **lower** than the last reading and get an error naming that reading.
- [x] A valid entry updates the schedule, the dashboard's cost summary and its *Service History* tab.
- [x] **Maintenance tasks:** add, edit and delete a task. A blank name is rejected, and miles/months may be blank.
- [x] There is no *Maintenance Logs* menu item and `/vehicle-maintenance-log` and `/maintenance-logs` answer 404: the vehicle maintenance log was retired in favour of preventive maintenance.

## 7. Cross-cutting

- [ ] Every role is refused (403) on the other roles' URLs.
- [ ] Dark theme and the font-size setting apply on every role's pages.
- [ ] Layout works at phone width (about 375 px): sidebar, tables, map and payment screens.
- [ ] Success and error **toasts** (bottom-right) appear and clear correctly on every page. Nothing shows a raw stack trace.
- [ ] In every add/edit/delete modal a validation error keeps the modal open and shows the message **under its own input**; success closes the modal and shows a toast. Pressing a submit button twice sends one request.

---

## Issues found while seeding (all resolved)

Found by running the seeded data through each page.

1. **Violations pages crashed whenever a violation had penalty text.** Fixed in `DriverController` and `DriverManagerController` (`Str::contains` instead of `str_contains`). Re-check that penalties show with the right colour: suspension and impound in red; warning and community service in amber.
2. **`/maintenance-logs` crashed when the logs spanned more than one page.** Fixed, and the page has since been removed together with the vehicle maintenance log.
3. **Admin revenue counted unpaid payments.** Fixed: revenue figures count paid payments only.
4. **The unused Routes feature was removed.** The `routes` table is still in the database.

Still worth a look: users have no `name` column, so commuters are identified by email. Check that the violations and driver-manager screens don't show "Unknown" for a name.
