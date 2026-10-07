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
        activity()->event('Index')->log('Action performed: index');
        // dd($request);
        $user = Auth::user();
        $userId = $user->id;
        $balance = $this->walletFor($userId)->balance;

        $validated = $request->validate([
            'pickup' => 'required',
            'destination' => 'required',
            'distance' => 'required|numeric|min:0',
            'price-regular' => 'required',
        ]);

        // Display the SERVER-priced fare; the calculator's figure is only a hint.
        $price = (float) $validated['price-regular'];
        try {
            $price = $this->fares->regularFare((float) $validated['distance']);
        } catch (RuntimeException $e) {
            activity()->event('Index')->log('Fare pricing unavailable, using submitted figure.');
        }

        return view('commuter.payment', [
            'pickup' => $request->pickup,
            'destination' => $request->destination,
            'distance' => $request->distance,
            'price' => $price,
            'balance' => $balance,
        ]);
    }

    public function process(Request $request)
    {
        activity()->event('Process')->log('Action performed: process');
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
        if (Payment::where('paid_by', $userId)->where('transaction_id', $transactionId)->exists()) {
            return back()->with('error', 'This fare has already been paid. Please wait a moment before retrying.');
        }

        // The fare is RE-PRICED server-side from the published rate table; the
        // amount posted by the browser is never trusted.
        try {
            $amount = $this->fares->regularFare((float) $validated['distance']);
        } catch (RuntimeException $e) {
            activity()->event('Process')->log('Could not price fare: '.$e->getMessage());

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
            activity()->event('Process')->log('Database error during fare payment.');

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
            activity()->event('Process')->log('PayMongo error: '.$e->getMessage());

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
            activity()->event('Process')->log('Database error while opening gateway payment.');

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
                activity()->event('Returned')->log('PayMongo lookup failed: '.$e->getMessage());
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
        activity()->event('History')->log('Action performed: history');
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
        $totalSpent = Payment::where('paid_by', $userId)->sum('price');

        $recentReceipts = $query->orderBy('paid_at', 'desc')->paginate(4)->withQueryString();

        return view('commuter.paymenthistory', [
            'recentReceipts' => $recentReceipts,
            'totalSpent' => $totalSpent,
            'balance' => $balance,
        ]);
    }

    public function showReceipt(string $id)
    {
        activity()->event('Showreceipt')->log('Action performed: showReceipt');
        // Scoped to the authenticated commuter: a receipt must never be
        // readable by another user who guesses/guesses the id.
        $payment = Payment::where('id', $id)
            ->where('paid_by', Auth::id())
            ->firstOrFail();

        return view('commuter.viewreceipt', [
            'pickup' => $payment->starting_point,
            'destination' => $payment->destination,
            'distance' => $payment->total_distance,
            'paymentMethod' => $payment->payment_method,
            'transactionId' => $payment->transaction_id,
            'price' => $payment->price,
            'paidAt' => $payment->paid_at->format('M d, Y h:i A'),
        ]);
    }

    public function topup()
    {
        activity()->event('Topup')->log('Action performed: topup');
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
        activity()->event('Topupprocess')->log('Action performed: topupProcess');
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
            activity()->event('Topupprocess')->log('Database error during topup.');

            return back()->with('error', 'Top-up failed. Please try again later.');
        }

        if ($updated) {

            TopupHistory::create([
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'amount_added' => $amount,
                'payment_method' => $request->{'payment-method'},
            ]);

            return back()->with('success', 'Successfully topped up!');
        }

        return back()->with('error', 'Topup failed.');
    }

    public function topupHistory(Request $request)
    {
        activity()->event('Topuphistory')->log('Action performed: topupHistory');
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

    public function showTransactions(Request $request)
    {
        activity()->event('Showtransactions')->log('Action performed: showTransactions');
        $query = Payment::with('user'); // Ensure the relationship is defined in Transaction model

        // dd($query->latest()->paginate(15));

        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', "%{$request->search}%");
            })->orWhere('transaction_id', 'like', "%{$request->search}%");
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return view('admin.fares.transactions', [
            'allTransactions' => $query->latest()->paginate(5),
            'totalRevenue' => Payment::sum('price'),
            'activeUsersCount' => Payment::distinct('paid_by')->count(),
        ]);
    }

    public function showReceiptAdmin(string $id)
    {
        activity()->event('Showreceiptadmin')->log('Action performed: showReceiptAdmin');
        // Laravel automatically decodes %23 back to #, so $id will be #SC-...
        // We can safely query the database directly
        $payment = Payment::with('user')->where('transaction_id', $id)->firstOrFail();

        return view('admin.commuters.receipt', [
            'pickup' => $payment->starting_point,
            'destination' => $payment->destination,
            'distance' => $payment->total_distance,
            'paymentMethod' => $payment->payment_method,
            'transactionId' => $payment->transaction_id,
            'price' => $payment->price,
            'paidAt' => $payment->paid_at,
            'user' => $payment->user,
        ]);
    }

    public function showTopupsAdmin(Request $request)
    {
        activity()->event('Showtopupsadmin')->log('Action performed: showTopupsAdmin');

        $query = TopupHistory::with('user');
        $total = TopupHistory::sum('amount_added');

        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', "%{$request->search}%");
            })->orWhere('id', 'like', "%{$request->search}%");
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return view('admin.topups', [
            'totalFundsAdded' => $total,
            'transactions' => $query->latest()->paginate(5),
        ]);
    }
}
