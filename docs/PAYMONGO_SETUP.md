# PayMongo Sandbox Setup

SmartCommute can settle fares through **PayMongo** (GCash / Maya / cards). The
integration is **disabled by default** and works with **no credentials at all** —
with the flag off, a gateway method is simply recorded as a declared method and
the commuter goes straight to the receipt, exactly as before.

Turn it on with **sandbox test keys** and no real money ever moves.

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
and reconciles. A webhook is the more reliable path.

PayMongo must reach this app over the public internet. With **ngrok** installed:

```bash
ngrok http 80
```

then in the dashboard set the webhook URL to:

```
https://<subdomain>.ngrok-free.app/webhooks/paymongo
```

Subscribe to `payment.paid`, `payment.failed`, `payment.expired` and
`checkout_session.expired`.

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

The return URLs carry **our own** transaction id. PayMongo payment intents expose
no `reference_id`, so the lookup deliberately does not depend on a
gateway-specific field.

**Wallet** payments are unaffected — they settle internally and never touch the
gateway.

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

25 tests covering: the disabled-flag fallback, checkout-session creation, the
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
| Payment stuck `pending` | The webhook never arrived and the return URL found it unsettled. Check the dashboard's webhook delivery log. |