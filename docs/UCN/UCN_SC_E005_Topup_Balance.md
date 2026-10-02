# UCN_SC_E005 — Topup Balance

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E005                                                                                                                                                           |
| **Use Case Name**     | Topup Balance                                                                                                                                                         |
| **Primary Actor**     | Commuter                                                                                                                                                              |
| **Secondary Actor**   | System / Admin                                                                                                                                                        |
| **Goal**              | Allow commuters to have digital currency for easier commuting payments.                                                                                               |
| **Trigger**           | Commuter presses the "+" button on the payment page and selects an amount and payment method.                                                                         |
| **Preconditions**     | 1. Commuter is successfully authenticated and logged in.<br>2. The digital wallet exists for the commuter (created at registration).<br>3. The System is operational. |
| **Supporting Actors** | Wallet, Top-up history ledger                                                                                                                                         |

> **Scope note:** the current implementation **does not integrate an external payment gateway**. The top-up is credited directly by the System after the commuter confirms the method. Steps relating to gateway redirects, callback signature validation and gateway cancellation are therefore **not implemented** — see *Known Gaps*.

---

## MAIN FLOW

| #   | Actor's Action                                                                                | System Response                                                                                                                                                        |
| --- | --------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Commuter clicks the "+" button.                                                               | System displays the Topup page with the current wallet balance, a custom-amount input, and predefined amount options.                                                  |
| 2   | Commuter selects a predefined amount (₱50 / ₱100 / ₱200 / ₱500) **or** types a custom amount. | System highlights the selected preset (and clears the highlight when a non-preset value is typed).                                                                     |
| 3   | Commuter selects a payment method (**GCash** or **Maya**) and clicks **"Proceed"**.           | System validates the request server-side: `amount` required, numeric, **min ₱10**, max ₱100,000; `payment-method` required and restricted to the self-service methods. |
| 4   |                                                                                               | System adds the amount to the commuter's wallet balance.                                                                                                               |
| 5   |                                                                                               | System creates a **top-up history record** (user, wallet, amount added, payment method) for auditing.                                                                  |
| 6   |                                                                                               | System displays the success message *"Successfully topped up!"* and returns to the top-up page showing the new balance.                                                |

---

## Alternate Flow

**A1 – Commuter enters a Custom Amount**
1. At step 2, the commuter types an amount instead of choosing a preset.
2. System validates the amount (must be a number between ₱10 and ₱100,000) and proceeds with the top-up.

**A2 – Commuter changes the payment method**
1. Commuter selects a different payment method card before pressing "Proceed".
2. System uses the newly selected method for the transaction record.

**A3 – Commuter reviews past top-ups**
1. Commuter opens **Top-up History** (`/payment/topup/history`).
2. System lists the records with search (ID) and filters (method, date range), plus total amount added.

**A4 – Admin monitors top-ups**
1. Admin opens `/topups`.
2. System lists all commuters' top-ups with the same search/filter options and a grand total.

---

## Postconditions

1. The commuter's wallet balance has been increased by the exact (2-decimal rounded) top-up amount.
2. A digital top-up / transaction record has been generated and saved (`TopupHistory`).
3. The commuter sees a success confirmation and the updated balance.

---

## Exceptions

**E1 – Payment Method Unavailable**
The commuter submits a method that is not available for self-service (anything other than **GCash** or **Maya**). System responds with *"The selected payment method is invalid."* and performs **no** wallet change.
> Cash / "Admin Settlement" is intentionally **not** self-service; it can only be recorded by an admin.

**E2 – Invalid Custom Amount**
The amount is missing, non-numeric, negative, zero, or below the ₱10 minimum. System responds with the corresponding validation message (e.g. *"The amount must be at least 10."*) and performs **no** wallet change. Amounts above ₱100,000 are rejected as well.

**E3 – Already Paid / Duplicate Submission**
The commuter (double-clicks, refreshes, or replays the request) submits an identical top-up (same user, amount and method) within **2 minutes** of a previous successful one. System detects the duplicate, cancels the transaction and displays *"This top-up was already processed. Please wait a moment before retrying."* — the balance is credited only once.

**E4 – Database / System Error**
The wallet update fails. System displays *"Top-up failed. Please try again later."* and performs **no** wallet change.

**E5 – Too Many Attempts (Rate Limiting)**
The commuter submits more than **5 top-up requests per minute**. System blocks the request (HTTP 429) and performs **no** wallet change.

**E6 – Suspected Fraud / Rate Limiting**
Covered by E5 (request-level) and E3 (duplicate-level). Automated credential-stuffing is additionally mitigated by the login throttle (see UCN_SC_E001 E5).

---

## Known Gaps / Notes

- **No external payment gateway.** There is no transaction ID, no redirect, no callback and **no signature verification**; the wallet is credited directly on form submission. Integrating GCash/Maya (e.g. PayMongo) would require re-adding the original steps 3–5, a webhook/callback endpoint, signature validation, and a pending→settled top-up state.
- **Duplicate detection is heuristic** (amount + method within 2 minutes), not a true idempotency key. Legitimate repeat top-ups of the same amount within 2 minutes are blocked by design.
- **Admin settlement recording has no UI.** Admins can *view* top-ups, but there is no admin action to record a cash/admin settlement, since the commuter-facing option was removed.
- Transactional email / receipt for a top-up is not sent; the receipt is only visible in-app (top-up history).

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `payment.topup`, `payment.topup.process` (`throttle:5,1`), `payment.topup.history`, `admin.topups` |
| Controller | `app/Http/Controllers/PaymentController.php::topup()`, `topupProcess()`, `topupHistory()`, `showTopupsAdmin()` |
| Allowed methods | `PaymentController::SELF_SERVICE_TOPUP_METHODS = ['gcash', 'maya']` |
| View | `resources/views/commuter/topup.blade.php`, `topuphistory.blade.php` |
| Models | `app/Models/Wallet.php`, `app/Models/TopupHistory.php` |