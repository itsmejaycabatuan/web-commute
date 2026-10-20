<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\PreventiveMaintenance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the fleet maintenance summary for a single vehicle.
 *
 * The numbers are derived from the vehicle's preventive-maintenance records: the
 * date, odometer reading and cost of the last service performed for each task.
 *
 * Used by the maintenance-manager dashboard to show the fleet summary.
 */
class FleetMaintenanceService
{
    /**
     * @return array{year:int, costSummary:Collection, monthlyTotals:array, ytdTotal:float,
     *               allLogs:Collection, totalServiceCost:float, costPerKm:float, annualKm:int,
     *               monthlyKm:array, monthlyStartOdo:array, monthlyEndOdo:array,
     *               monthlyCpk:array, yearStartOdo:?int, yearEndOdo:?int}
     */
    public function summaryFor(Vehicle $vehicle, ?int $year = null): array
    {
        $year ??= now()->year;

        // ── Cost Summary (services performed this year) ──
        $yearLogs = PreventiveMaintenance::where('vehicle_id', $vehicle->id)
            ->with('maintenanceTask')
            ->whereYear('last_service_date', $year)
            ->whereNotNull('last_service_date')
            ->orderBy('last_service_date')
            ->get();

        $costSummary = [];
        $monthlyTotals = array_fill(1, 12, 0);
        $ytdTotal = 0;

        foreach ($yearLogs as $log) {
            $taskName = $log->maintenanceTask?->tasks_performed ?? 'Unknown Task';
            $month = $log->last_service_date->month;
            $cost = (float) $log->last_service_cost;

            $costSummary[$taskName] ??= array_fill(1, 12, 0);
            $costSummary[$taskName][$month] += $cost;
            $monthlyTotals[$month] += $cost;
            $ytdTotal += $cost;
        }

        ksort($costSummary);
        $costSummary = collect($costSummary);

        // ── Log rows (recent activity table) ──
        $allLogs = PreventiveMaintenance::where('vehicle_id', $vehicle->id)
            ->with('maintenanceTask')
            ->orderByDesc('last_service_date')
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'task_name' => $log->maintenanceTask?->tasks_performed ?? 'Unknown Task',
                'service_date' => $log->last_service_date?->format('M d, Y'),
                'mileage' => $log->last_service_odo,
                'cost' => $log->last_service_cost,
                'remarks' => $log->comments,
            ]);

        $totalServiceCost = $ytdTotal;

        // ── Odometer / kilometres (derived from logged odometer readings) ──
        $ordered = PreventiveMaintenance::where('vehicle_id', $vehicle->id)
            ->whereNotNull('last_service_odo')
            ->whereNotNull('last_service_date')
            ->orderBy('last_service_date')
            ->orderBy('id')
            ->get();

        $monthlyKm = array_fill(1, 12, 0);
        $monthlyStartOdo = array_fill(1, 12, null);
        $monthlyEndOdo = array_fill(1, 12, null);
        $monthlyCpk = array_fill(1, 12, null);
        $runningOdo = null;

        $firstLogOfYear = $ordered->first(fn ($l) => $l->last_service_date && $l->last_service_date->year === $year);
        $annualStartingOdo = null;
        if ($firstLogOfYear) {
            $prevLog = $ordered->where('id', '<', $firstLogOfYear->id)->last();
            $annualStartingOdo = $prevLog
                ? $prevLog->last_service_odo
                : $firstLogOfYear->last_service_odo;
        }

        foreach ($ordered as $log) {
            $m = $log->last_service_date->month;

            if ($monthlyStartOdo[$m] === null) {
                $monthlyStartOdo[$m] = $runningOdo ?? $annualStartingOdo;
            }

            $monthlyEndOdo[$m] = $log->last_service_odo;

            $baseline = $monthlyStartOdo[$m] ?? $annualStartingOdo;

            if ($baseline !== null) {
                $delta = $log->last_service_odo - $baseline;
                if ($delta > 0) {
                    $monthlyKm[$m] += $delta;
                }
            }
            $runningOdo = $log->last_service_odo;
        }

        $yearStartOdo = $monthlyStartOdo[1];
        $yearEndOdo = $runningOdo;
        $annualKm = array_sum($monthlyKm);

        for ($m = 1; $m <= 12; $m++) {
            if ($monthlyKm[$m] > 0) {
                $monthlyCpk[$m] = round($monthlyTotals[$m] / $monthlyKm[$m], 2);
            }
        }

        $costPerKm = $annualKm > 0 ? round($totalServiceCost / $annualKm, 2) : 0;

        return [
            'year' => $year,
            'costSummary' => $costSummary,
            'monthlyTotals' => $monthlyTotals,
            'ytdTotal' => $ytdTotal,
            'allLogs' => $allLogs,
            'totalServiceCost' => $totalServiceCost,
            'costPerKm' => $costPerKm,
            'annualKm' => $annualKm,
            'monthlyKm' => $monthlyKm,
            'monthlyStartOdo' => $monthlyStartOdo,
            'monthlyEndOdo' => $monthlyEndOdo,
            'monthlyCpk' => $monthlyCpk,
            'yearStartOdo' => $yearStartOdo,
            'yearEndOdo' => $yearEndOdo,
        ];
    }

    /**
     * The same payload with every figure zeroed, for a vehicle with no history.
     */
    public function emptySummary(?int $year = null): array
    {
        return [
            'year' => $year ?? Carbon::now()->year,
            'costSummary' => collect(),
            'monthlyTotals' => array_fill(1, 12, 0),
            'ytdTotal' => 0,
            'allLogs' => collect(),
            'totalServiceCost' => 0,
            'costPerKm' => 0,
            'annualKm' => 0,
            'monthlyKm' => array_fill(1, 12, 0),
            'monthlyStartOdo' => array_fill(1, 12, null),
            'monthlyEndOdo' => array_fill(1, 12, null),
            'monthlyCpk' => array_fill(1, 12, null),
            'yearStartOdo' => null,
            'yearEndOdo' => 0,
        ];
    }
}