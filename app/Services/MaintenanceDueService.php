<?php

namespace App\Services;

use App\Models\PreventiveMaintenance;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Which preventive-maintenance services are overdue or about to fall due.
 *
 * The system has no live odometer, so a vehicle's "current" reading is the highest
 * odometer reading logged for any of its services; a later service on the same
 * vehicle is what shows an earlier one is coming up or has been passed.
 */
class MaintenanceDueService
{
    /**
     * Services needing attention, grouped by vehicle id. Vehicles with nothing due are left out,
     * and so are disposed units (nobody services them any more).
     *
     * @return Collection<int, array{vehicle: Vehicle, overdue: Collection, due_soon: Collection}>
     */
    public function fleet(): Collection
    {
        $records = PreventiveMaintenance::with(['maintenanceTask', 'vehicle'])
            ->whereHas('vehicle', fn ($q) => $q->where('status', '!=', 'disposed'))
            ->get()
            ->groupBy('vehicle_id');

        return $records->map(function (Collection $rows) {
            $currentOdo = $rows->max('last_service_odo');
            $items = $rows->map(fn (PreventiveMaintenance $pm) => $this->item($pm, $currentOdo))
                ->filter(fn ($item) => $item['status'] !== PreventiveMaintenance::OK);

            return [
                'vehicle' => $rows->first()->vehicle,
                'overdue' => $items->where('status', PreventiveMaintenance::OVERDUE)->values(),
                'due_soon' => $items->where('status', PreventiveMaintenance::DUE_SOON)->values(),
            ];
        })->filter(fn ($row) => $row['overdue']->isNotEmpty() || $row['due_soon']->isNotEmpty());
    }

    public function forVehicle(Vehicle $vehicle): array
    {
        return $this->fleet()->get($vehicle->id)
            ?? ['vehicle' => $vehicle, 'overdue' => collect(), 'due_soon' => collect()];
    }

    public function hasOverdue(Vehicle $vehicle): bool
    {
        return $this->forVehicle($vehicle)['overdue']->isNotEmpty();
    }

    /** One service in a form the page and the email can both print. */
    private function item(PreventiveMaintenance $pm, ?int $currentOdo): array
    {
        $date = $pm->nextDueDate();
        $odo = $pm->nextDueOdo();

        return [
            'status' => $pm->dueStatus($currentOdo),
            'task' => $pm->maintenanceTask?->tasks_performed ?? 'Unknown task',
            'due_date' => $date?->format('M d, Y'),
            'due_odo' => $odo,
            'km_left' => ($odo !== null && $currentOdo !== null) ? $odo - $currentOdo : null,
        ];
    }
}
