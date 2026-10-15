<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Payment;
use App\Models\PreventiveMaintenance;
use App\Models\TopupHistory;
use App\Models\User;
use App\Models\VehicleMaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ReportController extends Controller
{
    /**
     * Report types the admin may generate.
     */
    private const REPORT_TYPES = ['financial', 'driver', 'maintenance', 'commuter'];

    public function generate(Request $request)
    {
        $type = $request->get('type', 'financial');
        $start = $request->get('start_date');
        $end = $request->get('end_date');

        try {
            if (! in_array($type, self::REPORT_TYPES, true)) {
                $type = 'financial';
            }

            $this->assertValidRange($start, $end);

            $data = $this->buildReport($type, $start, $end);

            // E3 — No Data Found: the report still renders, but says plainly that
            // the selected type and date range hold nothing.
            $data['reportType'] = $type;
            $data['reportEmpty'] = $this->reportIsEmpty($type, $start, $end);

            // E4 — Data Sync Delay
            $data['syncLagMinutes'] = $this->syncLagMinutes();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            // E2 — Data Aggregation Timeout (and any other aggregation failure):
            // the run is cancelled and the admin is told to narrow the range.
            report($e);

            return back()->with(
                'error',
                'The database is taking too long to generate this report. Please try a shorter date range.'
            );
        }

        return view('admin.reports', $data);
    }

    /**
     * Guard for the aggregation window before any query is run.
     */
    private function assertValidRange(?string $start, ?string $end): void
    {
        if (! $start || ! $end) {
            return;
        }

        try {
            $from = \Carbon\Carbon::parse($start);
            $to = \Carbon\Carbon::parse($end);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'start_date' => 'The selected date range is not valid.',
            ]);
        }

        if ($to->lessThan($from)) {
            throw ValidationException::withMessages([
                'start_date' => 'The start date must be before the end date.',
            ]);
        }
    }

    private function buildReport(string $type, ?string $start, ?string $end): array
    {
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

        if ($type === 'commuter') {
            [$from, $to] = $this->commuterWindow($start, $end);

            $commuters = $this->commuterQuery($from, $to)->get();

            // Same precedence as the Commuters page: suspended, then verified,
            // otherwise still waiting on email verification.
            $suspended = $commuters->where('is_suspended', true)->count();
            $verified = $commuters->where('is_suspended', false)->whereNotNull('email_verified_at')->count();
            $unverified = $commuters->count() - $suspended - $verified;

            $data['commuterStats'] = [
                'total' => $commuters->count(),
                'new' => $commuters->filter(fn ($c) => $c->created_at->between($from, $to))->count(),
                'riders' => $commuters->where('trips', '>', 0)->count(),
                'suspended' => $suspended,
                'wallet_total' => (float) $commuters->sum(fn ($c) => $c->wallet?->balance ?? 0),
            ];

            $data['commuterStatusLabels'] = ['Verified', 'Unverified', 'Suspended'];
            $data['commuterStatusData'] = [$verified, $unverified, $suspended];

            // Sign-ups per day, gaps filled with 0 so the chart axis is continuous.
            $signups = $commuters
                ->filter(fn ($c) => $c->created_at->between($from, $to))
                ->countBy(fn ($c) => $c->created_at->format('Y-m-d'));

            $labels = [];
            $counts = [];
            foreach (new \DatePeriod($from->copy()->startOfDay(), new \DateInterval('P1D'), $to->copy()->addDay()->startOfDay()) as $day) {
                $labels[] = $day->format('M d');
                $counts[] = (int) ($signups[$day->format('Y-m-d')] ?? 0);
            }
            $data['commuterChartLabels'] = $labels;
            $data['commuterChartData'] = $counts;

            $data['topCommuters'] = $commuters
                ->where('trips', '>', 0)
                ->sortByDesc('spend')
                ->take(5)
                ->map(fn ($c) => [
                    'email' => $c->email,
                    'trips' => (int) $c->trips,
                    'spend' => (float) $c->spend,
                    'topups' => (float) $c->topups,
                ])
                ->values()
                ->toArray();
        }

        return $data;
    }

    /**
     * The report window as whole days, defaulting to the last 7 days (the same
     * default the date pickers show).
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    private function commuterWindow(?string $start, ?string $end): array
    {
        $from = $start ? \Carbon\Carbon::parse($start) : now()->subDays(7);
        $to = $end ? \Carbon\Carbon::parse($end) : now();

        return [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * Every commuter with their settled fares, spend and top-ups inside the window.
     */
    private function commuterQuery(\Carbon\Carbon $from, \Carbon\Carbon $to)
    {
        $paid = fn ($q) => $q->where('status', Payment::STATUS_PAID)->whereBetween('paid_at', [$from, $to]);
        $topups = fn ($q) => $q->whereBetween('created_at', [$from, $to]);

        return User::role('commuter')
            ->with('wallet')
            ->withCount(['payment as trips' => $paid])
            ->withSum(['payment as spend' => $paid], 'price')
            ->withSum(['topupHistories as topups' => $topups], 'amount_added')
            ->orderBy('email');
    }

    /**
     * E3 — No Data Found: true when the report holds nothing worth showing for
     * the selected type and date range.
     */
    private function reportIsEmpty(string $type, ?string $start, ?string $end): bool
    {
        return match ($type) {
            'financial' => Payment::whereBetween('paid_at', [$start, $end])->count() === 0
                && TopupHistory::whereBetween('created_at', [$start, $end])->count() === 0,
            'driver' => Driver::count() === 0,
            'maintenance' => PreventiveMaintenance::count() === 0
                && VehicleMaintenanceLog::whereBetween('created_at', [$start, $end])->count() === 0,
            'commuter' => User::role('commuter')->count() === 0,
            default => true,
        };
    }

    /**
     * E4 — Data Sync Delay: how far behind the newest ledger row is, in minutes.
     * A large value means the analytics are working off stale data.
     */
    private function syncLagMinutes(): ?int
    {
        $newest = Payment::max('paid_at');

        if (! $newest) {
            return null;
        }

        return (int) \Carbon\Carbon::parse($newest)->diffInMinutes(now());
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
            } elseif ($type === 'commuter') {
                // 4. COMMUTER EXPORT
                fputcsv($file, ['Email', 'Status', 'Registered', 'Wallet Balance', 'Fares Paid', 'Total Spend', 'Top-ups']);

                [$from, $to] = $this->commuterWindow($start, $end);

                foreach ($this->commuterQuery($from, $to)->get() as $c) {
                    fputcsv($file, [
                        $c->email,
                        $c->is_suspended ? 'Suspended' : ($c->email_verified_at ? 'Verified' : 'Unverified'),
                        $c->created_at->format('Y-m-d'),
                        $c->wallet?->balance ?? 0,
                        (int) $c->trips,
                        (float) $c->spend,
                        (float) $c->topups,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
