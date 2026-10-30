# UCN_SC_E006 — Pay Fare

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

## MAIN FLOW — A: Wallet (internal settlement)

| #   | Actor's Action                                                | System Response                                                                                                                                                                                                                                                      |
| --- | ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Commuter clicks **"Buy a Ride"**.                             | System validates `pickup`, `destination`, `distance` and re-**prices the fare server-side** from the published rate table, then displays the checkout page with the Final Fare, the commuter's balance, and the available methods (**GCash**, **Maya**, **Wallet**). |
| 2   | Commuter selects a payment method.                            | System highlights the selected method card and clears the highlight on the others.                                                                                                                                                                                   |
| 3   | Commuter selects **Wallet** and clicks **"Confirm Payment"**. | System re-validates the payload (`amount` numeric ≥ 0, `payment-method` ∈ `GCash, Maya, Wallet`, `transaction-id` required) and rejects a replayed `transaction-id`.                                                                                                 |
| 4   |                                                               | System **deducts the fare from the wallet**; if the balance is short the request is rejected (E2).                                                                                                                                                                   |
| 5   |                                                               | System creates a **payment record** with `status = paid`.                                                                                                                                                                                                            |
| 6   |                                                               | System **redirects (302)** to `/payment/receipt/{id}`, re-reading the stored payment, with the message *"Payment successful"*.                                                                                                                                       |

> The redirect at step 6 is deliberate: the receipt is rendered from the **persisted** record, so refreshing or going back can never charge the commuter twice.

## MAIN FLOW — B: GCash / Maya (PayMongo gateway)

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

## Alternate Flow

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

**A6 – The fare is attributed to the vehicle and the driver the commuter rides**
1. After scanning the ride QR code, the checkout page asks the browser for the commuter's position and sends it to `POST /payment/scan/locate` (`payment.scan.locate`).
2. System looks for an **active** vehicle whose driver's phone broadcast within the last `QR_BROADCAST_FRESH_MINUTES` (5) and which is within `QR_NEARBY_METERS` (150 m, widened by up to 100 m for a vague GPS fix) of the commuter, and takes the nearest (`NearbyVehicleFinder`).
3. System records that vehicle, the driver on duty and the distance on the scan (`qr_scans.vehicle_id`, `driver_id`, `matched_distance_m`, `located_at`) and tells the page *"On board ABC 1234."*. **The commuter's coordinates are matched and discarded: they are never stored.**
4. The payment that spends the scan inherits `vehicle_id` and `driver_id` (`payments`), so every fare belongs to a vehicle and a driver without anyone typing it; Fleet Analytics (UCN_SC_E020 A4) reports revenue per driver and vehicle from it.
5. With `QR_REQUIRE_NEARBY_VEHICLE=true` (**off by default**) a scan that was not matched to a vehicle cannot pay: the checkout answers *"We couldn't confirm you are on a SmartCommute PUJ…"*, charges nothing and keeps the scan (E11). Without it, an unmatched scan still pays, only unattributed.

**A7 – A missed webhook is reconciled automatically**
1. Every 10 minutes the scheduler runs `php artisan payments:reconcile` (only while the gateway is enabled). It takes gateway payments still `pending` after `PAYMONGO_RECONCILE_AFTER_MINUTES` (5), at most `PAYMONGO_RECONCILE_BATCH` (100), and asks PayMongo about each one.
2. An intent the gateway reports as paid is settled; one it reports as failed is closed as `failed`; anything else is left alone. A run stops after 3 gateway errors in a row. `--dry-run` reports without changing anything.
3. The webhook, the return page and this job all go through one `PaymentSettlement`, so whichever arrives first wins and the others find the payment already settled.

**A8 – Receipts and failure emails**
1. When a payment becomes **paid** (wallet, webhook, return page or reconciliation) the commuter is emailed **one** receipt: reference, trip, method, amount, time and a link to the receipt page.
2. When a gateway payment ends **failed** the commuter is emailed that it did not go through and that nothing was charged. A payment the commuter cancelled, or that expired, is not emailed.
3. A mail failure is logged and never changes the payment (`Notifier`).

**A9 – Repeated gateway failures alert the Admins**
1. Each gateway error is counted: a checkout that could not be opened, a payment that could not be looked up, a reconciliation call that failed.
2. When `GATEWAY_FAILURE_THRESHOLD` (3) errors fall inside `GATEWAY_FAILURE_WINDOW_MINUTES` (15), every Admin is emailed once for that window, with the most recent error (`GatewayMonitor`).

---

## Postconditions

