# PayMongo Sandbox Setup

SmartCommute settles **fares** and **wallet top-ups** through **PayMongo**
(GCash / Maya). The integration is **disabled by default** and works with **no
credentials at all** — with the flag off, a gateway fare is simply recorded as a
declared method and the commuter goes straight to the receipt, and a top-up is
**simulated** (credited at once) in a development or test environment. In
`production` a top-up with no working gateway is **refused**, so a wallet is never
funded without a payment.

Turn it on with **sandbox (test mode) keys** — PayMongo's test mode is free and no
real money ever moves.

---

## 1. Get test keys

1. Sign in to the PayMongo (now **Maya**) dashboard.
2. Switch the account to **test / sandbox mode**.
3. Copy the **secret key** (`sk_test_…`) and **public key** (`pk_test_…`).

> This is the one step that cannot be automated — keys are account-scoped.
> Note that PayMongo rebranded under Maya; if a brand-new account does not
> expose test mode, the gateway stays off and the app is unaffected.

## 2. Configure

```dotenv
PAYMONGO_ENABLED=true
PAYMONGO_SECRET_KEY=sk_test_xxxxxxxx
PAYMONGO_PUBLIC_KEY=pk_test_xxxxxxxx
```

```bash
php artisan config:clear
```

`PAYMONGO_BASE_URL` stays `https://api.paymongo.com/v1` for sandbox; live keys
would use `https://api.live.paymongo.com/v1`.

## 3. Migrate

```bash
php artisan migrate
```

Adds the payment lifecycle (`status`, `paymongo_payment_intent_id`,
`paymongo_checkout_session_id`, `paymongo_reference_id`, `failed_at`,
`failure_message`) and makes `payments.paid_at` nullable so a *pending* payment
can exist before money moves.

The top-up table gets the same lifecycle
(`2026_10_31_090000_add_gateway_lifecycle_to_topup_histories_table`):
`transaction_id` (unique, `TOPUP-XXXXXXXXXXXX`), `paymongo_payment_intent_id`,
`paymongo_checkout_session_id`, `paid_at`, `failed_at` and `failure_message`. Rows
from before the change are stamped paid at the time they were created.

---

## 4. Test cards

Any of these work at the sandbox checkout:

| Card | Result |
|---|---|
| `4000 0000 0000 0020` | ✅ Payment succeeds |
| `4000 0000 0000 9995` | ❌ Insufficient balance |
| `4000 0000 0000 0069` | ❌ Expired card |

Any future expiry / any CVC.

---

## 5. Webhooks (optional but recommended)

Without a webhook the payment still settles: the **return URL** polls PayMongo
and reconciles, and the scheduler's reconciliation job (below) catches whatever
the commuter never came back for. A webhook is the quickest path.

PayMongo must reach this app over the public internet. With **ngrok** installed:

```bash
ngrok http 80
```

then in the dashboard set the webhook URL to:

```
https://<subdomain>.ngrok-free.app/webhooks/paymongo
```

Subscribe to `payment.paid`, `payment.failed`, `payment.expired` and
`checkout_session.expired`. **One endpoint serves both fares and top-ups**: the
webhook looks the event's payment intent (then the `transaction_id` in its metadata)
up among fare payments first and then among top-ups.

The endpoint is **CSRF-exempt** (`app/Http/Middleware/VerifyCsrfToken.php`) and
**unauthenticated by design** — every call is verified against the HMAC signature
(PayMongo sends both `paymongo-signature` and `paymongo-te-signature`; both are
accepted). An unsigned or tampered call gets **401**.

If you set a distinct webhook signing key in the dashboard, put it in
`PAYMONGO_WEBHOOK_SECRET`; otherwise the secret key is used.

---

## 6. How a payment flows

