# UCN_SC_E005 — Topup Balance

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E005                                                                                                                                                           |
| **Use Case Name**     | Topup Balance                                                                                                                                                         |
| **Primary Actor**     | Commuter                                                                                                                                                              |
| **Secondary Actor**   | System / Admin / PayMongo Gateway                                                                                                                                     |
| **Goal**              | Allow commuters to have digital currency for easier commuting payments, funded by a real GCash or Maya payment.                                                       |
| **Trigger**           | Commuter presses the "+" button on the payment page and selects an amount and payment method.                                                                         |
| **Preconditions**     | 1. Commuter is successfully authenticated and logged in.<br>2. The digital wallet exists for the commuter (created at registration).<br>3. The System is operational.<br>4. The payment gateway is enabled (`PAYMONGO_ENABLED=true` with valid keys — PayMongo **test mode** keys in development; see `docs/PAYMONGO_SETUP.md`). |
| **Supporting Actors** | Wallet, Top-up history ledger, PayMongo (hosted checkout, signed webhook, payment-intent lookup)                                                                      |

> **Scope note:** a top-up is a **gateway payment**. The wallet is **never** credited when the form is submitted; it is credited only when PayMongo confirms that the money arrived. Until then the top-up exists as a `pending` record. In development the gateway runs against PayMongo's **test mode** (free, no real money); production uses live keys — the configuration safety rail in `docs/PAYMONGO_SETUP.md` refuses a live key outside `production`.

---

## MAIN FLOW

| #   | Actor's Action                                                                                | System Response                                                                                                                                                        |
| --- | --------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Commuter clicks the "+" button.                                                               | System displays the Topup page with the current wallet balance, a custom-amount input, and predefined amount options.                                                  |
| 2   | Commuter selects a predefined amount (₱50 / ₱100 / ₱200 / ₱500) **or** types a custom amount. | System highlights the selected preset (and clears the highlight when a non-preset value is typed).                                                                     |
| 3   | Commuter selects a payment method (**GCash** or **Maya**) and clicks **"Confirm Payment"**.           | System validates the request server-side: `amount` required, numeric, **min ₱10**, max ₱100,000; `payment-method` required and restricted to the self-service methods. |
| 4   |                                                                                               | System opens a **PayMongo checkout session** for that amount (sent in centavos) and the chosen channel (`gcash` / `paymaya`), with SmartCommute's own return and cancel URLs. |
| 5   |                                                                                               | System creates a **top-up record** with status `pending`, a unique transaction id (`TOPUP-XXXXXXXXXXXX`) and the gateway's payment-intent and checkout-session ids. The wallet is **not** touched. |
| 6   |                                                                                               | System redirects the commuter to PayMongo's hosted checkout page.                                                                                                      |
| 7   | Commuter authorizes the payment in GCash / Maya.                                              | PayMongo confirms the payment to the System by a **signed webhook** (`payment.paid`).                                                                                  |
| 8   |                                                                                               | System verifies the webhook's HMAC signature, finds the top-up by payment intent (or transaction id), and — in one locked database transaction — marks it `completed`, stamps `paid_at` and **adds the amount to the wallet balance**. |
| 9   |                                                                                               | System emails the commuter a **top-up receipt** (amount, method, new balance).                                                                                         |
| 10  | Commuter is returned to SmartCommute.                                                         | System shows *"Top-up successful! ₱X was added to your wallet."* on the top-up page with the new balance. If the webhook has not arrived yet, the System asks PayMongo directly (see A6) before answering. |

---

## Alternate Flow

**A1 – Commuter enters a Custom Amount**
1. At step 2, the commuter types an amount instead of choosing a preset.
2. System validates the amount (must be a number between ₱10 and ₱100,000) and proceeds with the top-up.

**A2 – Commuter changes the payment method**
1. Commuter selects a different payment method card before pressing "Confirm Payment".
2. System opens the checkout for the newly selected channel and records that method on the top-up.

**A3 – Commuter reviews past top-ups**
1. Commuter opens **Top-up History** (`/payment/topup/history`).
2. System lists the records — **with their status** (completed, pending, failed, cancelled) — with search (ID) and filters (method, date range). Only completed top-ups are shown as money added; a pending one offers a *Check status* link.

**A4 – Admin monitors top-ups**
1. Admin opens `/topups`.
2. System lists **every** top-up — completed, pending, failed and cancelled — with a **Status** column, and filters by *search, payment status, payment method, date range and amount range*. The "funds added" total counts **completed** top-ups only. The list shows 10 per page.

