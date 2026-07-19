<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with('driver')->latest()->get()->map(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'driver_id' => $vehicle->driver_id,
                'year' => $vehicle->year,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'plate_number' => $vehicle->plate_number,
                'status' => $vehicle->status,
                'fuel_type' => $vehicle->fuel_type,
                'tank_capacity' => $vehicle->tank_capacity,
                'vin' => $vehicle->vin,
                'location' => $vehicle->location,
                // Y-m-d format: fixes both the JS dateStr() and the <input type="date">
                'acquistion_date' => $vehicle->acquistion_date?->format('Y-m-d'),
                'exp_disposal_date' => $vehicle->exp_disposal_date?->format('Y-m-d'),
                // Explicitly pull the driver name so it's always a plain string in JSON
                'driver_name' => $vehicle->driver?->name,
                // ISO string for timestamps (used by dateTimeStr which doesn't append T00:00:00)
                'created_at' => $vehicle->created_at?->toISOString(),
                'updated_at' => $vehicle->updated_at?->toISOString(),
            ];
        });

        $drivers = Driver::orderBy('name')->get();

        return view('maintenance-manager.vehicles', compact('vehicles', 'drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:1990|max:2030',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'vin' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'tank_capacity' => 'nullable|string|max:20',
            'driver_id' => 'nullable|exists:drivers,id',
            'location' => 'nullable|string|max:150',
            'status' => 'required|in:active,maintenance,inactive,disposed',
            'acquistion_date' => 'required|date',
            'exp_disposal_date' => 'nullable|date|after:acquistion_date',
        ]);

        Vehicle::create($validated);

        return back()->with('success', 'Vehicle successfully added.');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:1990|max:2030',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,' . $vehicle->id,
            'vin' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'tank_capacity' => 'nullable|string|max:20',
            'driver_id' => 'nullable|exists:drivers,id',
            'location' => 'nullable|string|max:150',
            'status' => 'required|in:active,maintenance,inactive,disposed',
            'acquistion_date' => 'required|date',
            'exp_disposal_date' => 'nullable|date|after:acquistion_date',
        ]);

        $vehicle->update($validated);

        return back()->with('success', 'Vehicle successfully updated.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return back()->with('success', 'Vehicle successfully deleted.');
    }
}