```
Commuter picks GCash/Maya
  └─ POST /payment/process
       ├─ fare RE-PRICED server-side (posted amount is ignored)
       ├─ POST /v1/checkout_sessions (line_items + payment_method_types)
       │     → cs_… + embedded payment_intent (pi_…)
       ├─ Payment row written as status=pending
       └─ redirect → PayMongo hosted checkout

PayMongo ──► POST /webhooks/paymongo   (HMAC verified)
               └─ payment.paid  → status=paid, paid_at set
               └─ payment.failed→ status=failed

Commuter ──► /payment/returned?ref=#SC-XXXXXXXX
               ├─ already paid          → receipt
               ├─ polls PayMongo, paid  → settle + receipt
               └─ still pending         → "Confirming your payment…" (auto-refresh)

Commuter ──► /payment/cancelled?ref=#SC-XXXXXXXX  → status=cancelled, nothing charged
```

### How a top-up flows

```
Commuter picks GCash/Maya on /payment/topup
  └─ POST /payment/topup/process        (throttle 5/min, named bucket topup-process)
       ├─ amount ₱10 – ₱100,000 and method validated; a completed identical top-up
       │   within 2 minutes is refused
       ├─ POST /v1/checkout_sessions (centavos, payment_method_types = gcash | paymaya)
       │     → cs_… + embedded payment_intent (pi_…)
       ├─ TopupHistory row written as status=pending, transaction_id=TOPUP-…
       │   (the wallet is NOT touched)
       └─ redirect → PayMongo hosted checkout

PayMongo ──► POST /webhooks/paymongo   (HMAC verified)
               └─ payment.paid   → TopupSettlement::settle  → wallet += amount, status=completed, receipt email
               └─ payment.failed → TopupSettlement::fail    → status=failed, "did not go through" email
               └─ *.expired      → TopupSettlement::cancel  → status=cancelled

Commuter ──► /payment/topup/returned?ref=TOPUP-…
               ├─ already completed          → /payment/topup  "Top-up successful!"
               ├─ polls PayMongo, succeeded  → settle → same
               ├─ polls PayMongo, failed     → failed, back to /payment/topup with an error
               └─ still pending              → "Confirming your top-up…" (auto-refresh)

Commuter ──► /payment/topup/cancelled?ref=TOPUP-…  → status=cancelled, nothing charged
```

`TopupSettlement` is the only code that credits a wallet. It runs in a database
transaction that locks both the top-up row and the wallet row, and refuses to act on
a top-up that is already `completed`, so the webhook, the return URL and
`payments:reconcile` can all report the same payment and the wallet is credited
once. It *does* credit a top-up that was `cancelled` or `failed` locally if the
gateway later says the money arrived (the commuter cancelled, then paid the
still-open session): the gateway's word is the truth, and only verified paths
(a signed webhook, or a lookup at PayMongo) reach it.

If the gateway is enabled but the safety rail refuses it, or the environment is
`production` with the gateway off, a top-up is refused with *"Top-ups are
unavailable right now."* and nothing is recorded.

The return URLs carry **our own** transaction id. PayMongo payment intents expose
no `reference_id`, so the lookup deliberately does not depend on a
gateway-specific field.

**Wallet** payments are unaffected — they settle internally and never touch the
gateway.

### Pending payments expire after 12 hours

A gateway payment **or top-up** still `pending` 12 hours after it was started is closed as
`cancelled` ("Expired: not completed within 12 hours"; nothing was charged), so it
does not sit in the ledger forever. This runs hourly (`payments:expire-pending`,
needs the usual `php artisan schedule:run` cron) and also whenever a payment list,
the ledger or a checkout page is opened, so it works without cron. A webhook that
arrives later for a genuinely paid session still settles the payment.

### Missed webhooks are reconciled every 10 minutes

