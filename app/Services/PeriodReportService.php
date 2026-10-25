<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\TimeKeeping;
use App\Models\TopupHistory;
use App\Models\VehicleMaintenanceLog;
use App\Models\ViolationLog;
use App\Support\AuditPresenter;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * The figures in a weekly or monthly report, and the CSV files that go with them.
 */
class PeriodReportService
{
    public function __construct(
        private FleetAnalyticsService $analytics,
        private MaintenanceDueService $maintenanceDue,
    ) {
    }

    /** The period a scheduled run covers: the last full week (Mon–Sun) or the last full calendar month. */
    public static function periodFor(string $kind, ?Carbon $now = null): array
    {
        $now ??= now();

        if ($kind === 'monthly') {
            $from = $now->copy()->subMonthNoOverflow()->startOfMonth();

            return [$from, $from->copy()->endOfMonth()];
        }

        $from = $now->copy()->subWeek()->startOfWeek();

        return [$from, $from->copy()->endOfWeek()];
    }

    public function build(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $paid = Payment::settled()->whereBetween('paid_at', [$start, $end]);
        $byMethod = (clone $paid)->selectRaw('payment_method, COUNT(*) as total, COALESCE(SUM(price), 0) as revenue')
            ->groupBy('payment_method')->orderByDesc('revenue')->get()
            ->map(fn ($r) => ['method' => $r->payment_method, 'count' => (int) $r->total, 'revenue' => round((float) $r->revenue, 2)])->all();

        $topups = TopupHistory::completed()->whereBetween('created_at', [$start, $end]);

        $violations = ViolationLog::whereBetween('date_of_violation', [$start->toDateString(), $end->toDateString()]);

        $maintenanceLogs = VehicleMaintenanceLog::whereBetween('service_date', [$start->toDateString(), $end->toDateString()]);
        $due = $this->maintenanceDue->fleet();

        $drivers = $this->analytics->driverStats($start, $end);
        $vehicles = $this->analytics->vehicleStats($start, $end);

        return [
            'from' => $start,
            'to' => $end,
            'label' => $start->format('M j').' – '.$end->format('M j, Y'),
            'revenue' => [
                'total' => round((float) (clone $paid)->sum('price'), 2),
                'count' => (clone $paid)->count(),
                'by_method' => $byMethod,
            ],
            'payments' => [
                'failed' => Payment::where('status', Payment::STATUS_FAILED)->whereBetween('failed_at', [$start, $end])->count(),
                'pending_now' => Payment::where('status', Payment::STATUS_PENDING)->count(),
            ],
            'topups' => [
                'total' => round((float) (clone $topups)->sum('amount_added'), 2),
                'count' => (clone $topups)->count(),
            ],
            'violations' => [
                'count' => (clone $violations)->count(),
                'issued' => round((float) (clone $violations)->sum('violation_fine'), 2),
                'collected' => round((float) ViolationLog::where('payment_status', ViolationLog::PAID)->whereBetween('paid_at', [$start, $end])->sum('violation_fine'), 2),
                'outstanding' => round((float) ViolationLog::unpaid()->sum('violation_fine'), 2),
            ],
            'maintenance' => [
                'cost' => round((float) (clone $maintenanceLogs)->sum('cost'), 2),
                'services' => (clone $maintenanceLogs)->count(),
                'overdue_now' => $due->sum(fn ($row) => $row['overdue']->count()),
                'due_soon_now' => $due->sum(fn ($row) => $row['due_soon']->count()),
            ],
            'time' => [
                'hours' => round($drivers->sum('hours'), 1),
                'overtime' => round($drivers->sum('overtime'), 1),
                'auto_closed' => $drivers->sum('auto_closed'),
                'awaiting_review' => TimeKeeping::needsReview()->count(),
            ],
            'distance_km' => round($vehicles->sum('km'), 1),
            'top_drivers' => $drivers->take(5)->all(),
            'top_vehicles' => $vehicles->take(5)->all(),
            'repeat_violators' => $this->analytics->repeatViolators()->map(fn ($r) => ['name' => $r['driver']->name, 'count' => $r['count']])->all(),
            'abandoned_scans' => $this->analytics->abandonedScans()->map(fn ($r) => ['email' => $r['email'], 'count' => $r['count']])->all(),
        ];
    }

