<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FleetAnalyticsService;
use Illuminate\Http\Request;

/**
 * Fleet and driver analytics: the numbers the system works out by itself from time-keeping, the GPS
 * trail, payments, violations and maintenance logs. Read-only; the same figures go out in the scheduled report.
 */
class AnalyticsController extends Controller
{
    public const PERIODS = [7, 30, 90];

    public function index(Request $request, FleetAnalyticsService $analytics)
    {
        $days = (int) $request->query('days', 30);
        $days = in_array($days, self::PERIODS, true) ? $days : 30;

        $from = today()->subDays($days - 1);
        $to = today();

        $drivers = $analytics->driverStats($from, $to);
        $vehicles = $analytics->vehicleStats($from, $to);

        return view('admin.analytics', [
            'days' => $days,
            'periods' => self::PERIODS,
            'from' => $from,
            'to' => $to,
            'drivers' => $drivers,
            'vehicles' => $vehicles,
            'totals' => $analytics->totals($drivers, $vehicles),
            'trend' => $analytics->overtimeTrend(8),
            'repeatViolators' => $analytics->repeatViolators(),
            'abandoned' => $analytics->abandonedScans(),
        ]);
    }
}
