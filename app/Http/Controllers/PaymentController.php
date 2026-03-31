<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index (Request $request) {
        // dd($request);

        $validated = $request->validate([
            'pickup' => 'required',
            'destination' => 'required',
            'distance' => 'required',
            'price-regular' => 'required'
        ]);

        if($validated) {
            return view('commuter.payment', [
                'pickup' => $request->pickup,
                'destination' => $request->destination,
                'distance' => $request->distance,
                'price' => $request->{'price-regular'}
            ]);
        }
        return back()->with('error', 'Pick-up point and destination is required');
    }

    public function process(Request $request) {
        // dd($request);
        // dd(Auth::user()->id);
        $userId = Auth::user()->id;

        $payment = Payment::create([
            'paid_by' => $userId,
            'starting_point' => $request->pickup,
            'destination' => $request->destination,
            'total_distance' => $request->distance,
            'payment_method' => $request->{'payment-method'},
            'transaction_id' => $request->{'transaction-id'},
            'price' => $request->amount
        ]);

        if($payment) {
            return view('commuter.receipt', [
                'pickup' => $request->pickup,
                'destination' => $request->destination,
                'distance' => $request->distance,
                'paymentMethod' => $request->{'payment-method'},
                'transactionId' => $request->{'transaction-id'},
                'price' => $request->amount
            ])->with('success', 'Payment successful');
        }
        return back()->with('error', 'There was a problem trying to process the payment');
    }

    public function history(Request $request) {
        $userId = Auth::user()->id;
        $query = Payment::where('paid_by', $userId);

        // $request->validate([
        //     'from_date' => 'required',
        //     'to_date' => 'required'
        // ]);

        $query->when($request->from_date, function($q) use ($request) {
            return $q->whereDate('paid_at', '>=', $request->from_date);
        });

        $query->when($request->to_date, function($q) use ($request) {
            return $q->whereDate('paid_at', '<=', $request->to_date);
        });

        $totalSpent = Payment::where('paid_by', $userId)->get()->sum('price');
        $recentReceipts = $query->orderBy('paid_at')->paginate(4);

        return view('commuter.paymenthistory', [
            'recentReceipts' => $recentReceipts,
            'totalSpent' => $totalSpent
        ]);
    }

    public function showReceipt(string $id) {
        $payment = Payment::where('id', $id)->first();
        // dd($payment);
        return view('commuter.viewreceipt', [
            'pickup' => $payment->starting_point,
            'destination' => $payment->destination,
            'distance' => $payment->total_distance,
            'paymentMethod' => $payment->payment_method,
            'transactionId' => $payment->transaction_id,
            'price' => $payment->price,
            'paidAt' => $payment->paid_at
        ]);
    }
}