**A5 – Commuter cancels at checkout**
1. At step 6/7 the commuter backs out of the hosted checkout; PayMongo sends them to SmartCommute's cancel URL.
2. System marks the still-pending top-up `cancelled` ("Cancelled at checkout."), shows *"Top-up cancelled. Nothing was charged."* and leaves the wallet unchanged. No email is sent.
3. If the commuter later pays the still-open checkout session anyway, the confirmed payment still credits the wallet (steps 7–9) — money that arrived is never lost.

**A6 – The webhook is late or never arrives**
1. The commuter is sent to the return URL and the top-up is still `pending`. System shows *"Confirming your top-up"* (auto-refreshing) and, at most every 4 seconds, asks PayMongo for the payment intent.
2. When the intent is `succeeded` the System settles the top-up exactly as in step 8 and returns the commuter to the top-up page.
3. A commuter who never returns is covered by the scheduled `payments:reconcile` job (every 10 minutes): it asks PayMongo about every top-up still pending after 5 minutes and settles or fails it through the same code.
4. The webhook, the return URL and the reconciliation share **one** settlement (`TopupSettlement`), so whichever arrives first wins and the others find it done: the wallet is credited, and the receipt emailed, once.

**A7 – Gateway switched off in a development / test environment**
1. With `PAYMONGO_ENABLED=false` and a non-production environment, there is no money to wait for.
2. System simulates the payment: the same top-up record is created and settled at once (steps 5, 8, 9), and the commuter sees *"Successfully topped up!"*. No external call is made. This path does not exist in production (see E7).

---

## Postconditions

1. After a **confirmed** payment the commuter's wallet balance has been increased by the exact (2-decimal rounded) top-up amount, once.
2. A top-up record (`TopupHistory`) exists with status `completed`, its transaction id, gateway ids and `paid_at`. A top-up that was not paid ends `failed` or `cancelled` and the wallet is unchanged.
3. The commuter sees a success confirmation and the updated balance.
4. The commuter is emailed a **top-up receipt** (amount, method, new balance), or — for a declined payment — a notice that it *did not go through*. A mail failure is logged and never fails the top-up (`Notifier`).
5. Every state change of a top-up (created, completed, failed, cancelled) is written to the audit trail.

---

## Exceptions

**E1 – Payment Method Unavailable**
The commuter submits a method that is not available for self-service (anything other than **GCash** or **Maya**). System responds with *"The selected payment-method is invalid."* and performs **no** wallet change and **no** gateway call.
> Cash / "Admin Settlement" is intentionally **not** self-service; it can only be recorded by an admin.

**E2 – Invalid Custom Amount**
The amount is missing, non-numeric, negative, zero, or below the ₱10 minimum. System responds with the corresponding validation message (e.g. *"The amount must be at least 10."*) and performs **no** wallet change and **no** gateway call. Amounts above ₱100,000 are rejected as well.

**E3 – Already Paid / Duplicate Submission**
The commuter (double-clicks, refreshes, or replays the request) submits an identical top-up (same user, amount and method) within **2 minutes** of a previous **completed** one. System detects the duplicate, cancels the transaction and displays *"This top-up was already processed. Please wait a moment before retrying."* — the balance is credited only once. A top-up that is merely pending, failed or cancelled does not count, so a commuter who backed out of checkout can try again at once.

**E4 – Gateway Unavailable**
PayMongo cannot be reached, rejects the checkout request, or answers without a checkout link or payment intent. System displays *"The payment service is unavailable right now. Please try again."*, creates **no** top-up record, performs **no** wallet change, and counts the error toward the gateway failure alert (3 errors in 15 minutes email the Admins once — `GatewayMonitor`).

**E5 – Database / System Error**
The top-up record cannot be written after the checkout was opened. System displays *"Top-up failed. Please try again later."* and does **not** redirect to the gateway, so nothing can be paid for it; the wallet is unchanged.

**E6 – Payment Declined or Failed at the Gateway**
PayMongo reports the payment `failed` (webhook `payment.failed`, or the intent is `failed` / `canceled` / `expired` on a lookup). System marks the top-up `failed`, leaves the wallet unchanged and emails the commuter *"Your top-up did not go through"*. A `checkout_session.expired` event marks it `cancelled` without an email.

**E7 – No Working Gateway**
If the gateway is enabled but refuses to run (bad keys, mismatched host, live key outside production), or the environment is `production` with the gateway off, System displays *"Top-ups are unavailable right now. Please try again later."*, logs the reason, and credits **nothing** — a wallet is never funded without a payment. (A7 applies only to a development / test environment with the gateway deliberately off.)

**E8 – Forged or Unsigned Webhook**
A call to `/webhooks/paymongo` without a valid HMAC signature gets **HTTP 401** and changes nothing.

