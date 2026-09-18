<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Payment;
use App\Models\PreventiveMaintenance;
use App\Models\TopupHistory;
use App\Models\VehicleMaintenanceLog;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function generate(Request $request)
    {
        $type = $request->get('type', 'financial');
        $start = $request->get('start_date');
        $end = $request->get('end_date');

        // Default return structure
        $data = [];

        if ($type === 'financial') {
            // 1. Calculate Stats
            $data['financialStats'] = [
                'total_revenue' => Payment::whereBetween('paid_at', [$start, $end])->sum('price'),
                'total_topups' => TopupHistory::whereBetween('created_at', [$start, $end])->sum('amount_added'),
                'tx_count' => Payment::whereBetween('paid_at', [$start, $end])->count(),
            ];

            // 2. Prepare Chart Data (Dynamic Logic)
            // Group payments by date and sum the price
            $revenueByDate = Payment::whereBetween('paid_at', [$start, $end])
                ->selectRaw('DATE(paid_at) as date, SUM(price) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date'); // Key by date so we can fill gaps

            // Create a date range array to fill in missing days (where revenue was 0)
            $period = new \DatePeriod(
                new \DateTime($start),
                new \DateInterval('P1D'),
                new \DateTime($end . ' +1 day') // Include end date
            );

            $labels = [];
            $revenues = [];

            foreach ($period as $day) {
                $dateStr = $day->format('Y-m-d');
                $labels[] = $day->format('M d'); // Format: "Oct 25"

                // If there is revenue for this day, use it. Otherwise, use 0.
                $revenues[] = isset($revenueByDate[$dateStr]) ? (float) $revenueByDate[$dateStr]->total : 0;
            }

            $data['chartLabels'] = $labels;
            $data['chartRevenue'] = $revenues;
        }

        if ($type === 'driver') {
            // 1. Basic Stats
            $data['driverStats'] = [
                'total' => Driver::count(),
                'active' => Driver::where('status', 'active')->count(),
            ];

            // 2. Top Performers Logic (The Fix)
            // We use withSum() to calculate the sum of 'hours_worked' from the 'timeKeeping' relationship
            // and store it in a temporary attribute called 'total_hours'.
            $topPerformers = Driver::withSum('timeKeeping as total_hours', 'hours_worked')
                ->orderByDesc('total_hours') // Order by that calculated sum
                ->take(5) // Get top 5
                ->get()
                ->map(function ($driver) {
                    return [
                        'name' => $driver->name,
                        'status' => $driver->status ?? 'Inactive',
                        // Cast to float because your migration says hours_worked is a string
                        'hours_worked' => (float) ($driver->total_hours ?? 0),
                    ];
                })->toArray();

            $data['topPerformers'] = $topPerformers;

            // 3. Status Distribution
            // Count drivers grouped by their status column
            $statusCounts = Driver::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Ensure we pass the data in the order expected by the Chart, filling missing keys with 0
            $data['driverStatusLabels'] = ['Active', 'Inactive', 'Suspended'];
            $data['driverStatusData'] = [
                ($statusCounts['Active'] ?? 0),
                ($statusCounts['Inactive'] ?? 0),
                ($statusCounts['Suspended'] ?? 0),
            ];
        }

        if ($type === 'maintenance') {
            $now = now();

            // 1. Pending: Scheduled for the future
            $data['maintStats']['pending'] = PreventiveMaintenance::where('last_service_date', '>', $now)->count();

            // 2. Overdue: Scheduled date has passed
            $data['maintStats']['overdue'] = PreventiveMaintenance::where('last_service_date', '<', $now)->count();

            // 3. Completed: Count actual work logs created within the selected date range
            // (Since 'PreventiveMaintenance' is the schedule, 'VehicleMaintenanceLog' is the history)
            $data['maintStats']['completed'] = VehicleMaintenanceLog::whereBetween('created_at', [$start, $end])->count();

            // 4. Upcoming Schedule (For the table)
            // Get scheduled items where the due date is >= now (Pending) or < now (Overdue)
            $upcomingSchedule = PreventiveMaintenance::with(['vehicle', 'maintenanceTask'])
                ->orderBy('last_service_date', 'asc')
                ->get()
                ->map(function ($item) {
                    $dueDate = $item->last_service_date;
                    $isOverdue = $dueDate && $dueDate->isPast();

                    return [
                        'vehicle_plate' => $item->vehicle?->plate_number ?? 'N/A',
                        'task_name' => $item->maintenanceTask?->tasks_performed ?? 'Unknown Task',
                        'due_date' => $dueDate ? $dueDate->format('Y-m-d') : 'N/A',
                        'is_overdue' => $isOverdue,
                    ];
                })->toArray();

            $data['upcomingSchedule'] = $upcomingSchedule;
        }

        return view('admin.reports', $data);
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'financial');
        $start = $request->get('start_date');
        $end = $request->get('end_date');

        $filename = "smartcommute_{$type}_report_" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($type, $start, $end) {
            $file = fopen('php://output', 'w');

            if ($type === 'financial') {
                // 1. FINANCIAL EXPORT (Raw Transactions)
                fputcsv($file, ['Date', 'Type', 'Reference ID', 'Amount', 'User']);

                // Get Fares
                $payments = Payment::whereBetween('paid_at', [$start, $end])
                    ->with('user:id,email')
                    ->get();

                foreach ($payments as $p) {
                    fputcsv($file, [
                        $p->paid_at->format('Y-m-d H:i'),
                        'Fare Payment',
                        $p->id,
                        $p->price,
                        $p->user->email ?? 'N/A',
                    ]);
                }

                // Get Topups
                $topups = TopupHistory::whereBetween('created_at', [$start, $end])
                    ->with('user:id,email')
                    ->get();

                foreach ($topups as $t) {
                    fputcsv($file, [
                        $t->created_at->format('Y-m-d H:i'),
                        'Wallet Top-up',
                        $t->id,
                        $t->amount_added,
                        $t->user->email ?? 'N/A',
                    ]);
                }
            } elseif ($type === 'driver') {
                // 2. DRIVER EXPORT
                fputcsv($file, ['Driver Name', 'Status', 'Total Hours Worked']);

                $drivers = Driver::withSum('timeKeeping as total_hours', 'hours_worked')
                    ->orderByDesc('total_hours')
                    ->get();

                foreach ($drivers as $driver) {
                    fputcsv($file, [
                        $driver->name,
                        $driver->status ?? 'Inactive',
                        (float) ($driver->total_hours ?? 0),
                    ]);
                }
            } elseif ($type === 'maintenance') {
                // 3. MAINTENANCE EXPORT
                fputcsv($file, ['Vehicle Plate', 'Task', 'Due Date', 'Status']);

                $tasks = PreventiveMaintenance::with(['vehicle', 'maintenanceTask'])
                    ->orderBy('last_service_date', 'asc')
                    ->get();

                foreach ($tasks as $task) {
                    $dueDate = $task->last_service_date;
                    $isOverdue = $dueDate && $dueDate->isPast();

                    fputcsv($file, [
                        $task->vehicle?->plate_number ?? 'N/A',
                        $task->maintenanceTask?->tasks_performed ?? 'Unknown',
                        $dueDate ? $dueDate->format('Y-m-d') : 'N/A',
                        $isOverdue ? 'Overdue' : 'Scheduled',
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
