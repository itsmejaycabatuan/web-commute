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

- [ ] Landing page `/` loads; login and register links work.
- [ ] `/map/guest` shows the map and fare calculator while logged out.
- [ ] Visiting `/dashboard`, `/map` or `/payment/history` while logged out redirects to login.
- [ ] **Register commuter:** valid data creates the account, lands on "verify your email", and creates a wallet.
- [ ] Register rejects: duplicate email, password under 8 characters, mismatched confirmation, terms unchecked.
- [ ] **Register driver** (`/register/driver`): valid data plus a JPG/PNG under 2 MB shows "wait for admin approval". Rejects a missing image, a PDF or GIF, and a file over 2 MB.
- [ ] **Login** with a wrong password and with an unknown email shows the same generic message ("credentials do not match").
- [ ] `pedro.lim@` and `carlos.bautista@` are refused with "This account has been suspended".
- [ ] `lara.gomez@` is sent to the verify-email page, not the map.
- [ ] `elena.torres@` shows the "pending approval" message and is logged out.
- [ ] `nico.tan@` shows the "rejected" message and is logged out.
- [ ] Each role lands on `/map` after login. The sidebar menu differs per role.
- [ ] Forgot password: submitting a known email shows a status message; an invalid email shows a validation error.
- [ ] Logout returns to login, and the Back button doesn't reveal protected pages.

## 2. Commuter

Log in as `commuter@gmail.com` unless noted.

**Map and fare**
- [ ] Map loads, and a pickup and destination give a distance and a fare.
- [ ] The discounted fare is lower than the regular fare.
- [ ] "Buy a Ride" without both a pickup and a destination shows "Pick-up point and destination is required."
- [ ] Recent receipts (last 3) show on the map side panel.
- [ ] Active drivers appear on the map. The seeded pings last only 5 minutes, so re-run the seeder or broadcast from a driver (§3).

**Pay a fare**
- [ ] Wallet payment succeeds, the balance drops by exactly the fare, and the receipt opens.
- [ ] Refreshing the receipt does **not** charge again.
- [ ] Double-clicking Pay or going Back and re-submitting shows "This fare has already been paid".
- [ ] As `juan.delacruz@` (₱0), a Wallet payment shows "You don't have enough balance" and nothing is charged.
- [ ] GCash or Maya redirects to the PayMongo sandbox checkout. Complete it and you get a receipt. Cancel it and you get "Payment cancelled. Nothing was charged."
- [ ] As `juan.delacruz@`, history shows paid, **pending**, **failed** and **cancelled** rows with distinct labels. Opening the pending one shows the "processing" screen and does not claim success.

**History and receipts**
- [ ] `/payment/history` lists payments, newest first.
- [ ] Search by transaction ID (`SEED-…`) works. The from/to date filters work, as does the method filter.
- [ ] As `ana.reyes@`, the 14 payments paginate and the filters survive page changes.
- [ ] A receipt shows the correct route, distance, price and method.
- [ ] You cannot open another user's receipt by changing the id in the URL.

**Wallet top-up**
- [ ] Top up ₱100 by GCash: success, and the balance goes up by ₱100.
- [ ] Rejects ₱5 (min 10), ₱100001 (max 100000), non-numeric input, and a missing method.
- [ ] Repeating the same amount and method within 2 minutes shows "already processed".
- [ ] `/payment/topup/history` lists top-ups. As `ana.reyes@`, the 12 rows paginate. Filter by method and date.

**Profile and settings**
- [ ] `/profile` shows balance, payments and top-ups.
- [ ] Change password: wrong current password is rejected; a new password under 8 characters or with a mismatched confirmation is rejected; a valid change succeeds, and you can log in with the new password. Reset it to `admin123` afterwards.
- [ ] Settings: change email, change theme (light/dark), change font size. The choice persists after a reload and across logins (`ana.reyes@` starts in dark, size 14).
- [ ] "Log out other devices" shows a success message.
- [ ] Delete account: logs you out, the account is gone, and login fails. Test this on a throwaway account you registered.

