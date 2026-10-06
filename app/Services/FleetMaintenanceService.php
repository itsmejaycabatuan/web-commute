<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleMaintenanceLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the fleet maintenance summary for a single vehicle.
 *
 * The numbers are derived from the **service logs** (`vehicle_maintenance_logs`),
 * i.e. the services actually performed, rather than from the preventive-maintenance
 * schedule — a scheduled-but-not-yet-performed service is not a cost or a
 * kilometre reading.
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
        $yearLogs = VehicleMaintenanceLog::where('vehicle_id', $vehicle->id)
            ->with('maintenanceTask')
            ->whereYear('service_date', $year)
            ->whereNotNull('service_date')
            ->orderBy('service_date')
            ->get();

        $costSummary = [];
        $monthlyTotals = array_fill(1, 12, 0);
        $ytdTotal = 0;

        foreach ($yearLogs as $log) {
            $taskName = $log->maintenanceTask?->tasks_performed ?? 'Unknown Task';
            $month = $log->service_date->month;
            $cost = (float) $log->cost;

            $costSummary[$taskName] ??= array_fill(1, 12, 0);
            $costSummary[$taskName][$month] += $cost;
            $monthlyTotals[$month] += $cost;
            $ytdTotal += $cost;
        }

        ksort($costSummary);
        $costSummary = collect($costSummary);

        // ── Log rows (recent activity table) ──
        $allLogs = VehicleMaintenanceLog::where('vehicle_id', $vehicle->id)
            ->with('maintenanceTask')
            ->orderByDesc('service_date')
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'task_name' => $log->maintenanceTask?->tasks_performed ?? 'Unknown Task',
                'service_date' => $log->service_date?->format('M d, Y'),
                'mileage' => $log->mileage_at_service,
                'cost' => $log->cost,
                'performed_by' => $log->performed_by,
                'invoice_number' => $log->invoice_number,
                'remarks' => $log->remarks,
            ]);

        $totalServiceCost = $ytdTotal;

        // ── Odometer / kilometres (derived from logged odometer readings) ──
        $ordered = VehicleMaintenanceLog::where('vehicle_id', $vehicle->id)
            ->whereNotNull('mileage_at_service')
            ->whereNotNull('service_date')
            ->orderBy('service_date')
            ->get();

        $monthlyKm = array_fill(1, 12, 0);
        $monthlyStartOdo = array_fill(1, 12, null);
        $monthlyEndOdo = array_fill(1, 12, null);
        $monthlyCpk = array_fill(1, 12, null);
        $runningOdo = null;

        $firstLogOfYear = $ordered->first(fn ($l) => $l->service_date && $l->service_date->year === $year);
        $annualStartingOdo = null;
        if ($firstLogOfYear) {
            $prevLog = $ordered->where('id', '<', $firstLogOfYear->id)->last();
            $annualStartingOdo = $prevLog
                ? $prevLog->mileage_at_service
                : $firstLogOfYear->mileage_at_service;
        }

        foreach ($ordered as $log) {
            $m = $log->service_date->month;

            if ($monthlyStartOdo[$m] === null) {
                $monthlyStartOdo[$m] = $runningOdo ?? $annualStartingOdo;
            }

            $monthlyEndOdo[$m] = $log->mileage_at_service;

            $baseline = $monthlyStartOdo[$m] ?? $annualStartingOdo;

            if ($baseline !== null) {
                $delta = $log->mileage_at_service - $baseline;
                if ($delta > 0) {
                    $monthlyKm[$m] += $delta;
                }
            }
            $runningOdo = $log->mileage_at_service;
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