# Rate Limiting

SmartCommute limits how often a client may hit sensitive or expensive routes using Laravel's `throttle`
middleware. Limits are declared per route in `routes/web.php`; there is one extra named limiter (`api`) in
`app/Providers/RouteServiceProvider.php`.

## How it works

- A limit is written `throttle:N,M[,bucket]` — at most **N** requests per **M** minutes. `throttle:5,1` is
  5 requests per minute.
- Each request is counted in the cache (`CACHE_DRIVER=file`) under a key made of the **bucket name** plus the
  **requester**:
  - signed-in user → the user ID
  - guest → the route domain and the client IP address
- Over the limit, the request is rejected with **HTTP 429** and a `Retry-After` header. The counter clears
  when the window (M minutes) ends.
- The `api` limiter (`RouteServiceProvider::configureRateLimiting`) allows 60 requests per minute per user,
  or per IP for guests.

## Limits in place

| Route | Limit | Bucket |
|---|---|---|
| `POST /login` | 5 / min | unnamed |
| `POST /register` | 6 / min | unnamed |
| `POST /register/driver` | 6 / min | unnamed |
| Resend verification email (`verification.send`) | 6 / min | unnamed |
| `POST /email/verify/code` | 10 / min | unnamed |
| `GET /email/verify/{id}/{hash}` | 20 / min | unnamed |
| `POST /payment/scan` (verify) | 20 / min | unnamed |
| `POST /payment/scan/locate` | 30 / min | unnamed |
| `GET /ride/scan/{token}` | 20 / min | unnamed |
| Top-up submit | 5 / min | `topup-process` |
| Top-up return polling | 60 / min | `topup-returned` |
| `POST /vehicle-scans` (start) | 10 / min | `vscan-start` |
| `GET /vehicle-scans/{session}` (poll) | 120 / min | `vscan-poll` |
| `GET /scan/{token}` (phone page) | 60 / min | `vscan-phone` |
| `POST /scan/{token}` (phone upload) | 12 / min | `vscan-upload` |
| `api` limiter | 60 / min | per user / IP |

## Things to be aware of

### Unnamed throttles share one counter

The third argument is the bucket name. Without it, **every unnamed throttle shares a single counter for the
same user or IP**. Each route still compares that counter against its own limit, so hits on one route use up
the allowance of another. For example, three login attempts plus two registration attempts from the same IP
leave `/login` with no attempts left.

This is why polling routes (which fire every few seconds) always get their own bucket. Always name the bucket
on new routes, especially anything polled:

```php
->middleware('throttle:10,1,my-bucket-name')
```

Currently unnamed and therefore sharing a counter: login, both registration routes, the verification routes,
and the three payment-scan routes.

### Typo on the resend-verification route

`routes/web.php` declares `throttle:6.1` (a dot) instead of `throttle:6,1`. Laravel reads it as 6 attempts
with the default 1-minute window, so it behaves as intended by accident. It should be corrected.

### Guest limits are per IP, not per account

- Login throttling is per IP address. An attacker rotating IPs is not slowed down, and many legitimate users
  behind one shared IP (a campus or office network) share one allowance.
- There is no account lockout or CAPTCHA. Once the window passes, attempts continue.
- `app/Http/Middleware/TrustProxies.php` sets `$proxies = '*'`, so the app trusts `X-Forwarded-*` headers
  from any sender. If the app is reachable directly (not only through a trusted proxy), a client can forge its
  IP and sidestep the IP-based limits. In production, restrict `$proxies` to the real proxy or load balancer
  addresses.

### Counters live in the file cache

Counters are stored in `storage/framework/cache`. Clearing the cache (`php artisan cache:clear`) resets every
limit, and multiple app servers would each keep their own counters. Use a shared store such as Redis if the
app is ever scaled out.

## Adding or changing a limit

1. Add `->middleware('throttle:N,M,bucket-name')` to the route, with a unique bucket name.
2. Pick N from how often a real user can legitimately hit the route. A polling route needs at least
   (60 ÷ poll interval in seconds) × M, with headroom.
3. Update the table above.
