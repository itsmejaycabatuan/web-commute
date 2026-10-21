<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\TopupHistory;
use App\Models\Wallet;
use App\Services\FareCalculator;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentController extends Controller
{
    /**
     * Payment methods a commuter may self-select for a FARE payment.
     *
     * 'GCash' and 'Maya' are settled through PayMongo when the gateway is
     * enabled (see config/paymongo.php); with the gateway disabled they fall
     * back to being recorded as a declared method only. 'Wallet' is always
     * settled internally from the commuter's balance.
     */
    public const SELF_SERVICE_FARE_METHODS = ['GCash', 'Maya', 'Wallet'];

    public function __construct(
        protected PayMongoService $paymongo,
        protected FareCalculator $fares
    ) {}

    public function index(Request $request)
    {
        // dd($request);
        Payment::expireStale();
        $user = Auth::user();
        $userId = $user->id;
        $balance = $this->walletFor($userId)->balance;

        $validated = $request->validate([
            'pickup' => 'required',
            'destination' => 'required',
            'distance' => 'required|numeric|min:0',
            'price-regular' => 'required',
        ], [
            // UCN_SC_E004 E5 — both ends of the trip are needed before "Buy a Ride".
            'pickup.required' => 'Pick-up point and destination is required.',
            'destination.required' => 'Pick-up point and destination is required.',
        ]);

        // Display the SERVER-priced fare; the calculator's figure is only a hint.
        $price = (float) $validated['price-regular'];
        try {
            $price = $this->fares->regularFare((float) $validated['distance']);
        } catch (RuntimeException $e) {
            \Illuminate\Support\Facades\Log::warning('Fare pricing unavailable, using submitted figure.', ['area' => 'Index', 'exception' => $e->getMessage()]);
        }

        // Coming back from the gateway's checkout page (browser Back) lands here
        // again with the same trip: say so instead of silently offering a second
        // payment for it.
        $inFlight = Payment::where('paid_by', $userId)
            ->where('status', Payment::STATUS_PENDING)
            ->where('starting_point', $request->pickup)
            ->where('destination', $request->destination)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->latest()
            ->first();

        return view('commuter.payment', [
            'pickup' => $request->pickup,
            'destination' => $request->destination,
            'distance' => $request->distance,
            'price' => $price,
            'balance' => $balance,
            'inFlightPayment' => $inFlight,
        ]);
    }

    public function process(Request $request)
    {
        $userId = Auth::user()->id;
        $wallet = Wallet::where('user_id', $userId)->firstOrFail();

        // E1 - Payment method unavailable / E2 - malformed fare payload
        $validated = $request->validate([
            'pickup' => 'required',
            'destination' => 'required',
            'distance' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'payment-method' => 'required|in:' . implode(',', self::SELF_SERVICE_FARE_METHODS),
            'transaction-id' => 'required',
        ]);

        $transactionId = $validated['transaction-id'];
        $method = $validated['payment-method'];

        // E3 - Already Paid / duplicate submission guard.
        // The transaction id is minted server-side when the checkout page is
        // rendered, so a double-click or refresh replays the same id.
        $existing = Payment::where('paid_by', $userId)->where('transaction_id', $transactionId)->first();

        if ($existing) {
            return $this->replayedPayment($existing);
        }

        // The fare is RE-PRICED server-side from the published rate table; the
        // amount posted by the browser is never trusted.
        try {
            $amount = $this->fares->regularFare((float) $validated['distance']);
        } catch (RuntimeException $e) {
            \Illuminate\Support\Facades\Log::warning('Could not price fare.', ['area' => 'Process', 'exception' => $e->getMessage()]);

            return back()->with('error', 'Fares are unavailable right now. Please try again later.');
        }

        // ── Gateway method (GCash / Maya) ────────────────────────────────
        if ($this->paymongo->isGatewayMethod($method) && $this->paymongo->enabled()) {
            return $this->startGatewayPayment($userId, $validated, $method, $transactionId, $amount);
        }

        // ── Wallet (or gateway method with the gateway disabled) ─────────
        $currentBalance = (float) $wallet->balance;
        $newBalance = $currentBalance;

        if ($method === 'Wallet') {
            $newBalance = $currentBalance - $amount;
        }

        if ($newBalance < 0) {
            return back()->with('error', "You don't have enough balance");
        }

        try {
            $payment = Payment::create([
                'paid_by' => $userId,
                'starting_point' => $validated['pickup'],
                'destination' => $validated['destination'],
                'total_distance' => $validated['distance'],
                'payment_method' => $method,
                'transaction_id' => $transactionId,
                'price' => $amount,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Database error during fare payment.', ['area' => 'Process', 'exception' => $e->getMessage()]);

            return back()->with('error', 'There was a problem trying to process the payment');
        }

        if ($payment) {
            $wallet->update([
                'balance' => $newBalance,
            ]);

            // POST/redirect/GET: the receipt is re-read from the stored payment,
            // so refreshing the page can never re-charge the commuter.
            return redirect()
                ->route('payment.showReceipt', $payment->id)
                ->with('success', 'Payment successful');
        }

        return back()->with('error', 'There was a problem trying to process the payment');
    }

    /**
     * The same transaction id was submitted again (double click, Back button,
     * refresh). Tell the commuter where that payment actually stands instead of
     * charging again or showing a generic error.
     */
    protected function replayedPayment(Payment $payment): \Illuminate\Http\RedirectResponse
    {
        return match ($payment->status) {
            Payment::STATUS_PAID => redirect()
                ->route('payment.showReceipt', $payment->id)
                ->with('warning', 'This fare has already been paid. Here is your receipt.'),
            Payment::STATUS_PENDING => redirect()
                ->route('payment.returned', ['ref' => $payment->transaction_id])
                ->with('warning', 'This payment is already being processed. Please wait for the confirmation.'),
            default => redirect()
                ->route('payment.history')
                ->with('error', 'That payment did not go through and nothing was charged. Start a new payment to try again.'),
        };
    }

    /**
     * Create a PayMongo payment intent + checkout session and redirect the
     * commuter to the hosted checkout page. The payment row is written as
     * `pending` and only settles via the webhook or the return URL.
     */
    protected function startGatewayPayment(int $userId, array $validated, string $method, string $transactionId, float $amount): \Illuminate\Http\RedirectResponse
    {
        try {
            // One call: PayMongo creates the payment intent as part of the
            // checkout session.
            //
            // The return URLs carry OUR transaction id, not a gateway field:
            // PayMongo payment intents expose no reference_id, and the lookup
            // must not depend on a gateway-specific shape.
            $session = $this->paymongo->createCheckoutSession(
                $amount,
                'SmartCommute fare '.$transactionId,
                route('payment.returned', ['ref' => $transactionId]),
                route('payment.cancelled', ['ref' => $transactionId]),
                PayMongoService::channelFor($method),
                ['transaction_id' => $transactionId, 'user_id' => (string) $userId]
            );
        } catch (RuntimeException $e) {
            \Illuminate\Support\Facades\Log::error('PayMongo error.', ['area' => 'Process', 'exception' => $e->getMessage()]);

            return back()->with('error', 'The payment service is unavailable right now. Please try again.');
        }

        if (empty($session['checkout_url']) || empty($session['payment_intent_id'])) {
            return back()->with('error', 'The payment service is unavailable right now. Please try again.');
        }

        try {
            Payment::create([
                'paid_by' => $userId,
                'starting_point' => $validated['pickup'],
                'destination' => $validated['destination'],
                'total_distance' => $validated['distance'],
                'payment_method' => $method,
                'transaction_id' => $transactionId,
                'price' => $amount,
                'status' => 'pending',
                'paymongo_payment_intent_id' => $session['payment_intent_id'],
                'paymongo_checkout_session_id' => $session['id'],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Database error while opening gateway payment.', ['area' => 'Process', 'exception' => $e->getMessage()]);

            return back()->with('error', 'There was a problem starting the payment');
        }

        // The commuter leaves for PayMongo's hosted checkout.
        return redirect()->away($session['checkout_url']);
    }

    /**
     * Gateway success return URL. Also acts as the reconciliation path for a
     * webhook that never arrived.
     */
    public function returned(Request $request)
    {
        Payment::expireStale();
        $payment = Payment::where('paid_by', Auth::id())
            ->where('transaction_id', (string) $request->query('ref'))
            ->first();

        if (! $payment) {
            return redirect()->route('payment.history')->with('error', 'We could not find that payment.');
        }

        if ($payment->status === 'paid') {
            return redirect()->route('payment.showReceipt', $payment->id)->with('success', 'Payment successful');
        }

        if ($payment->status === 'failed' || $payment->status === 'cancelled') {
            return redirect()->route('payment.history')->with('error', 'That payment was not completed.');
        }

        // Still pending at the gateway — ask PayMongo directly.
        //
        // The pending screen reloads every few seconds, so the lookup is
        // throttled to avoid hammering the API on every poll.
        $lastPolled = (int) $request->session()->get('paymongo_polled_at', 0);

        if ($payment->paymongo_payment_intent_id && (time() - $lastPolled) >= 4) {
            $request->session()->put('paymongo_polled_at', time());

            try {
                $intent = $this->paymongo->retrievePaymentIntent($payment->paymongo_payment_intent_id);
            } catch (RuntimeException $e) {
                \Illuminate\Support\Facades\Log::warning('PayMongo lookup failed.', ['area' => 'Returned', 'exception' => $e->getMessage()]);
            }

            if (isset($intent)) {
                if ($this->paymongo->isSettled($intent['status'] ?? null)) {
                    $this->settle($payment);

                    return redirect()->route('payment.showReceipt', $payment->id)
                        ->with('success', 'Payment successful');
                }

                // The gateway gave up on this payment — stop showing a spinner.
                if ($this->paymongo->isFailed($intent['status'] ?? null)) {
                    $payment->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'failure_message' => 'The payment was not completed at the gateway.',
                    ]);

                    return redirect()->route('payment.history')
                        ->with('error', 'That payment could not be completed. Nothing was charged.');
                }
            }
        }

        // Settling (webhook pending) — show an honest "processing" state rather
        // than claiming success.
        return view('commuter.paymentpending', ['payment' => $payment]);
    }

    /**
     * Gateway cancel return URL.
     */
    public function cancelled(Request $request)
    {
        $payment = Payment::where('paid_by', Auth::id())
            ->where('transaction_id', (string) $request->query('ref'))
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $payment->update([
                'status' => 'cancelled',
                'failed_at' => now(),
                'failure_message' => 'Cancelled at checkout.',
            ]);
        }

        return redirect()->route('payment.history')->with('error', 'Payment cancelled. Nothing was charged.');
    }

    /**
     * PayMongo webhook. Signature-verified, unauthenticated (PayMongo signs the
     * call), CSRF-exempt, and idempotent.
     */
    public function webhook(Request $request)
    {
        $rawBody = $request->getContent();

        $signature = $request->header('paymongo-signature');
        $teSignature = $request->header('paymongo-te-signature');
        $timestamp = $request->header('paymongo-timestamp');

        if (! $this->paymongo->verifySignature($rawBody, $teSignature, $signature, $timestamp)) {
            Log::warning('PAYMONGO webhook rejected: bad signature');

            return response('Invalid signature', 401);
        }

        $event = $this->paymongo->parseWebhook($rawBody);

        // Match in order of reliability. Never build this as a single OR-chain:
        // where('col', null) becomes whereNull() and would match an unrelated
        // pending payment.
        $payment = null;

        if (! empty($event['payment_intent_id'])) {
            $payment = Payment::where('paymongo_payment_intent_id', $event['payment_intent_id'])->first();
        }

        if (! $payment && ! empty($event['transaction_id'])) {
            $payment = Payment::where('transaction_id', $event['transaction_id'])->first();
        }

        if (! $payment && ! empty($event['reference_id'])) {
            $payment = Payment::where('paymongo_reference_id', $event['reference_id'])->first();
        }

        if (! $payment) {
            Log::warning('PAYMONGO webhook: no matching payment', $event);

            return response('OK', 200);
        }

        $type = (string) $event['event_type'];

        // PayMongo has used both `payment.paid` and `payment.succeeded` naming;
        // accept either rather than dropping a real settlement on the floor.
        if (str_contains($type, 'paid') || str_contains($type, 'succeeded') || str_contains($type, 'success')) {
            $this->settle($payment);
        } elseif (str_contains($type, 'failed') || str_contains($type, 'declined')) {
            if ($payment->status === 'pending') {
                $payment->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'failure_message' => 'The payment was declined by the gateway.',
                ]);
            }
        } elseif (str_contains($type, 'expired') || str_contains($type, 'cancel')) {
            if ($payment->status === 'pending') {
                $payment->update([
                    'status' => 'cancelled',
                    'failed_at' => now(),
                    'failure_message' => 'The checkout session expired.',
                ]);
            }
        }

        return response('OK', 200);
    }

    /**
     * Mark a payment settled. Idempotent: only a `pending` payment transitions,
     * so a replayed webhook cannot re-apply anything.
     */
    protected function settle(Payment $payment): Payment
    {
        if ($payment->status === 'paid') {
            return $payment;
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => $payment->paid_at ?? now(),
        ]);

        return $payment;
    }

    public function history(Request $request)
    {
        Payment::expireStale();
        $userId = Auth::user()->id;
        $query = Payment::where('paid_by', $userId);
        $balance = $this->walletFor($userId)->balance;

        // Search by transaction ID or destination
        $query->when($request->search, function ($q) use ($request) {
            $term = $request->search;

            return $q->where(function ($sub) use ($term) {
                $sub->where('transaction_id', 'like', "%{$term}%")
                    ->orWhere('destination', 'like', "%{$term}%");
            });
        });

        // Date range filters
        $query->when($request->from_date, function ($q) use ($request) {
            return $q->whereDate('paid_at', '>=', $request->from_date);
        });

        $query->when($request->to_date, function ($q) use ($request) {
            return $q->whereDate('paid_at', '<=', $request->to_date);
        });

        // DB-level sum instead of collecting all records
        // Only settled fares were actually charged.
        $totalSpent = Payment::settled()->where('paid_by', $userId)->sum('price');

        // Pending / failed attempts have no paid_at: sort them by when they were started.
        $recentReceipts = $query->orderByRaw('COALESCE(paid_at, created_at) DESC')->paginate(10)->withQueryString();

        return view('commuter.paymenthistory', [
            'recentReceipts' => $recentReceipts,
            'totalSpent' => $totalSpent,
            'balance' => $balance,
        ]);
    }

    /**
     * One receipt for everyone: a commuter sees their own, an admin may open any
     * payment from the transactions ledger (whatever its status).
     */
    public function showReceipt(string $id)
    {

        $isAdmin = Auth::user()->hasRole('admin');

        // A commuter's receipt is scoped to them: it must never be readable by
        // another user who guesses the id.
        $payment = Payment::with('user')
            ->where('id', $id)
            ->when(! $isAdmin, fn ($q) => $q->where('paid_by', Auth::id()))
            ->firstOrFail();

        // Only a settled payment has a receipt for its owner. A pending one goes
        // back through the gateway reconciliation; a failed or cancelled one has
        // nothing to show. An admin auditing the ledger sees every attempt.
        if (! $payment->paid_at && ! $isAdmin) {
            if ($payment->status === 'pending') {
                return redirect()->route('payment.returned', ['ref' => $payment->transaction_id]);
            }

            return redirect()->route('payment.history')->with('error', 'That payment was not completed.');
        }

        return view('commuter.viewreceipt', [
            'pickup' => $payment->starting_point,
            'destination' => $payment->destination,
            'distance' => $payment->total_distance,
            'paymentMethod' => $payment->payment_method,
            'transactionId' => $payment->transaction_id,
            'price' => $payment->price,
            'paidAt' => ($payment->paid_at ?? $payment->created_at)->format('M d, Y h:i A'),
            'status' => $payment->status,
            'commuterEmail' => $isAdmin ? $payment->user?->email : null,
            'backUrl' => $isAdmin ? route('faretransactions') : route('payment.history'),
            'backLabel' => $isAdmin ? 'Back to Transactions' : 'Back to History',
        ]);
    }

    public function topup()
    {
        $user = Auth::user();
        $userId = $user->id;
        $balance = $this->walletFor($userId)->balance;

        return view('commuter.topup', [
            'balance' => $balance,
        ]);
    }

    /**
     * The signed-in user's wallet, created on the fly if the registration-time
     * row is missing — the checkout, history and top-up pages must not 500.
     */
    private function walletFor(int $userId): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $userId]);
    }

    /**
     * Payment methods a commuter may self-select. Cash / "Admin Settlement" is
     * NOT self-service: it must be recorded by an admin, never by the commuter.
     */
    public const SELF_SERVICE_TOPUP_METHODS = ['gcash', 'maya'];

    public function topupProcess(Request $request)
    {
        $userId = Auth::id();
        $wallet = Wallet::where('user_id', $userId)->firstOrFail();
        $balance = $wallet->balance;

        $request->validate([
            // E5 - Invalid Custom Amount: numeric, at least 10, at most 100000
            'amount' => ['required', 'numeric', 'min:10', 'max:100000'],
            // E1 - Payment method unavailable: only self-service methods allowed
            'payment-method' => ['required', 'in:' . implode(',', self::SELF_SERVICE_TOPUP_METHODS)],
        ]);

        $amount = round((float) $request->amount, 2);
        $currentBalance = (float) $balance;
        $newBalance = round($currentBalance + $amount, 2);

        // E3 - Already Paid / duplicate submission guard (idempotency window)
        $duplicate = TopupHistory::where('user_id', $userId)
            ->where('amount_added', $amount)
            ->where('payment_method', $request->{'payment-method'})
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($duplicate) {
            return back()->with('error', 'This top-up was already processed. Please wait a moment before retrying.');
        }

        try {
            $updated = $wallet->update([
                'balance' => $newBalance,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Database error during topup.', ['area' => 'Topupprocess', 'exception' => $e->getMessage()]);

            return back()->with('error', 'Top-up failed. Please try again later.');
        }

        if ($updated) {

            TopupHistory::create([
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'amount_added' => $amount,
                'payment_method' => $request->{'payment-method'},
                'status' => TopupHistory::STATUS_COMPLETED,
            ]);

            return back()->with('success', 'Successfully topped up!');
        }

        return back()->with('error', 'Topup failed.');
    }

    public function topupHistory(Request $request)
    {
        $userId = Auth::user()->id;
        $query = TopupHistory::where('user_id', $userId)->with('user', 'wallet');

        // Search by transaction ID
        $query->when($request->search, function ($q) use ($request) {
            return $q->where('id', 'like', "%{$request->search}%");
        });

        // Filter by payment method
        $query->when($request->method, function ($q) use ($request) {
            return $q->where('payment_method', $request->method);
        });

        // Date range filters
        $query->when($request->from_date, function ($q) use ($request) {
            return $q->whereDate('created_at', '>=', $request->from_date);
        });

        $query->when($request->to_date, function ($q) use ($request) {
            return $q->whereDate('created_at', '<=', $request->to_date);
        });

        $transactions = $query->latest()->paginate(10)->withQueryString();

        return view('commuter.topuphistory', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * Admin ledger of every fare payment attempt - pending, paid, failed and
     * cancelled - narrowed by the filters below. The money figures count settled
     * payments only.
     */
    public function showTransactions(Request $request)
    {
        Payment::expireStale();

        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:'.implode(',', Payment::STATUSES),
            'method' => 'nullable|in:'.implode(',', self::SELF_SERVICE_FARE_METHODS),
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ]);

        $query = Payment::with('user')
            ->when($filters['search'] ?? null, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('transaction_id', 'like', "%{$term}%")
                        ->orWhere('destination', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['method'] ?? null, fn ($q, $method) => $q->where('payment_method', $method))
            ->when($filters['from_date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when(isset($filters['min_amount']), fn ($q) => $q->where('price', '>=', $filters['min_amount']))
            ->when(isset($filters['max_amount']), fn ($q) => $q->where('price', '<=', $filters['max_amount']));

        return view('admin.fares.transactions', [
            'allTransactions' => $query->latest()->paginate(10)->withQueryString(),
            'statuses' => Payment::STATUSES,
            'methods' => self::SELF_SERVICE_FARE_METHODS,
            // The list shows every attempt, but the money figures count settled ones only.
            'totalRevenue' => Payment::settled()->sum('price'),
            'settledCount' => Payment::settled()->count(),
            'activeUsersCount' => Payment::settled()->distinct()->count('paid_by'),
            'statusCounts' => Payment::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /**
     * Admin ledger of every wallet top-up, whatever its payment status. The
     * "funds added" figure counts completed top-ups only.
     */
    public function showTopupsAdmin(Request $request)
    {

        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:'.implode(',', TopupHistory::STATUSES),
            'method' => 'nullable|string|max:50',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ]);

        $query = TopupHistory::with('user')
            ->when($filters['search'] ?? null, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('id', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['method'] ?? null, fn ($q, $method) => $q->where('payment_method', $method))
            ->when($filters['from_date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when(isset($filters['min_amount']), fn ($q) => $q->where('amount_added', '>=', $filters['min_amount']))
            ->when(isset($filters['max_amount']), fn ($q) => $q->where('amount_added', '<=', $filters['max_amount']));

        return view('admin.topups', [
            'totalFundsAdded' => TopupHistory::completed()->sum('amount_added'),
            'completedCount' => TopupHistory::completed()->count(),
            'transactions' => $query->latest()->paginate(10)->withQueryString(),
            'statuses' => TopupHistory::STATUSES,
            'methods' => TopupHistory::distinct()->orderBy('payment_method')->pluck('payment_method'),
            'statusCounts' => TopupHistory::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