A commuter who pays and then closes the tab, with the webhook lost on the way, used
to leave the payment `pending` until it expired. `payments:reconcile` (every 10
minutes, needs the `schedule:run` cron) now asks PayMongo about each gateway payment
still `pending` after `PAYMONGO_RECONCILE_AFTER_MINUTES` (5), up to
`PAYMONGO_RECONCILE_BATCH` (100) per run, and settles or fails it through the same
code the webhook and the return URL use (`PaymentSettlement`), so the three paths
cannot disagree and a payment is settled once however many of them arrive. Pending
**top-ups** are checked in the same run through `TopupSettlement`.

```bash
php artisan payments:reconcile --dry-run   # names each payment and what it would do; changes nothing
php artisan payments:reconcile
```

- With the gateway off the command does nothing.
- Three gateway errors in a row stop the run (the gateway is down; asking more only adds noise).
- **Receipts and notices:** when a payment is settled by any path the commuter is emailed
  *Receipt for your fare #SC-…* once; a failed one gets *did not go through*; a payment the
  commuter cancelled at checkout sends nothing.
- **Gateway failure alert:** a checkout that could not be opened, a payment that could not be
  looked up, or a declined payment is counted (`GatewayMonitor`). At `GATEWAY_FAILURE_THRESHOLD` (3)
  failures inside `GATEWAY_FAILURE_WINDOW_MINUTES` (15), the Admins are emailed once, and not
  again until the window has passed.
- The activity log records *Payment settled / failed / expired* with *System* as the actor
  when the scheduler did it.

### The sandbox checkout page

PayMongo's **test-mode** hosted checkout shows *Authorize / Fail / Expire test
payment* buttons instead of a normal "cancel" link, and it does **not** send the
browser back to SmartCommute after *Fail* or *Expire*. That page is PayMongo's and
cannot be changed from this app. What the app does to cover it:

- (a top-up behaves the same: it stays `pending` until the webhook, a return-URL lookup or the 10-minute
  reconciliation sees the result, and shows in **Top-up History** with its real status)
- the failure or expiry reaches us through the webhook (`payment.failed`, or a
  session that expires), which marks the payment `failed` / `cancelled`;
- the commuter can press the browser's back button or open **Payment History**,
  where the attempt shows with its real status and nothing was charged;
- the checkout page shows a *payment already in progress* notice, with a *Check
  payment status* link, if the commuter comes back to the same trip while a payment
  is still pending, and a repeated submission is answered with the existing
  payment instead of opening a second checkout.

In live mode the commuter sees the real bank / e-wallet screens and PayMongo
redirects to the success / cancel URLs as described above.

---

## Configuration safety rail

`PayMongoService::configProblems()` refuses to arm the gateway when the
configuration is unsafe, logging `PAYMONGO CONFIGURATION REFUSED` and falling back
to the declared-payment flow:

| Condition | Why it is refused |
|---|---|
| Live key (`sk_live_`) + sandbox host | Wrong money |
| Sandbox key (`sk_test_`) + live host | Would silently fail in production |
| **Live key outside `production`** | A dev machine must never take real money |
| Host that isn't a PayMongo host | Typo / spoofed endpoint |
| `enabled` with an empty secret key | Misconfiguration |

Verify the current state any time:

```bash
php artisan tinker --execute="echo json_encode(app(App\Services\PayMongoService::class)->configProblems());"
```

Empty array `[]` = safe. Any string = the gateway is **off** and payments fall
back to being recorded as declared.

---

## 7. Tests

The whole suite runs **without keys**; every gateway call is faked:

```bash
./vendor/bin/phpunit tests/Feature/PayMongoPaymentTest.php
```

