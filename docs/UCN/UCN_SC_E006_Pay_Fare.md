# UCN_SC_E006 — Pay Fare

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E006                                                                                                                                                           |
| **Use Case Name**     | Pay Fare                                                                                                                                                              |
| **Primary Actor**     | Commuter                                                                                                                                                              |
| **Secondary Actor**   | System / PayMongo Gateway                                                                                                                                             |
| **Goal**               | Allow commuters to pay for the planned trip.                                                                                                                          |
| **Trigger**           | Commuter clicks the **"Buy a Ride"** button on the Fare Calculator in the map's left sidebar.                                                                          |
| **Preconditions**     | 1. Commuter is authenticated and logged in (route is behind `auth` + `verified`).<br>2. Commuter has an active, calculated route (pick-up + destination resolved).<br>3. A wallet exists for the commuter (created at registration).<br>4. For a gateway method: the PayMongo gateway is enabled (see *Scope note*). |
| **Supporting Actors** | Wallet, Payment ledger, Fare rate table, PayMongo Gateway                                                                                                             |

> **Scope note — the gateway is optional and flag-controlled.** `config/paymongo.php` defaults to **disabled**, which preserves the original behaviour: a gateway method is recorded as a *declared* method and the commuter goes straight to the receipt. With `PAYMONGO_ENABLED=true` and **sandbox test keys** (`sk_test_`/`pk_test_`) the fare is settled through PayMongo's hosted checkout at **zero cost** — see `docs/PAYMONGO_SETUP.md`. A production deploy would use live keys.
>
> **Boarding permission is still not issued** — see *Known Gaps*.

---

## MAIN FLOW — A: Wallet (internal settlement)

| #   | Actor's Action                                     | System Response                                                                                                                                                       |
| --- | ------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Commuter clicks **"Buy a Ride"**.                  | System validates `pickup`, `destination`, `distance` and re-**prices the fare server-side** from the published rate table, then displays the checkout page with the Final Fare, the commuter's balance, and the available methods (**GCash**, **Maya**, **Wallet**). |
| 2   | Commuter selects a payment method.                    | System highlights the selected method card and clears the highlight on the others.                                                                                     |
| 3   | Commuter selects **Wallet** and clicks **"Confirm Payment"**. | System re-validates the payload (`amount` numeric ≥ 0, `payment-method` ∈ `GCash, Maya, Wallet`, `transaction-id` required) and rejects a replayed `transaction-id`.    |
| 4   |                                                       | System **deducts the fare from the wallet**; if the balance is short the request is rejected (E2).                                                                      |
| 5   |                                                       | System creates a **payment record** with `status = paid`.                                                                                                                |
| 6   |                                                       | System **redirects (302)** to `/payment/receipt/{id}`, re-reading the stored payment, with the message *"Payment successful"*.                                        |

> The redirect at step 6 is deliberate: the receipt is rendered from the **persisted** record, so refreshing or going back can never charge the commuter twice.

## MAIN FLOW — B: GCash / Maya (PayMongo gateway)

| #   | Actor's Action                                    | System Response                                                                                                                                                                                                                          |
| --- | ----------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Commuter completes steps 1–2 above and picks **GCash** or **Maya**. | System creates a **checkout session** in one call (`POST /v1/checkout_sessions`) with `line_items` (amount converted to **centavos**) and `payment_method_types = [gcash|maya]`. PayMongo creates the payment intent as part of the session. |
| 2   |                                                       | System writes the **payment record** as `status = pending` with the checkout-session and payment-intent ids. **The wallet is not touched.**                                                                                          |
| 3   |                                                       | System **redirects the commuter to PayMongo's hosted checkout page**.                                                                                                                                                                       |
| 4   | Commuter completes payment on PayMongo.               | PayMongo sends a **signed webhook** (`POST /webhooks/paymongo`). System verifies the HMAC signature and, on `payment.paid`, sets `status = paid` and `paid_at`.                    |
| 5   | Commuter is returned to `/payment/returned`.               | System **reconciles**: already `paid` → receipt; still `pending` → System queries the intent at PayMongo and settles it when the status is `succeeded`; if the gateway reports a terminal failure, records `failed` and says so; otherwise shows the *"Confirming your payment"* screen, which auto-refreshes every few seconds. |
| 6   |                                                       | System displays the receipt, or — on decline/expiry — records `status = failed` / `cancelled`.                                                                                                                                           |

> Step 5 is the reconciliation fallback: if the webhook is lost, the return URL still settles the payment by asking PayMongo directly. Without it a commuter could sit on "pending" forever. The lookup is throttled to once every 4 seconds because this screen polls.

---

## Alternate Flow

**A1 – Commuter changes the payment method**
1. At the method step, the commuter clicks another method card.
2. System moves the highlight and uses the newly selected method for the transaction.

**A2 – Gateway method with the gateway disabled**
1. The commuter picks GCash/Maya while `PAYMONGO_ENABLED=false` (or no secret key is set).
2. System makes **no** external call and records the payment as a declared method, `status = paid`, then shows the receipt. This is the pre-integration behaviour and keeps local dev and CI working with no credentials.