## 3. Driver

**Shift**
- [ ] As `driver@gmail.com`: dashboard shows the vehicle, this week's hours, and recent violations.
- [ ] Clock in records the time and sets status to active; the status badge updates.
- [ ] Clocking in a second time shows "You have already clocked in today."
- [ ] Clock out computes hours, and overtime appears only beyond 8 hours. Status returns to inactive.
- [ ] Clocking out with no open shift shows "No active shift found to clock out."
- [ ] As `ramon.delacruz@`: an open shift is already in progress. Clock out works.
- [ ] `/timekeeping`: week navigation (prev/next) works. Seeded rows include an overtime day, a sick day and a vacation day, and the week totals add up.
- [ ] Manual status toggle (active/inactive) works and rejects any other value.

**Broadcasting**
- [ ] On the map, as an assigned driver, allow location access and confirm your vehicle appears for a commuter in a second browser.
- [ ] A driver with no vehicle, or a pending or suspended driver, cannot broadcast (403 message).

**Violations**
- [ ] `/violations` lists violations with code, fine, date, place and penalty, and a correct total.

**Access control**
- [ ] A driver cannot open `/commuters`, `/vehicles` or `/reports` (403).
- [ ] Suspend `ramon.delacruz@` (see §4) while he is logged in: his next action is refused.

## 4. Admin

Log in as `admin@gmail.com`.

**Dashboard**
- [ ] Revenue, funds added, active users, recent fares and recent top-ups show; the 7-day chart renders.
- [ ] Total Revenue counts **paid** payments only. The seeded data has ₱634 paid and ₱83 pending/failed/cancelled, so the card should read ₱634.
- [ ] "Active users" counts commuters with at least one paid fare, and the 7-day chart plots paid fares by the day they were paid.

**Commuters** (`/commuters`)
- [ ] The list shows all 6 commuters with correct status (pedro = suspended, lara = unverified).
- [ ] Create a commuter: valid works; duplicate email, password under 8 characters and unconfirmed password are rejected.
- [ ] Edit email, and edit with the password left blank (password unchanged).
- [ ] The commuter detail page shows wallet, payments and top-ups.
- [ ] Suspend then reactivate `maria.santos@`; while suspended she cannot log in.
- [ ] Suspending or deleting **your own** account is refused.
- [ ] Deleting `pedro.lim@` (₱85) is refused: "Cannot delete commuter with a balance…".
- [ ] Deleting `lara.gomez@` (₱0) succeeds. Re-run the seeder to restore her.

**Drivers** (`/drivers`)
- [ ] Pending drivers are listed first. The licence image opens for each (needs `storage:link`, already done).
- [ ] Approve `elena.torres@`: the licence number, licence code, expiry and driver code are required. A licence number already used by another driver (e.g. `N01-23-000001`) is rejected. A valid approval then lets her log in.
- [ ] Reject `mark.ocampo@`: she can't log in and sees the rejected message. Rejecting twice shows "already rejected"; approving twice shows "already approved".
- [ ] Suspend and reactivate `liza.mercado@`: a suspended driver can't log in or clock in.
- [ ] Create a driver manually, and edit one (including replacing the licence image).
- [ ] Delete a driver who has a vehicle (`ramon.delacruz@`) and get "still assigned to a vehicle". Delete one without a vehicle (`dante.cruz@`) and it works.
- [ ] Expired and expiring licences (dante, liza): see whether the UI flags them. Note the behaviour either way.

**Fares** (`/fares`)
- [ ] The current rate table shows 50 tiers.
- [ ] Bulk edit: save valid changes, then check the commuter's fare changes. Rates of `0` or below are rejected.
- [ ] Upload a non-PDF and get "must be a PDF". Upload without a file and get "A fare document file is required."

