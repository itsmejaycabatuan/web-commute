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
                'acquisition_date' => $vehicle->acquisition_date?->format('M d, Y'),
                'exp_disposal_date' => $vehicle->exp_disposal_date?->format('M d, Y'),
                'driver_name' => $vehicle->driver?->name,
                'created_at' => $vehicle->created_at?->format('M d, Y'),
                'updated_at' => $vehicle->updated_at?->format('M d, Y'),
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
            'acquisition_date' => 'required|date',
            'exp_disposal_date' => 'nullable|date|after:acquisition_date',
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
            'acquisition_date' => 'required|date',
            'exp_disposal_date' => 'nullable|date|after:acquisition_date',
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
