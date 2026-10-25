<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Payment;
use App\Models\QrScan;
use App\Models\TimeKeeping;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use App\Models\VehicleMaintenanceLog;
use App\Models\ViolationLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Hours, distance, overtime, fares, violations and maintenance cost per driver and per vehicle, worked out
 * from the records the system already keeps (time-keeping, the GPS trail, payments, violations,
 * maintenance logs). Nothing here is typed in by hand.
 */
class FleetAnalyticsService
{
    /**
     * One row per approved driver, busiest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function driverStats(Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $time = TimeKeeping::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('driver_id, COALESCE(SUM(hours_worked), 0) as hours, COALESCE(SUM(overtime_hours), 0) as overtime,'
                .' SUM(CASE WHEN hours_worked > 0 THEN 1 ELSE 0 END) as shifts,'
                .' SUM(CASE WHEN auto_closed_at IS NOT NULL THEN 1 ELSE 0 END) as auto_closed')
            ->groupBy('driver_id')
            ->get()->keyBy('driver_id');

        $violations = ViolationLog::whereBetween('date_of_violation', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('user_id, COUNT(*) as total, COALESCE(SUM(violation_fine), 0) as fines')
            ->groupBy('user_id')
            ->get()->keyBy('user_id');

        $fares = Payment::settled()->whereNotNull('driver_id')->whereBetween('paid_at', [$from, $to])
            ->selectRaw('driver_id, COUNT(*) as total, COALESCE(SUM(price), 0) as revenue')
            ->groupBy('driver_id')
            ->get()->keyBy('driver_id');

        return Driver::where('is_approved', true)->where('is_rejected', false)->get()
            ->map(function (Driver $d) use ($time, $violations, $fares) {
                $t = $time->get($d->id);
                $v = $violations->get($d->user_id);
                $f = $fares->get($d->id);

                return [
                    'id' => $d->id,
                    'code' => $d->driver_code,
                    'name' => $d->name,
                    'shifts' => (int) ($t->shifts ?? 0),
                    'hours' => round((float) ($t->hours ?? 0), 2),
                    'overtime' => round((float) ($t->overtime ?? 0), 2),
                    'auto_closed' => (int) ($t->auto_closed ?? 0),
                    'violations' => (int) ($v->total ?? 0),
                    'fines' => round((float) ($v->fines ?? 0), 2),
                    'fares' => (int) ($f->total ?? 0),
                    'revenue' => round((float) ($f->revenue ?? 0), 2),
                ];
            })
            ->sortByDesc('hours')
            ->values();
    }

    /**
     * One row per vehicle that is not disposed.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function vehicleStats(Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $km = VehicleLocationHistory::join('vehicle_locations', 'vehicle_locations.id', '=', 'vehicle_location_histories.vehicle_location_id')
            ->whereBetween('vehicle_location_histories.created_at', [$from, $to])
            ->selectRaw('vehicle_locations.vehicle_id as vehicle_id, COALESCE(SUM(vehicle_location_histories.distance_from_last_pos), 0) as km')
            ->groupBy('vehicle_locations.vehicle_id')
            ->get()->keyBy('vehicle_id');

        $hours = TimeKeeping::whereNotNull('vehicle_id')->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('vehicle_id, COALESCE(SUM(hours_worked), 0) as hours')
            ->groupBy('vehicle_id')
            ->get()->keyBy('vehicle_id');

        $fares = Payment::settled()->whereNotNull('vehicle_id')->whereBetween('paid_at', [$from, $to])
            ->selectRaw('vehicle_id, COUNT(*) as total, COALESCE(SUM(price), 0) as revenue')
            ->groupBy('vehicle_id')
            ->get()->keyBy('vehicle_id');

        $maintenance = VehicleMaintenanceLog::whereBetween('service_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('vehicle_id, COALESCE(SUM(cost), 0) as cost')
            ->groupBy('vehicle_id')
            ->get()->keyBy('vehicle_id');

        $scans = QrScan::whereNotNull('vehicle_id')->whereBetween('scanned_at', [$from, $to])
            ->selectRaw('vehicle_id, COUNT(*) as scanned, SUM(CASE WHEN used_at IS NOT NULL THEN 1 ELSE 0 END) as paid')
            ->groupBy('vehicle_id')
            ->get()->keyBy('vehicle_id');

        return Vehicle::where('status', '!=', 'disposed')->with('drivers')->orderBy('plate_number')->get()
            ->map(function (Vehicle $v) use ($km, $hours, $fares, $maintenance, $scans) {
                $s = $scans->get($v->id);
                $scanned = (int) ($s->scanned ?? 0);
                $paid = (int) ($s->paid ?? 0);

                return [
                    'id' => $v->id,
                    'plate' => $v->plate_number,
                    'drivers' => $v->driver_name,
                    'km' => round((float) ($km->get($v->id)->km ?? 0), 1),
                    'hours' => round((float) ($hours->get($v->id)->hours ?? 0), 2),
                    'fares' => (int) ($fares->get($v->id)->total ?? 0),
                    'revenue' => round((float) ($fares->get($v->id)->revenue ?? 0), 2),
                    'maintenance_cost' => round((float) ($maintenance->get($v->id)->cost ?? 0), 2),
                    'scans' => $scanned,
                    // Share of scans on this vehicle that ended in a fare (null: nobody scanned on it).
                    'collection_rate' => $scanned > 0 ? (int) round($paid / $scanned * 100) : null,
                ];
            })
            ->sortByDesc('km')
            ->values();
    }

    /**
     * Total and overtime hours per week for the last $weeks weeks, oldest first.
     *
     * @return array<int, array{week: string, label: string, hours: float, overtime: float}>
     */
    public function overtimeTrend(int $weeks = 8): array
    {
        $first = now()->startOfWeek()->subWeeks($weeks - 1);

        $byWeek = TimeKeeping::where('date', '>=', $first->toDateString())
            ->get(['date', 'hours_worked', 'overtime_hours'])
            ->groupBy(fn ($row) => Carbon::parse($row->getRawOriginal('date'))->startOfWeek()->toDateString());

        $series = [];

        for ($i = 0; $i < $weeks; $i++) {
            $start = $first->copy()->addWeeks($i);
            $rows = $byWeek->get($start->toDateString(), collect());

            $series[] = [
                'week' => $start->toDateString(),
                'label' => $start->format('M j'),
                'hours' => round((float) $rows->sum('hours_worked'), 1),
                'overtime' => round((float) $rows->sum('overtime_hours'), 1),
            ];
        }

        return $series;
    }

