<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\TopupHistory;

class AdminController extends Controller
{
    public function dashboard()
    {
        activity()->event('Dashboard')->log('Action performed: dashboard');

        $totalRevenue = Payment::settled()->sum('price');
        $totalFundsAdded = TopupHistory::completed()->sum('amount_added');
        $activeUsersCount = Payment::settled()->distinct()->count('paid_by');
        $recentFares = Payment::settled()->with('user')->latest()->take(5)->get();
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
