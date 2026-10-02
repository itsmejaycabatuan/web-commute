# UCN_SC_E007 — View Payment History

| Field                 | Value                                                                                                                                                                 |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E007                                                                                                                                                           |
| **Use Case Name**     | View Payment History                                                                                                                                                   |
| **Primary Actor**     | Commuter                                                                                                                                                              |
| **Secondary Actor**   | System                                                                                                                                                                |
| **Goal**               | Allow the commuter to review past fare payments so they can verify that their money was deducted correctly.                                                          |
| **Trigger**           | Commuter clicks the **"History"** button (map right sidebar / receipt list, or **"Back to History"** on a receipt).                                                     |
| **Preconditions**     | 1. Commuter is authenticated and logged in.<br>2. The payment ledger is readable.<br>3. A wallet exists for the commuter (the page displays the balance).          |
| **Supporting Actors** | Payment ledger, Wallet                                                                                                                                                 |

---

## MAIN FLOW

| #   | Actor's Action                                        | System Response                                                                                                                                                       |
| --- | ----------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Commuter opens Payment History.                      | System queries **only that commuter's** payments and displays them newest-first in a card list, together with the wallet balance and the **total spent**.        |
| 2   | Commuter scrolls down the list.                      | System paginates the list (**4 receipts per page**) and fetches the older page on demand; filters/search are preserved across pages.                            |
| 3   | Commuter taps a specific transaction.                | System displays the **receipt detail** for that payment (route, distance, method, transaction id, fare, paid-at) at `/payment/receipt/{id}`.                       |

> The detail view is **owner-scoped**: `showReceipt()` filters on `paid_by = Auth::id()`, so a commuter cannot open another commuter's receipt by guessing the id (HTTP 404).

---

## Alternate Flow

**A1 – Commuter selects another transaction**
1. At step 3 the commuter opens a different row (either from the list or from the previous receipt via **"Back to History"**).
2. System displays that payment's receipt.

**A2 – Commuter wants top-up history instead**
1. The commuter clicks the **"+" / wallet** button next to the balance in the map header (or the wallet card on the profile page).
2. System opens `/payment/topup/history` (see UCN_SC_E005 A3) and redirects to `/payment/history` when they navigate back.

**A3 – Filter by date range**
1. The commuter picks a **Quick Range** (7 / 30 / 90 days) or types an explicit `from_date` / `to_date`, then clicks **"Apply Filters"**.
2. System filters on `whereDate('paid_at', ...)` and shows only transactions inside that period. **"Clear Filters"** restores the full list.

**A4 – Search by transaction ID**
1. The commuter types into the search field (placeholder: *"Transaction ID or destination"*) and applies.
2. System matches `transaction_id` **or** `destination` with a `LIKE %term%` and shows the matching records.

**A5 – Download a receipt**
1. In the detail view the commuter clicks **"Download Receipt"**.
2. System renders the receipt to a PNG in the browser (html2canvas, scale 3) and saves it to the commuter's device as `Receipt-#SC-XXXXXXXX.png`. Nothing is sent to the server.

---

## Postconditions

1. The list reflects the commuter's **active search term and date range** (carried in the query string and re-applied on every page).
2. The displayed **total spent** is the sum of *all* of the commuter's fare payments.
3. Opening a receipt reveals that single payment only; no other commuter's data is reachable.

---

## Exceptions

**E1 – No History Found**
The commuter has never paid a fare. System renders the empty state *"No transactions found"* — no error, no empty table.

**E2 – No Results for Filter / Search**
The applied filter or search term matches nothing. System renders the same empty state and offers **"Clear Filters"** to return to the full list.

**E3 – Receipt Not Found / Not Yours**
The requested receipt id does not exist, or belongs to another commuter. System responds with HTTP 404.

**E4 – Network / Server Error**
**Not handled explicitly** — see *Known Gaps*.

---

## Known Gaps / Notes

- **No explicit network/server error state.** A failed query renders an empty list rather than an error banner, so "no results" and "something broke" look identical to the commuter.
- **Quick ranges are relative windows (7 / 30 / 90 days)**, not the calendar presets *"Last Month"* / *"This Month"* from the original narrative; only the relative windows and free-form dates exist.
- **"Total spent" ignores the active filters** — it is computed with an unfiltered `sum('price')`, so it can disagree with the visible list. Filtered totals would need `sum()` on the filtered query.
- **No sorting or column options** — the order is fixed to `paid_at DESC`.
- **Pagination size is 4** per page, which is small for a commuter with many trips.
- **The list markup is duplicated** for the desktop and mobile layouts; the paginator is therefore rendered twice on the page (hidden by breakpoint).
- The page renders the balance via `Wallet::first()` (HTTP 404 if the wallet row is missing).

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `payment.history` (`GET /payment/history`), `payment.showReceipt` (`GET /payment/receipt/{id}`), `payment.topup.history` |
| Controller | `app/Http/Controllers/PaymentController.php::history()`, `showReceipt()` |
| Pagination | `->orderBy('paid_at', 'desc')->paginate(4)->withQueryString()` |
| Views | `resources/views/commuter/paymenthistory.blade.php`, `resources/views/commuter/viewreceipt.blade.php` |
| Models | `app/Models/Payment.php`, `app/Models/Wallet.php` |
| Related | UCN_SC_E006 (Pay Fare — creates these records), UCN_SC_E005 (Top-up History) |