    /**
     * Drivers with $count or more violations in the last $days days.
     *
     * @return Collection<int, array{driver: Driver, count: int, fines: float, violations: Collection}>
     */
    public function repeatViolators(?int $count = null, ?int $days = null): Collection
    {
        $count ??= (int) config('alerts.repeat_violator_count', 3);
        $days ??= (int) config('alerts.repeat_violator_days', 30);

        $recent = ViolationLog::with('violationCode')
            ->where('date_of_violation', '>=', today()->subDays($days)->toDateString())
            ->get()
            ->groupBy('user_id')
            ->filter(fn (Collection $rows) => $rows->count() >= $count);

        if ($recent->isEmpty()) {
            return collect();
        }

        $drivers = Driver::whereIn('user_id', $recent->keys())->get()->keyBy('user_id');

        return $recent->map(fn (Collection $rows, $userId) => [
            'driver' => $drivers->get($userId),
            'count' => $rows->count(),
            'fines' => round((float) $rows->sum('violation_fine'), 2),
            'violations' => $rows,
        ])->filter(fn ($row) => $row['driver'])->sortByDesc('count')->values();
    }

    /**
     * Commuters whose scans were never followed by a fare: scanned, then did not pay inside the scan's
     * validity window. Only scans old enough to have expired count.
     *
     * @return Collection<int, array{user_id: int, email: string, count: int, last: Carbon}>
     */
    public function abandonedScans(?int $minimum = null, ?int $days = null): Collection
    {
        $minimum ??= (int) config('alerts.fare_evasion_scans', 3);
        $days ??= (int) config('alerts.fare_evasion_days', 7);

        return QrScan::with('user:id,email')
            ->whereNull('used_at')
            ->where('scanned_at', '>=', now()->subDays($days))
            ->where('scanned_at', '<', now()->subMinutes((int) config('qr.valid_minutes', 15)))
            ->get()
            ->groupBy('user_id')
            ->filter(fn (Collection $rows) => $rows->count() >= $minimum)
            ->map(fn (Collection $rows, $userId) => [
                'user_id' => (int) $userId,
                'email' => $rows->first()->user?->email ?? 'unknown',
                'count' => $rows->count(),
                'last' => $rows->max('scanned_at'),
            ])
            ->sortByDesc('count')
            ->values();
    }

    /** Headline totals for a period, for the page and the scheduled report. */
    public function totals(Collection $drivers, Collection $vehicles): array
    {
        return [
            'hours' => round($drivers->sum('hours'), 1),
            'overtime' => round($drivers->sum('overtime'), 1),
            'km' => round($vehicles->sum('km'), 1),
            'revenue' => round($vehicles->sum('revenue'), 2),
            'violations' => $drivers->sum('violations'),
            'maintenance_cost' => round($vehicles->sum('maintenance_cost'), 2),
        ];
    }
}