**A3 – Commuter refreshes / goes back after paying**
1. The receipt is a `GET` on `/payment/receipt/{id}`; refreshing re-renders it from the stored record.
2. System creates **no** second payment and debits **no** further balance.

**A4 – Commuter cancels at the PayMongo checkout**
1. The commuter abandons the hosted checkout; PayMongo returns them to `/payment/cancelled`.
2. System marks the pending payment `status = cancelled` with *"Cancelled at checkout."* — **nothing is charged**.

**A5 – Commuter pays with the wallet instead**
1. The commuter selects **Wallet**; the card displays the live balance.
2. System subtracts the fare and stores the method as `Wallet`. The gateway is never contacted.

---

## Postconditions

1. A **payment record** exists with a unique transaction id, the chosen method, the route, the **server-computed** fare, and a terminal status.
2. If the method was `Wallet`, the balance is reduced by the fare; for a gateway method the balance is **unchanged**.
3. For a gateway payment: `status = paid` with `paid_at` set, and the PayMongo checkout-session / payment-intent ids stored for reconciliation.
4. The commuter sees the **digital receipt**, and the payment appears in their history (UCN_SC_E007) with a status badge for anything still `pending` / `failed` / `cancelled`.

---

## Exceptions

**E1 – Payment Method Unavailable**
The submitted `payment-method` is not one of `GCash`, `Maya`, `Wallet`. System responds with the validation message and creates **no** payment and **no** balance change.

**E2 – Insufficient Wallet Balance**
The commuter selects **Wallet** but the balance is less than the fare. System responds with *"You don't have enough balance"*; no payment, no balance change.

**E3 – Already Paid / Duplicate Submission**
The commuter replays a request carrying a `transaction-id` this commuter already paid. System responds with *"This fare has already been paid…"* — no second charge.

**E4 – External Payment Gateway Timeout / Error**
The gateway rejects the call, times out, or returns an error envelope. System responds with *"The payment service is unavailable right now. Please try again."*, creates **no** payment row and **no** checkout session, and logs `PAYMONGO … FAILED`. The commuter keeps the funds.

**E5 – Payment Declined at the Gateway**
The commuter declines or the card/e-wallet has insufficient funds. PayMongo reports the intent as `failed` (webhook `payment.failed`, or discovered by the return-URL reconciliation). System sets `status = failed` with `failed_at` and *"The payment was declined by the gateway."* The balance is untouched, and the pending screen stops spinning and explains the failure instead of looping forever.

**E6 – Checkout Session Expired**
PayMongo emits `checkout_session.expired`; System sets `status = cancelled` with *"The checkout session expired."*

**E7 – Unsigned / Tampered Webhook**
A webhook arrives with an invalid HMAC signature. System logs the rejection and responds **401** without touching any payment.

**E8 – Database / System Error**
Creating the payment record fails. System responds with *"There was a problem processing the payment"* and performs **no** balance change.

**E9 – Missing Wallet**
The commuter has no wallet row (data inconsistency). System aborts with HTTP 404 rather than crashing.

**E10 – Fare Table Empty**
No fare rates are published for the current fare table, so the fare cannot be priced. System responds with *"Fares are unavailable right now. Please try again later."*

---

## Known Gaps / Notes

- **The gateway is flag-controlled, and a safety rail refuses to arm it unsafely.** With it off, GCash/Maya are recorded as *declared* and **never charged*. `PayMongoService::configProblems()` refuses to run when the key type and base URL disagree (live key + sandbox host, sandbox key + live host), when a **live key is armed outside `production`**, when the host is not a PayMongo host, or when the secret key is empty. Each refusal logs `PAYMONGO CONFIGURATION REFUSED` and degrades to the declared-payment flow — it never 500s.
- **`statement_descriptor` is not honoured** on a checkout session (only on a standalone payment intent); PayMongo falls back to the merchant's registered descriptor.
- **No boarding permission / ticket.** Paying does **not** create a trip, a one-time ride token, or any state a driver or vehicle can verify. This remains the largest functional gap in this use case.
- **The fare is re-priced server-side, but the *distance* is still client-supplied** (it comes from the browser's OSRM call). The amount can no longer be tampered with, but the distance can. Closing it means routing the trip server-side.
- **Discount fares are not verified.** The calculator displays a discounted figure for Student/Elderly/PWD, but only the **regular** fare is priced and charged server-side; no ID is checked.
- **No refunds, cancellations, or fare disputes** from the commuter side. Once `paid`, a payment is immutable.
- **No webhook event-deduplication store.** Settlement is idempotent (`pending → paid` only happens once), but replayed events are not recorded.
- **No dead-letter/queue handling** — webhook processing is synchronous, so a slow PayMongo call blocks the request.
- The receipt is rendered by a single view (`commuter/viewreceipt.blade.php`); the former duplicate `commuter/receipt.blade.php` was removed in favour of POST/redirect/GET.
- Receipts are in-app only — no email or PDF.

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
| Related | UCN_SC_E005 (Top-up), UCN_SC_E007 (Payment History) |