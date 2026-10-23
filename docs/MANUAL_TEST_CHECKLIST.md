# SmartCommute: Manual Test Checklist

Test data comes from `database/seeders/DevSeeder.php`.

```bash
/opt/lampp/bin/php artisan db:seed --class=DevSeeder   # load / reset the baseline
/opt/lampp/bin/php artisan serve                        # then browse http://127.0.0.1:8000
```

Use `127.0.0.1:8000`, not XAMPP Apache. Apache writes compiled views that `artisan serve` can't overwrite.

Re-running the seeder is safe: it never duplicates rows. It resets the seeded accounts (password, suspension, approval, today's shift) and leaves your other test changes alone.

**Login is throttled to 5 attempts per minute.** If you see "Too many attempts" (429), wait a minute.

**Behind ngrok / a reverse proxy:** the app trusts `X-Forwarded-*`, so URLs use the tunnel's https address. Forms and fetch calls also post to the page's own origin, so add/edit modals work through the tunnel.

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
- [x] Wallet payment succeeds, the balance drops by exactly the fare, and the receipt opens.
- [x] Refreshing the receipt does **not** charge again.
- [x] Double-clicking Pay sends one request. Re-submitting a payment that already went through opens its receipt with "This fare has already been paid"; re-submitting a pending GCash/Maya payment opens the "Confirming your payment" screen with a warning; going Back from the PayMongo checkout shows a "payment already in progress" notice with a *Check payment status* link.
- [x] As `juan.delacruz@` (₱0), a Wallet payment shows "You don't have enough balance" and nothing is charged.
- [x] GCash or Maya redirects to the PayMongo sandbox checkout. Complete it and you get a receipt. Cancel it and you get "Payment cancelled. Nothing was charged."
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
- [x] Delete account: logs you out, the account is gone, and login fails. Test this on a throwaway account you registered. With payments or top-ups behind it the account is *anonymised* rather than removed, and a wallet balance blocks it — see section 11.

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
- [x] There is no *Maintenance Logs* menu item and `/vehicle-maintenance-log` answers 404. **Maintenance Logs** is a Maintenance Manager menu item (`/maintenance-logs`; any other role gets 403). Log the same task twice on Preventive Maintenance: the schedule row shows the latest service, the Maintenance Logs page lists both.
- [x] A driver with no vehicle (Elena Torres) sees the amber *no vehicle assigned* notice on the map; **Clock In** is disabled and the Availability toggle can only be switched off.
- [x] Violation Log → **Mark Paid** turns the badge green and the row to ₱0.00 (original struck through); the driver's *Outstanding* totals drop. **Mark Unpaid** restores them.
- [ ] Two drivers on one vehicle (Vehicles → edit → second driver select): **Duty Roster** → Generate, swap a day, Save. The rostered driver can clock in; the co-driver sees *"Today is …'s day"* and a disabled Clock In. A one-driver vehicle needs no roster.
- [x] Drivers → ID-card icon: the modal lists the driver's timekeeping and violations.

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
4. **The unused Routes feature was removed.** Its `routes` table (and the equally unused `fleets` table) was dropped by migration `2026_10_21_090000_drop_unused_routes_and_fleets_tables`, applied on the dev database on 2026-10-21.

Still worth a look: users have no `name` column, so commuters are identified by email. Check that the violations and driver-manager screens don't show "Unknown" for a name.

---

## 8. Second test round (Oct 2026)

- [x] Through an ngrok URL: add / edit / delete in any modal works (no "Network error"), settings theme and font size save.
- [x] Driver toggles *Available / Unavailable* on their map: within a few seconds the live marker turns green / red for a commuter and its popup reads *Available* / *Unavailable*.
- [x] Admin Add / Edit **Commuter**, **Driver** and **Staff** modals check input while typing: bad e-mail, password under 8 characters, mismatched confirmation, a non-Philippine phone number and a PDF / oversized licence image each show a message under the field, and nothing is sent.
- [x] The contact field in the driver modals shows a `+63` prefix and formats as `917 123 4567`.
- [x] The **View** driver modal marks an expired licence (red date, *Expired*) and an expiring one (amber, *Expires in N days*).
- [x] Sign in as a driver with an expiring or expired licence (`liza.mercado@`, `dante.cruz@`): a warning toast appears at **every** sign-in. A driver with a valid licence and a commuter see none.
- [x] Lists show 10 per page with a pager: PUJ Drivers, PUJ Commuters, Vehicles, Violation Codes (in the browser) and Violations Log, Maintenance Tasks, a driver's own Violations, a commuter's trips and top-ups on the admin detail page (from the server).
- [x] Admin: Fare Transactions → a row → *View Full Receipt* opens `payment/receipt/{id}` with the commuter e-mail, status and *Back to Transactions*; `/transactions/receipt/…` is 404.
- [x] Toasts look the same (light card) in light and dark mode.

## 9. Third test round

- [x] A pending GCash/Maya payment older than 12 hours turns *cancelled* the next time payment history, the admin ledger or its checkout page is opened (`php artisan payments:expire-pending` does the same).
- [x] Vehicles → Add / Edit: the *Assigned Driver* list shows only approved, non-suspended drivers who hold no other vehicle; editing a vehicle keeps its own driver selectable.
- [x] Select boxes look the same everywhere (same chevron, padding, readable option list in dark mode): staff, vehicles, violations, filters.
- [x] Every add/edit modal (including *Add Staff*) shows a spinner on its button and a "Saving…" overlay when the request takes more than a moment.

## 10. Audit trail and Activity Log (Oct 2026)

Not yet clicked through in a browser — the automated suites (`AuditTrailTest`, `ActivityLogPageTest`) cover the same ground. On a local
server the IP address will read `127.0.0.1`. Do the actions first, then read them back as the admin.

**Access**
- [ ] As `admin@gmail.com` the sidebar shows **Activity Log** (clipboard icon, after *Staff*). As `drivermanager@gmail.com`, `maintenancemanager@gmail.com`, a driver and a commuter it does **not**, and typing `/activity-log` gives 403. Signed out, it redirects to the login page.

**Things that must leave an entry** (open Activity Log afterwards; newest is first)
- [ ] Sign in as `commuter@gmail.com` → *Signed in*, with the email, role, IP and a device such as "Chrome on Windows".
- [ ] Try the wrong password for that account → *Failed sign-in for commuter@gmail.com*, no password anywhere in the entry; the page header now shows "1 failed sign-in in the last 24 h" and the link filters to it.
- [ ] Sign out → *Signed out*. Change a password in Settings → *Password changed*, with no password in the detail.
- [ ] Admin suspends then reactivates a commuter who is not already suspended (not `pedro`) → *Suspended*, *Reactivated*, both naming the admin.
- [ ] Vehicles → change a vehicle's status to *Maintenance* → *Updated vehicle …* and, expanded, a Before / After row for **Status** only.
- [ ] Drivers → approve a pending driver → *Approved driver DRV-…*; suspend a driver with a reason → *Suspended*, with the reason and whether a vehicle was released.
- [ ] Fare Rates → edit two rates and save → one *Fare rates updated* entry, expanded to a per-distance table (₱13.00 → ₱15.00 …). Save again without changing anything → no new entry.
- [ ] Staff → add, change the role of, then delete a staff account → *Created*, *Role changed* (old → new), *Deleted*.
- [ ] Reports → Export CSV → a *Report exported* entry (report type and dates).

**Things that must NOT leave an entry**
- [ ] Open the live map as a guest and as a commuter for a minute, then check Activity Log: no new rows.
- [ ] A driver on the map broadcasting, toggling Available / Unavailable, clocking in and out: no entries except the *Created / Updated time entry* rows for the clock-in and clock-out.
- [ ] Opening Activity Log, filtering and paging adds nothing. Settings theme / font size changes add nothing.

**The page**
- [ ] Search by an email, by a word from a description, and by the email a failed sign-in tried; each finds the right rows.
- [ ] Category, Event and the from / to dates work alone and together; they survive going to page 2; **Clear** resets them. An end date before the start date shows an error toast.
- [ ] Click a row: *What changed* and *Where it came from* open; click again to close. Entries from before the rebuild show *Legacy* with "No field values were recorded".
- [ ] **Export CSV** downloads a file that Excel opens with `₱` and arrows intact, honours the filters, and leaves an *Activity log exported* entry.
- [ ] Light and dark mode both read well; on a phone-width window the filters stack and the table scrolls sideways.

## 11. Account removal keeps the ledger (Oct 2026)

Destructive: use throwaway accounts you registered, not the seeded ones. Automated cover: `AccountErasureTest`.

- [ ] **Balance blocks it.** Register a commuter, top up ₱50, then Settings → *Delete account* → refused with "Your wallet still holds PHP 50.00…", still signed in, nothing changed.
- [ ] **History is kept.** Spend the balance on a fare (wallet payment of ₱50 or more, so it reaches ₱0), delete the account → signed out. Logging in with the old email and password fails. As admin: *Fare Transactions* still lists that payment, now under `deleted-<id>@deleted.invalid`; *Top Ups* still lists the top-up; *PUJ Commuters* no longer lists the account.
- [ ] **Audit.** *Activity Log* shows *Deleted own account* with `outcome: anonymised` and the original email in its details, and nothing else for that deletion.
- [ ] **The email is free again.** Register a new account with the same email address: it works and starts with an empty history.
- [ ] **No history → really deleted.** Register a commuter that never paid or topped up, delete it: it is gone (admin *PUJ Commuters* never lists it, *Activity Log* says `outcome: deleted`).
- [ ] **Admin removes a commuter.** *PUJ Commuters* → delete a commuter with a payment and zero balance → toast "Commuter removed. Their payments and top-ups were kept for the records." and an *Anonymised* entry in the Activity Log naming you. A commuter with a balance is still refused ("Cannot delete commuter with a balance of PHP…").
- [ ] **Staff.** Add then delete a staff account: still *Deleted* (no payments), unchanged.

## 8. Pay on board (QR codes)

- [ ] Admin → **QR Codes**: press **Generate QR Code** (no form: a new *Code #n* appears), see its QR, **Print** opens a printable sheet, **Switch off** / **Switch on**, **Delete**. Other roles get 403.
- [ ] Commuter: map → **Buy a Ride** with no valid scan lands on **Scan the QR code**; after scanning (camera, typed token, or opening the link with the phone camera) the checkout opens for the same trip and shows *Verified on board*.
- [ ] A random QR code, a switched-off code and a deleted code are all refused.
- [ ] After paying, the next fare needs a new scan; a scan older than 15 minutes is refused. A replayed payment still shows its receipt.