**Reports** (`/reports`)
- [ ] Financial, Driver and Maintenance reports each render for a range covering the last 30 days.
- [ ] A range with no data shows the "no data" state, not an error.
- [ ] End date before start date shows a validation error.
- [ ] Export downloads a CSV with the right filename and the same rows as the screen.

**Top-ups and transactions**
- [ ] `/topups` lists all top-ups. `/transactions` lists every payment attempt (all statuses), and receipts open from there.
- [ ] On `/transactions`, Total Revenue is ₱634 (paid only), and the average fare is that revenue divided by the 28 paid fares, not by all 31 attempts.

**Map tools (local env)**
- [ ] The seeded Dev Jeep markers show for admin. Add, toggle and remove a marker. Commuters see only the active ones, with obfuscated positions.

## 5. Driver manager

Log in as `drivermanager@gmail.com`.

- [ ] Dashboard shows the driver list, time-keeping and violation counts.
- [ ] **Time keeping:** save a normal shift (in 06:00, out 14:00) and check hours and overtime.
- [ ] Rejects: out earlier than in; only one of in/out; a shift that overlaps an existing one that day.
- [ ] Marking sick or vacation saves a zero-hours row without times.
- [ ] **Violation codes:** 6 codes are listed. Add a code. A duplicate code is rejected. A negative fine and a blank tier are rejected. Edit a code, then delete one.
- [ ] **Violations log:** log a single violation and a bulk set (2+ rows). A future date and an offence number outside 1–4 are rejected.
- [ ] A driver manager can't open admin pages (`/commuters`, `/reports`) or maintenance pages (403).

## 6. Maintenance manager

Log in as `maintenancemanager@gmail.com`.

- [ ] Dashboard: choose each vehicle; cost summary, monthly totals, cost-per-km and the log table update. A vehicle with no history (STU-1122) shows zeros, not an error.
- [ ] **Vehicles:** add a vehicle. Rejected: duplicate plate, year outside 1990–2030, disposal date before acquisition date.
- [ ] Assigning a driver to a maintenance, inactive or disposed vehicle is refused.
- [ ] Set ABC-1234 to *disposed* while Ramon is clocked in: refused until he clocks out.
- [ ] Deleting a vehicle that has maintenance records is refused ("mark it Disposed instead"). Deleting an empty one (STU-1122, or one you just added) works.
- [ ] **Preventive maintenance:** the schedule shows a mix of OK and Overdue items. Log a service with an odometer **lower** than the last reading and get an error naming that reading.
- [ ] A valid entry updates the schedule and adds a service-log row.
- [ ] **Maintenance tasks:** add, edit and delete a task. A blank name is rejected, and miles/months may be blank.
- [ ] **Vehicle maintenance log:** add, edit and delete a log; cost, mileage and performed-by are required. The totals and cost-per-mile recalculate.
- [ ] `/maintenance-logs` lists all logs. With 12 seeded logs, the list paginates (10, then 2) and the page links work.

## 7. Cross-cutting

- [ ] Every role is refused (403) on the other roles' URLs.
- [ ] Dark theme and the font-size setting apply on every role's pages.
- [ ] Layout works at phone width (about 375 px): sidebar, tables, map and payment screens.
- [ ] Success and error banners appear and clear correctly. Nothing shows a raw stack trace.

---

## Issues found while seeding (all resolved)

Found by running the seeded data through each page.

1. **Violations pages crashed whenever a violation had penalty text.** Fixed in `DriverController` and `DriverManagerController` (`Str::contains` instead of `str_contains`). Re-check that penalties show with the right colour: suspension, revocation and impound in red; warning and community service in amber.
2. **`/maintenance-logs` crashed when the logs spanned more than one page.** Fixed: pagination now uses `vendor.pagination.custom`, like the other paginated pages.
3. **Admin revenue counted unpaid payments.** Fixed: revenue figures count paid payments only.
4. **The unused Routes feature was removed.** The `routes` table is still in the database.

Still worth a look: users have no `name` column, so commuters are identified by email. Check that the violations and driver-manager screens don't show "Unknown" for a name.
