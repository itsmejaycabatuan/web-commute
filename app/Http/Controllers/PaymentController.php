<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\TopupHistory;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        // dd($request);
        $user = Auth::user();
        $userId = $user->id;
        $balance = Wallet::where('user_id', $userId)->first()->balance;

        $validated = $request->validate([
            'pickup' => 'required',
            'destination' => 'required',
            'distance' => 'required',
            'price-regular' => 'required',
        ]);

        if ($validated) {
            return view('commuter.payment', [
                'pickup' => $request->pickup,
                'destination' => $request->destination,
                'distance' => $request->distance,
                'price' => $request->{'price-regular'},
                'balance' => $balance,
            ]);
        }

        return back()->with('error', 'Pick-up point and destination is required');
    }

    public function process(Request $request)
    {
        $userId = Auth::user()->id;
        $wallet = Wallet::where('user_id', $userId)->first();
        $balance = $wallet->balance;
        $currentBalance = (float) $balance;
        $newBalance = $currentBalance;

        if ($request->{'payment-method'} === 'Wallet') {
            $price = (float) $request->amount;
            $newBalance = $currentBalance - $price;
        }

        if ($newBalance < 0) {
            return back()->with('error', "You don't have enough balance");
        }

        $payment = Payment::create([
            'paid_by' => $userId,
            'starting_point' => $request->pickup,
            'destination' => $request->destination,
            'total_distance' => $request->distance,
            'payment_method' => $request->{'payment-method'},
            'transaction_id' => $request->{'transaction-id'},
            'price' => $request->amount,
            'paid_at' => now(), // Add this if your table has this column
        ]);

        if ($payment) {
            $wallet->update([
                'balance' => $newBalance,
            ]);

            return view('commuter.receipt', [
                'pickup' => $request->pickup,
                'destination' => $request->destination,
                'distance' => $request->distance,
                'paymentMethod' => $request->{'payment-method'},
                'transactionId' => $request->{'transaction-id'},
                'price' => $request->amount,
                'paidAt' => $payment->paid_at->format('M d, Y h:i A'), // ✅ Added this
            ])->with('success', 'Payment successful');
        }

        return back()->with('error', 'There was a problem trying to process the payment');
    }

    public function history(Request $request)
    {
        $userId = Auth::user()->id;
        $query = Payment::where('paid_by', $userId);
        $wallet = Wallet::where('user_id', $userId)->first();
        $balance = $wallet->balance;

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
        $payment = Payment::where('id', $id)->first();

        return view('commuter.viewreceipt', [
            'pickup' => $payment->starting_point,
            'destination' => $payment->destination,
            'distance' => $payment->total_distance,
            'paymentMethod' => $payment->payment_method,
            'transactionId' => $payment->transaction_id,
            'price' => $payment->price,
            'paidAt' => $payment->paid_at,
        ]);
    }

    public function topup()
    {
        $user = Auth::user();
        $userId = $user->id;
        $wallet = Wallet::where('user_id', $userId)->first();
        $balance = $wallet->balance;

        return view('commuter.topup', [
            'balance' => $balance,
        ]);
    }

    public function topupProcess(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;
        $wallet = Wallet::where('user_id', $userId)->first();
        $balance = $wallet->balance;

        // dd($request);

        $request->validate([
            'amount' => 'required',
            'payment-method' => 'required',
        ]);

        $amount = (float) $request->amount;
        $currentBalance = (float) $balance;
        $newBalance = $currentBalance + $amount;

        if ($wallet->update([
            'balance' => $newBalance,
        ])) {

            TopupHistory::create([
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'amount_added' => $request->amount,
                'payment_method' => $request->{'payment-method'},
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

    public function showTransactions(Request $request)
    {
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
        $payment = Payment::with('user')->where('id', $id)->first();

        // dd($payment);
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

        $query = TopupHistory::with('user');
        $total = TopupHistory::sum('amount_added');

        if ($request->filled('search')) {
            $query->whereHas('usadmin/er', function ($q) use ($request) {
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
