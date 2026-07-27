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
        // dd($request);
        // dd(Auth::user()->id);
        $userId = Auth::user()->id;
        $wallet = Wallet::where('user_id', $userId)->first();
        $balance = $wallet->balance;
        $currentBalance = (float) $balance;

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

        // $request->validate([
        //     'from_date' => 'required',
        //     'to_date' => 'required'
        // ]);

        $query->when($request->from_date, function ($q) use ($request) {
            return $q->whereDate('paid_at', '>=', $request->from_date);
        });

        $query->when($request->to_date, function ($q) use ($request) {
            return $q->whereDate('paid_at', '<=', $request->to_date);
        });

        $totalSpent = Payment::where('paid_by', $userId)->get()->sum('price');
        $recentReceipts = $query->orderBy('paid_at', 'desc')->paginate(4);

        return view('commuter.paymenthistory', [
            'recentReceipts' => $recentReceipts,
            'totalSpent' => $totalSpent,
            'balance' => $balance,
        ]);
    }

    public function showReceipt(string $id)
    {
        $payment = Payment::where('id', $id)->first();

        // dd($payment);
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

    public function topupHistory()
    {
        $user = Auth::user();
        $userId = $user->id;
        $history = TopupHistory::where('user_id', $userId)->with('user', 'wallet')->latest()->paginate(10);

        // dd($history[0]->wallet);

        return view('commuter.topuphistory', [
            'transactions' => $history,
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

        return view('admin.transactions', [
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