**E9 – Never Completed**
A top-up still `pending` 12 hours after it was started is closed as `cancelled` ("Expired: not completed within 12 hours.") by the hourly `payments:expire-pending` job, and whenever a top-up list or page is opened. The wallet is unchanged. A confirmed payment that arrives afterwards (a webhook for a genuinely paid session) still credits the wallet.

**E10 – Too Many Attempts (Rate Limiting)**
The commuter submits more than **5 top-up requests per minute**. System blocks the request (HTTP 429) and performs **no** wallet change and **no** gateway call.

**E11 – Suspected Fraud / Rate Limiting**
Covered by E10 (request-level) and E3 (duplicate-level). Automated credential-stuffing is additionally mitigated by the login throttle (see UCN_SC_E001 E5). The amount credited is always the amount stored on the server-created top-up record, never a figure sent by the browser or the webhook.

---

## Known Gaps / Notes

- **Duplicate detection is heuristic** (amount + method within 2 minutes of a *completed* top-up), not a true idempotency key. Legitimate repeat top-ups of the same amount within 2 minutes are blocked by design. Double-clicking *Confirm Payment* can open two checkouts; each must be paid separately and only a paid one credits the wallet.
- **Admin settlement recording has no UI.** Admins can *view* top-ups, but there is no admin action to record a cash/admin settlement, since the commuter-facing option was removed.
- **Sandbox checkout page.** PayMongo's **test-mode** hosted page shows *Authorize / Fail / Expire test payment* buttons and does not always send the browser back to SmartCommute after *Fail* or *Expire*. The outcome still reaches the System by webhook, reconciliation or expiry (E6, A6, E9). See `docs/PAYMONGO_SETUP.md`.
- **Webhook needs a public URL.** Locally PayMongo cannot call `/webhooks/paymongo` without a tunnel (ngrok); the return URL lookup and the scheduled reconciliation settle the top-up meanwhile.
- **Test mode only moves test money.** The PayMongo test keys never move real money; production needs live keys, a live webhook and a PayMongo account activated for GCash / Maya.
- **Maya's channel is `paymaya` in the PayMongo API** (`maya` is rejected). The code maps it; this was verified against the sandbox for opening a checkout, not for completing a Maya payment.
- **A top-up receipt is emailed** (amount, method, new balance) once the top-up is completed, and a failure notice when it fails.
- Top-ups share the gateway pipeline with fare payments (UCN_SC_E006): the same PayMongo client, webhook endpoint, signature check, reconciliation job, expiry job and failure alert.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `payment.topup`, `payment.topup.process` (`throttle:5,1,topup-process`), `payment.topup.returned`, `payment.topup.cancelled`, `payment.topup.history`, `admin.topups`, `paymongo.webhook` |
| Controller | `app/Http/Controllers/PaymentController.php::topup()`, `topupProcess()`, `startGatewayTopup()`, `simulatedTopup()`, `topupReturned()`, `topupCancelled()`, `webhook()` / `applyTopupEvent()`, `topupHistory()`, `showTopupsAdmin()` |
| Allowed methods | `PaymentController::SELF_SERVICE_TOPUP_METHODS = ['gcash', 'maya']`; channel map `PayMongoService::channelFor()` (`gcash`, `paymaya`) |
| Gateway client | `app/Services/PayMongoService.php` (`createCheckoutSession`, `retrievePaymentIntent`, `verifySignature`, `parseWebhook`, safety rail `configProblems()`), `config/paymongo.php` |
| Settlement | `app/Services/TopupSettlement.php` (`settle`, `fail`, `cancel` — row- and wallet-locked, idempotent) |
| Reconciliation / expiry | `app/Console/Commands/ReconcilePayments.php` (`payments:reconcile`), `ExpirePendingPayments.php` (`payments:expire-pending`), `TopupHistory::expireStale()` |
| Schema | `database/migrations/2026_10_31_090000_add_gateway_lifecycle_to_topup_histories_table.php` — `transaction_id` (unique), `paymongo_payment_intent_id`, `paymongo_checkout_session_id`, `paid_at`, `failed_at`, `failure_message` |
| View | `resources/views/commuter/topup.blade.php`, `topuppending.blade.php`, `topuphistory.blade.php` |
| Models | `app/Models/Wallet.php`, `app/Models/TopupHistory.php` (`STATUS_*`, `completed()` scope) |
| Emails | `app/Notifications/TopupReceipt.php`, `TopupFailed.php`, sent through `app/Support/Notifier.php`; the channels are `config/alerts.php` (`NOTIFICATION_CHANNELS`, default `mail`) |
| Tests (automation) | `tests/Feature/TopupGatewayTest.php` (checkout, webhook, return URL, cancel, reconcile, expiry, no-gateway behaviour, history); `tests/Feature/AutomationPhase2Test.php` (`test_topup_emails_a_receipt_with_the_new_balance`) |