Reconciliation, the receipt emails and the gateway alert are covered in
`tests/Feature/AutomationTest.php` and `AutomationPhase2Test.php`. Top-ups have their own
suite, `tests/Feature/TopupGatewayTest.php` (checkout request, no credit before payment,
webhook credit and replay, payment-event intent matching, forged webhooks, decline, a payment
that arrives after cancelling, return-URL lookup, cancel, reconciliation, 12-hour expiry,
the no-gateway behaviour in development and production, and the history screen).
`phpunit.xml` sets `PAYMONGO_ENABLED=false` so the suite can never reach the sandbox even
though the development `.env` has the gateway on. `PayMongoPaymentTest` has 25 tests covering: the disabled-flag fallback, checkout-session creation, the
declared payment channel, centavos conversion, anti-tamper re-pricing,
wallet-stays-internal, gateway failure, signature rejection (unsigned +
tampered), settlement, replay idempotency, decline, return-URL reconciliation,
pending state, cancellation, cross-commuter isolation, fare tier selection, and
all five configuration-safety-rail refusals.

---

## 8. Verified API contract

Confirmed live against the sandbox (these are easy to get wrong):

| Fact | Value |
|---|---|
| Checkout session requires | `line_items[]` + `payment_method_types[]` — **`payment_intent_id` is rejected** |
| The intent is created by | the checkout session, returned nested at `data.attributes.payment_intent.id` |
| Checkout URL | `data.attributes.checkout_url` |
| Intent status | `data.attributes.status` |
| **Success status** | **`succeeded`** — *not* `paid`. Missing this left successful payments stuck on the pending screen. |
| Failure status | `failed` / `canceled` |
| **Maya channel** | **`paymaya`** in `payment_method_types` — `maya` and `maya_checkout` are rejected with *"… is an invalid payment_method"*. `PayMongoService::channelFor()` maps it. |
| Minimum amount | A ₱10 GCash checkout session is accepted in test mode (the app's own minimum is ₱10). |
| There is **no** | `reference_id` field on payment intents |
| Amounts | minor units (centavos) |
| Basic auth | secret key as username, empty password |

---

## 9. If the commuter is stuck on "Confirming your payment"

The pending screen reconciles itself, but if it ever hangs, check what PayMongo
actually thinks:

```bash
php artisan tinker --execute="
\$p = App\Models\Payment::where('status','pending')->latest('id')->first();
echo \$p->transaction_id.PHP_EOL;
echo json_encode(app(App\Services\PayMongoService::class)->retrievePaymentIntent(\$p->paymongo_payment_intent_id));
"
```

If the intent comes back `succeeded` but the row is still `pending`, the status
check has a gap — the value must be in `isSettled()`. To reconcile by hand:

```bash
php artisan tinker --execute="
\$svc = app(App\Services\PayMongoService::class);
foreach (App\Models\Payment::where('status','pending')->get() as \$p) {
    if (\$svc->isSettled(\$svc->retrievePaymentIntent(\$p->paymongo_payment_intent_id)['status'] ?? null)) {
        \$p->update(['status' => 'paid', 'paid_at' => \$p->paid_at ?? now()]);
        echo 'SETTLED '.\$p->transaction_id.PHP_EOL;
    }
}
"
```

## 10. Troubleshooting

| Symptom | Cause |
|---|---|
| `The payment service is unavailable right now.` | The gateway refused to arm (see the safety rail above), or PayMongo returned an error envelope. Check `storage/logs/laravel.log` for `PAYMONGO CONFIGURATION REFUSED` or `PAYMONGO … FAILED`. |
| Checkout clicks but stays on the page | The browser posted back with a validation error (pick-up/destination missing, or a fare table with no published rates). The error banner on the checkout page shows which field. |
| Webhook always 401 | The signature is computed over the **raw** body; make sure nothing re-encodes the JSON in front of the controller. |
| Top-up stays `pending` after paying | Same causes. Open `/payment/topup/returned?ref=TOPUP-…` (or **Top-up History → Check status**) to make the app ask PayMongo, or run `php artisan payments:reconcile`. |
| `maya is an invalid payment_method` in the log | An old build sent `maya`; the channel is `paymaya`. |
| Payment stuck `pending` | The webhook never arrived and the return URL found it unsettled. Check the dashboard's webhook delivery log. |