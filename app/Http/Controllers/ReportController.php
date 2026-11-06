<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use App\Models\Driver;
use App\Models\Payment;
use App\Models\PreventiveMaintenance;
use App\Models\TopupHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ReportController extends Controller
{
    /**
     * Report types the admin may generate.
     */
    private const REPORT_TYPES = ['financial', 'driver', 'maintenance'];

    public function generate(Request $request)
    {
        $type = $request->get('type', 'financial');
        [$start, $end] = $this->dates($request);

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
     * The report window as the page shows it: the dates asked for, else the last 7 days up to today. The page
     * fills its date boxes with those same defaults, so opening it must report them instead of an empty window.
     *
     * @return array{0: string, 1: string} Y-m-d start and end
     */
    private function dates(Request $request): array
    {
        return [
            $request->get('start_date') ?: now()->subDays(7)->toDateString(),
            $request->get('end_date') ?: now()->toDateString(),
        ];
    }

    /**
     * The window as timestamps. A bare end date means midnight at the START of that day, so querying with it
     * dropped every payment made on the last day (today's, with the default range): the end runs to 23:59:59.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function window(?string $start, ?string $end): array
    {
        return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()];
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

        [$from, $to] = $this->window($start, $end);

        if ($type === 'financial') {
            // 1. Calculate Stats
            $data['financialStats'] = [
                'total_revenue' => Payment::whereBetween('paid_at', [$from, $to])->sum('price'),
                'total_topups' => TopupHistory::completed()->whereBetween('created_at', [$from, $to])->sum('amount_added'),
                'tx_count' => Payment::whereBetween('paid_at', [$from, $to])->count(),
            ];

            // 2. Prepare Chart Data (Dynamic Logic)
            // Group payments by date and sum the price
            $revenueByDate = Payment::whereBetween('paid_at', [$from, $to])
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
                        'status' => strtolower((string) ($driver->status ?: 'inactive')),
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

            // In the order the chart expects, filling missing keys with 0. The column holds lowercase values
            // (`active`, `inactive`, `suspended`); looking them up capitalised made every slice 0 and the chart blank.
            $data['driverStatusLabels'] = ['Active', 'Inactive', 'Suspended'];
            $data['driverStatusData'] = [
                (int) ($statusCounts['active'] ?? 0),
                (int) ($statusCounts['inactive'] ?? 0),
                (int) ($statusCounts['suspended'] ?? 0),
            ];
        }

        if ($type === 'maintenance') {
            $rows = $this->maintenanceRows();

            // Pending: a service with something coming up (a due date or odometer) that is not yet overdue.
            $data['maintStats']['pending'] = $rows->whereIn('status', [PreventiveMaintenance::OK, PreventiveMaintenance::DUE_SOON])
                ->where('scheduled', true)->count();

            // Overdue: past its due date, or past the odometer reading it was due at.
            $data['maintStats']['overdue'] = $rows->where('status', PreventiveMaintenance::OVERDUE)->count();

            // Completed: services recorded within the selected date range.
            $data['maintStats']['completed'] = PreventiveMaintenance::whereBetween('created_at', [$from, $to])->count();

            $data['upcomingSchedule'] = $rows->all();
        }

        return $data;
    }

    /**
     * Every recorded service with its REAL due date and status (the model's nextDueDate() / dueStatus()), the
     * most urgent first. `last_service_date` is when the service was last done, not when it falls due: treating it
     * as the due date called every past service "overdue". As on the Maintenance Manager's pages, a vehicle's current
     * odometer is the highest reading logged for it, and disposed units are left out.
     *
     * @return \Illuminate\Support\Collection<int, array{vehicle_plate: string, task_name: string, due_date: string, due_at: ?Carbon, status: string, status_label: string, scheduled: bool, is_overdue: bool}>
     */
    private function maintenanceRows(): \Illuminate\Support\Collection
    {
        $records = PreventiveMaintenance::with(['vehicle', 'maintenanceTask'])
            ->whereHas('vehicle', fn ($q) => $q->where('status', '!=', 'disposed'))
            ->get();

        $currentOdo = $records->groupBy('vehicle_id')->map(fn ($rows) => $rows->max('last_service_odo'));

        return $records->map(function (PreventiveMaintenance $pm) use ($currentOdo) {
            $status = $pm->dueStatus($currentOdo[$pm->vehicle_id] ?? null);
            $due = $pm->nextDueDate();
            $scheduled = $due !== null || $pm->nextDueOdo() !== null;

            return [
                'vehicle_plate' => $pm->vehicle?->plate_number ?? 'N/A',
                'task_name' => $pm->maintenanceTask?->tasks_performed ?? 'Unknown Task',
                'due_date' => $due ? $due->format('Y-m-d') : 'N/A',
                'due_at' => $due,
                'status' => $status,
                'status_label' => match (true) {
                    $status === PreventiveMaintenance::OVERDUE => 'Overdue',
                    $status === PreventiveMaintenance::DUE_SOON => 'Due soon',
                    $scheduled => 'Scheduled',
                    default => 'No interval',
                },
                'scheduled' => $scheduled,
                'is_overdue' => $status === PreventiveMaintenance::OVERDUE,
            ];
        })->sortBy(fn (array $r) => [
            $r['is_overdue'] ? 0 : ($r['status'] === PreventiveMaintenance::DUE_SOON ? 1 : 2),
            $r['due_at'] ? $r['due_at']->timestamp : PHP_INT_MAX,
        ])->values();
    }

    /**
     * E3 — No Data Found: true when the report holds nothing worth showing for
     * the selected type and date range.
     */
    private function reportIsEmpty(string $type, ?string $start, ?string $end): bool
    {
        return match ($type) {
            'financial' => Payment::whereBetween('paid_at', $this->window($start, $end))->count() === 0
                && TopupHistory::whereBetween('created_at', $this->window($start, $end))->count() === 0,
            'driver' => Driver::count() === 0,
            'maintenance' => PreventiveMaintenance::count() === 0,
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
        [$start, $end] = $this->dates($request);

        $filename = "smartcommute_{$type}_report_" . now()->format('Y-m-d') . '.csv';

        // Exports leave the system, so they are part of the audit trail (who exported what, when).
        Audit::log(Audit::SYSTEM, 'report_exported', 'Exported the '.$type.' report as CSV', null, [
            'report' => $type,
            'from' => $start,
            'to' => $end,
        ]);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        [$from, $to] = $this->window($start, $end);

        $callback = function () use ($type, $from, $to) {
            $file = fopen('php://output', 'w');

            if ($type === 'financial') {
                // 1. FINANCIAL EXPORT (Raw Transactions)
                fputcsv($file, ['Date', 'Type', 'Reference ID', 'Amount', 'User']);

                // Get Fares
                $payments = Payment::whereBetween('paid_at', [$from, $to])
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
                $topups = TopupHistory::completed()->whereBetween('created_at', [$from, $to])
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
                        ucfirst($driver->status ?: 'inactive'),
                        (float) ($driver->total_hours ?? 0),
                    ]);
                }
            } elseif ($type === 'maintenance') {
                // 3. MAINTENANCE EXPORT
                fputcsv($file, ['Vehicle Plate', 'Task', 'Due Date', 'Status']);

                foreach ($this->maintenanceRows() as $row) {
                    fputcsv($file, [$row['vehicle_plate'], $row['task_name'], $row['due_date'], $row['status_label']]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
