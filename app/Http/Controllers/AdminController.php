<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\TopupHistory;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalRevenue = Payment::sum('price');
        $totalFundsAdded = TopupHistory::sum('amount_added');
        $activeUsersCount = Payment::distinct('paid_by')->count();
        $recentFares = Payment::with('user')->latest()->take(5)->get();
        $recentTopups = TopupHistory::with('user')->latest()->take(5)->get();

        return view('admin.dashboard', [
            'totalRevenue' => $totalRevenue,
            'totalFundsAdded' => $totalFundsAdded,
            'activeUsersCount' => $activeUsersCount, // Or your preferred logic
            'recentFares' => $recentFares,
            'recentTopups' => $recentTopups,
        ]);
    }
}