    /** The report as "Section, Metric, Value" rows: opens straight into a spreadsheet. */
    public function summaryCsv(array $report): string
    {
        $rows = [
            ['Section', 'Metric', 'Value'],
            ['Period', 'From', $report['from']->toDateString()],
            ['Period', 'To', $report['to']->toDateString()],
            ['Fares', 'Revenue (PHP)', $report['revenue']['total']],
            ['Fares', 'Paid fares', $report['revenue']['count']],
            ['Fares', 'Failed payments', $report['payments']['failed']],
            ['Fares', 'Payments pending now', $report['payments']['pending_now']],
        ];

        foreach ($report['revenue']['by_method'] as $m) {
            $rows[] = ['Fares', 'Revenue via '.$m['method'].' (PHP)', $m['revenue']];
        }

        array_push($rows,
            ['Top-ups', 'Completed top-ups (PHP)', $report['topups']['total']],
            ['Top-ups', 'Number of top-ups', $report['topups']['count']],
            ['Violations', 'Violations logged', $report['violations']['count']],
            ['Violations', 'Fines issued (PHP)', $report['violations']['issued']],
            ['Violations', 'Fines collected (PHP)', $report['violations']['collected']],
            ['Violations', 'Fines outstanding now (PHP)', $report['violations']['outstanding']],
            ['Maintenance', 'Service cost (PHP)', $report['maintenance']['cost']],
            ['Maintenance', 'Services performed', $report['maintenance']['services']],
            ['Maintenance', 'Overdue services now', $report['maintenance']['overdue_now']],
            ['Maintenance', 'Services due soon now', $report['maintenance']['due_soon_now']],
            ['Time-keeping', 'Hours worked', $report['time']['hours']],
            ['Time-keeping', 'Overtime hours', $report['time']['overtime']],
            ['Time-keeping', 'Auto-closed shifts', $report['time']['auto_closed']],
            ['Time-keeping', 'Auto-closed shifts awaiting review', $report['time']['awaiting_review']],
            ['Fleet', 'Distance driven (km)', $report['distance_km']],
        );

        foreach ($report['top_drivers'] as $d) {
            $rows[] = ['Top drivers by hours', $d['name'], $d['hours'].' h, '.$d['overtime'].' h overtime'];
        }
        foreach ($report['top_vehicles'] as $v) {
            $rows[] = ['Top vehicles by distance', $v['plate'], $v['km'].' km'];
        }

        return $this->csv($rows);
    }

    /** The audit trail for the period, newest first, capped. Null when there is nothing in it. */
    public function activityLogCsv(Carbon $from, Carbon $to): ?string
    {
        $activities = Activity::where('description', 'not like', 'Action performed:%')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('created_at')
            ->limit((int) config('reports.activity_log_limit', 10000))
            ->get();

        if ($activities->isEmpty()) {
            return null;
        }

        $rows = [['Entry', 'Time', 'Category', 'Event', 'Actor email', 'Actor role', 'Description', 'Record']];

        foreach ($activities as $activity) {
            $r = AuditPresenter::present($activity);
            $rows[] = [
                $r['id'], $r['when']?->format('Y-m-d H:i:s'), $r['category_label'], $r['event_label'],
                $r['actor_email'] ?? '', $r['actor_role'] ?? '', $r['description'], $r['subject'] ?? '',
            ];
        }

        return $this->csv($rows);
    }

    private function csv(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads ₱ and accents correctly

        foreach ($rows as $row) {
            fputcsv($out, array_map([$this, 'csvSafe'], $row));
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /** Stops a spreadsheet from running a cell that starts like a formula (=, +, -, @). */
    private function csvSafe(mixed $value): mixed
    {
        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }
}
