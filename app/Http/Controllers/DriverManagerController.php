<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\ViolationCode;
use App\Models\ViolationLog;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DriverManagerController extends Controller
{
    public function timeKeeping()
    {
        $drivers = Driver::with('user')
            ->get()
            ->map(fn($driver) => [
                'id' => $driver->id,
                'name' => "{$driver->name}",
            ]);

        $entries = TimeKeeping::with('driver.user')
            ->latest()
            ->paginate(10);

        return view('driver-manager.time-keeping', compact('drivers', 'entries'));
    }

    public function timeKeepingStore(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'date' => 'required|date',
        ]);

        $timeIn = Carbon::parse($validated['date'] . ' ' . $request->time_in);
        $timeOut = Carbon::parse($validated['date'] . ' ' . $request->time_out);

        $totalHours = $timeIn->diffInMinutes($timeOut) / 60;
        $overtime = max(0, $totalHours - 8);

        TimeKeeping::create([
            'driver_id' => $validated['driver_id'],
            'date' => $validated['date'],
            'time_in' => $request->time_in,
            'time_out' => $request->time_out,
            'hours_worked' => round($totalHours, 2),
            'overtime_hours' => round($overtime, 2),
            'sick' => $request->sick,
            'vacation' => $request->vacation,
        ]);

        return back()
            ->with('success', 'Time entry saved successfully.');

        return back();
    }

    public function violationsLog()
    {
        // Fetch drivers through User -> Driver relationship
        $drivers = User::with('driver')
            ->select('id', 'email')
            ->get()
            ->filter(fn($u) => $u->driver)
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->driver->name,
                'license' => $u->driver->license_number,
                'licenseCode' => $u->driver->license_code,
                'expirationDate' => $u->driver->expiration_date
                                    ? Carbon::parse($u->driver->expiration_date)->format('d M, Y')
                                    : 'N/A',
                'email' => $u->email,
                'contactInfo' => $u->driver->contact_info ?? 'N/A',
            ]);

        // Violation codes — unchanged
        $violationCodes = ViolationCode::select('id', 'code', 'violation_name', 'first_offense', 'second_offense', 'third_offense', 'fourth_offense')
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'code' => $v->code,
                'violation_name' => $v->violation_name,
                'first_offense' => (float) $v->first_offense,
                'second_offense' => (float) $v->second_offense,
                'third_offense' => (float) $v->third_offense,
                'fourth_offense' => (float) $v->fourth_offense,
            ]);

        // Violation logs — pull driver info through user->driver
        $violations = ViolationLog::with(['user.driver', 'violationCode'])
            ->latest()
            ->get()
            ->map(function ($v) {
                $driver = $v->user?->driver;

                return [
                    'id' => $v->id,
                    'driverId' => $v->user_id,
                    'driverName' => $driver?->name ?? 'N/A',
                    'license' => $driver?->license_number ?? 'N/A',
                    'expirationDate' => $driver?->expiration_date
                                        ? Carbon::parse($driver->expiration_date)->format('d M, Y')
                                        : 'N/A',
                    'violationType' => $v->violationCode?->violation_name ?? 'Unknown',
                    'violationCode' => $v->violationCode?->code ?? 'N/A',
                    'codeColor' => $v->violationCode?->severity ?? 'amber',
                    'offenseCount' => $v->violation_instance,
                    'remarks' => $v->remarks,
                    'location' => $v->place_of_violation,
                    'date' => Carbon::parse($v->date_of_violation)->format('d F, Y'),
                    'time' => Carbon::parse($v->time_of_violation)->format('h:i A'),
                    'fine' => (float) $v->violation_fine,
                    'penalty' => $v->additional_penalties,
                    'penaltyColor' => $this->getPenaltyColor($v->additional_penalties),
                ];
            });

        return view('driver-manager.violations-log', compact('drivers', 'violationCodes', 'violations'));
    }

    public function storeViolationLog(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'vc_id' => 'required|exists:violation_codes,id',
            'violation_instance' => 'required|integer|min:1',
            'violation_fine' => 'required|numeric|min:0',
            'place_of_violation' => 'required|string|max:255',
            'date_of_violation' => 'required|date',
            'time_of_violation' => 'required',
            'remarks' => 'required|string|max:500',
        ]);

        ViolationLog::create($validated);

        return back()->with('success', 'Violation logged!');
    }

    public function storeViolationLogBulk(Request $request)
    {
        $validator = $request->validate([
            'user_id' => 'required|exists:users,id',
            'violations' => 'required|array|min:1',
            'violations.*.vc_id' => 'required|exists:violation_codes,id',
            'violations.*.violation_instance' => 'required|in:1,2,3,4',
            'violations.*.violation_fine' => 'required|numeric|min:0',
            'violations.*.place_of_violation' => 'required|string|max:255',
            'violations.*.date_of_violation' => 'required|date',
            'violations.*.time_of_violation' => 'required',
            'violations.*.remarks' => 'nullable|string|max:500',
        ]);

        if (! $validator) {
            return redirect()->route('driver-manager.violations-log.index')
                ->withErrors($validator)
                ->withInput()
                ->with('bulk_validation_failed', true);
        }

        foreach ($request->violations as $v) {
            ViolationLog::create([
                'user_id' => $request->user_id,
                'vc_id' => $v['vc_id'],
                'violation_instance' => $v['violation_instance'],
                'violation_fine' => $v['violation_fine'],
                'place_of_violation' => $v['place_of_violation'],
                'date_of_violation' => $v['date_of_violation'],
                'time_of_violation' => $v['time_of_violation'],
                'remarks' => $v['remarks'] ?? null,
            ]);
        }

        return back()
            ->with('success', count($request->violations) . ' violations logged successfully.');
    }

    private function getPenaltyColor($penalty)
    {
        if (! $penalty || $penalty === 'N/A') {
            return 'muted';
        }
        $lower = strtolower($penalty);
        if (str_contains($lower, ['suspension', 'revocation', 'impound'])) {
            return 'red';
        }
        if (str_contains($lower, ['warning', 'community'])) {
            return 'amber';
        }

        return 'muted';
    }

    public function violationCodes()
    {
        $violationCodes = ViolationCode::get();

        return view('driver-manager.violation-codes', [
            'violation_codes' => $violationCodes,
        ]);
    }

    public function storeViolationCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'first' => 'required',
            'second' => 'required',
            'third' => 'required',
            'fourth_plus' => 'required',
            'is_revocation' => 'required',
        ]);

        $violationCode = ViolationCode::create([
            'code' => $request->code,
            'violation_name' => $request->name,
            'first_offense' => $request->first,
            'second_offense' => $request->second,
            'third_offense' => $request->third,
            'fourth_offense' => $request->fourth_plus,
            'is_revoked' => $request->is_revocation,
        ]);

        if (! $violationCode) {
            return back()->with('error', 'Error adding violation code.');
        }

        return back()->with('success', 'Violation code successfully added!');
    }

    public function updateViolationCode(Request $request, string $id)
    {

        $request->validate([
            'code' => 'required|string|max:255|unique:violation_codes,code,' . $id,
            'name' => 'required|string|max:255',
            'first' => 'required',
            'second' => 'required',
            'third' => 'required',
            'fourth_plus' => 'required',
            'is_revocation' => 'required',
        ]);

        $violationCode = ViolationCode::find($id);

        if (! $violationCode) {
            return back()->with('error', 'Error no violation code found');
        }

        $violationCode->update([
            'code' => $request->code,
            'violation_name' => $request->name,
            'first_offense' => $request->first,
            'second_offense' => $request->second,
            'third_offense' => $request->third,
            'fourth_offense' => $request->fourth_plus,
            'is_revoked' => $request->is_revocation,
        ]);

        return back()->with('success', 'Violation code successfully updated!');
    }

    public function destroyViolationCode(string $id)
    {
        $violationCode = ViolationCode::destroy($id);

        if (! $violationCode) {
            return back()->with('message', 'Error no violation code found.');
        }

        return back()->with('success', 'Violation code successfully deleted!');
    }

    public function profile()
    {
        $user = Auth::user();

        return view('driver-manager.profile', [
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
        ]);

        $userId = Auth::user()->id;
        $user = User::where('id', $userId)->first();

        if ($user->update(['password' => $request->password_confirmation])) {
            return redirect()->route('commuter.profile')->with('success', 'password successfully updated');
        }

        return back()->with('error', 'Failed to update password');
    }
}