1. A **payment record** exists with a unique transaction id, the chosen method, the route, the **server-computed** fare, and a terminal status.
2. If the method was `Wallet`, the balance is reduced by the fare; for a gateway method the balance is **unchanged**.
3. For a gateway payment: `status = paid` with `paid_at` set, and the PayMongo checkout-session / payment-intent ids stored for reconciliation.
4. The commuter sees the **digital receipt**, and the payment appears in their history (UCN_SC_E007) with a status badge for anything still `pending` / `failed` / `cancelled`.

5. When the scan was located, the payment carries the `vehicle_id` and `driver_id` of the vehicle the commuter rode (A6).
6. A **paid** payment has been emailed to the commuter as a receipt; a gateway payment that failed has been emailed as such (A8).
---

## Exceptions

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

**E11 – Not On a PUJ (only with `QR_REQUIRE_NEARBY_VEHICLE=true`)**
The scan was not matched to a vehicle (no PUJ near the commuter, or location refused). `process` redirects back with *"We couldn't confirm you are on a SmartCommute PUJ. Allow location access on this page, make sure you are inside the vehicle, then try again."*, creates no payment and does not spend the scan.

---

## Known Gaps / Notes

- **Top-ups are not reconciled** because they are not gateway payments (UCN_SC_E005): reconciliation (A7), failure emails and the gateway alert apply to **fare** payments through GCash / Maya.

- **The gateway is flag-controlled, and a safety rail refuses to arm it unsafely.** With it off, GCash/Maya are recorded as *declared* and **never charged*. `PayMongoService::configProblems()` refuses to run when the key type and base URL disagree (live key + sandbox host, sandbox key + live host), when a **live key is armed outside `production`**, when the host is not a PayMongo host, or when the secret key is empty. Each refusal logs `PAYMONGO CONFIGURATION REFUSED` and degrades to the declared-payment flow — it never 500s.
- **`statement_descriptor` is not honoured** on a checkout session (only on a standalone payment intent); PayMongo falls back to the merchant's registered descriptor.
- **No boarding permission a driver can check.** Paying attributes the fare to a vehicle and driver (A6) and a scan that is never followed by a fare is counted (`php artisan fares:flag-evasion`, daily 09:30: 3 unpaid scans in 7 days flags the commuter to the Admins, once per window), but a driver still has no screen that shows who has paid. The attribution depends on the driver's phone broadcasting and on the commuter allowing location.
- **The fare is re-priced server-side, but the *distance* is still client-supplied** (it comes from the browser's OSRM call). The amount can no longer be tampered with, but the distance can. Closing it means routing the trip server-side.
- **No refunds, cancellations, or fare disputes** from the commuter side. Once `paid`, a payment is immutable.
- **No webhook event-deduplication store.** Settlement is idempotent (`pending → paid` only happens once), but replayed events are not recorded.
- **No dead-letter/queue handling** — webhook processing is synchronous, so a slow PayMongo call blocks the request.
- The receipt is rendered by a single view (`commuter/viewreceipt.blade.php`); the former duplicate `commuter/receipt.blade.php` was removed in favour of POST/redirect/GET.
- Receipts are emailed (A8) and shown in-app; there is no PDF.

---

## Implementation References

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
| Settlement | `app/Services/PaymentSettlement.php` — the one place a fare becomes paid or failed, and where receipts / failure emails are sent |
| Reconciliation | `app/Console/Commands/ReconcilePayments.php` (`payments:reconcile`, every 10 minutes), `config/paymongo.php` (`reconcile_after_minutes`, `reconcile_batch`) |
| Gateway alert | `app/Services/GatewayMonitor.php`, `app/Notifications/GatewayAlert.php`, `config/alerts.php` (`gateway_failure_threshold`, `gateway_failure_window_minutes`) |
| Emails | `app/Notifications/PaymentReceipt.php`, `PaymentFailed.php`, sent through `app/Support/Notifier.php` |
| Vehicle attribution | `app/Services/NearbyVehicleFinder.php`, `RideVerificationController::locate()` (`payment.scan.locate`), `PaymentController::process()`, the location script in `resources/views/commuter/payment.blade.php`, `config/qr.php` |
| Fare-evasion flag | `app/Console/Commands/FlagFareEvasion.php`, `app/Notifications/FareEvasionFlag.php`, `config/alerts.php` (`fare_evasion_scans`, `fare_evasion_days`) |
| Schema | `database/migrations/2026_10_26_090000_add_vehicle_attribution_to_qr_scans_and_payments.php`, `2026_10_28_100000_stop_timestamps_updating_themselves.php` |
| Tests (automation) | `tests/Feature/AutomationPhase2Test.php` — settlement, reconciliation, gateway alert, locate, attribution, the on-board check, fare-evasion flag |
| Related | UCN_SC_E005 (Top-up), UCN_SC_E007 (Payment History) |
