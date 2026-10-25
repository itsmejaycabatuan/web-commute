<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\QrScan;
use App\Services\NearbyVehicleFinder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Pay on board: before a fare can be paid the commuter scans one of the QR codes the system generated
 * (UCN_SC_E006). The in-app scanner posts what the camera read; a phone's own camera app opens the
 * link in the code, which does the same thing.
 */
class RideVerificationController extends Controller
{
    private const INVALID = "That QR code isn't valid. Scan one of the QR codes inside the PUJ.";

    public function show(Request $request)
    {
        if (! config('qr.required')) {
            return redirect()->route('map');
        }

        return view('commuter.scan', [
            'scan' => QrScan::currentFor($request->user()->id),
            'hasTrip' => (bool) $request->session()->has('qr_trip'),
        ]);
    }

    /** What the in-app scanner (or the typed code) sent. */
    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string|max:2000'], [
            'code.required' => 'Scan a QR code or type its code.',
        ]);

        $code = QrCode::fromScanned($request->input('code'));

        if (! $code) {
            throw ValidationException::withMessages(['code' => self::INVALID]);
        }

        return $this->verified($request, $code);
    }

    /** The link inside the QR image, opened from a phone's camera app. */
    public function link(Request $request, string $token)
    {
        $code = QrCode::fromScanned($token);

        if (! $code) {
            return redirect()->route('payment.scan')->with('error', self::INVALID);
        }

        return $this->verified($request, $code);
    }

    /**
     * The checkout page reports where the commuter is. If a vehicle is broadcasting right there the scan
     * is tied to it and to its driver on duty; the coordinates themselves are not kept.
     */
    public function locate(Request $request, NearbyVehicleFinder $finder)
    {
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        $scan = QrScan::currentFor($request->user()->id);

        if (! $scan) {
            return response()->json(['matched' => false, 'message' => 'Scan a QR code first.'], 422);
        }

        $match = $finder->find((float) $data['latitude'], (float) $data['longitude'], isset($data['accuracy']) ? (float) $data['accuracy'] : null);

        $scan->update([
            'vehicle_id' => $match['vehicle']->id ?? null,
            'driver_id' => $match['driver']->id ?? null,
            'matched_distance_m' => $match['distance_m'] ?? null,
            'located_at' => now(),
        ]);

        if (! $match) {
            return response()->json([
                'matched' => false,
                'required' => (bool) config('qr.require_nearby_vehicle'),
                'message' => "We couldn't find a SmartCommute PUJ near you.",
            ]);
        }

        return response()->json([
            'matched' => true,
            'plate_number' => $match['vehicle']->plate_number,
            'distance_m' => $match['distance_m'],
        ]);
    }

    private function verified(Request $request, QrCode $code)
    {
        QrScan::record($request->user()->id, $code);

        $message = 'Verified. You can pay your fare now.';

        // Back to the trip they were buying when they were sent here; otherwise let them pick one.
        if ($trip = $request->session()->pull('qr_trip')) {
            return redirect()->route('payment.index', $trip)->with('success', $message);
        }

        return redirect()->route('map')->with('success', $message.' Choose your trip and press Buy a Ride.');
    }
}
