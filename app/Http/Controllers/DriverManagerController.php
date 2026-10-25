<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\ViolationCode;
use App\Models\ViolationLog;
use App\Notifications\ViolationPaid;
use App\Notifications\ViolationRecorded;
use App\Support\Notifier;
use Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        // The page filters and pages (10 per page) in the browser, so it needs the
        // entries themselves rather than a single server-side page of them.
        $entries = TimeKeeping::with('driver.user')
            ->latest()
            ->limit(1000)
            ->get();

        $needsReview = TimeKeeping::needsReview()->count();

        return view('driver-manager.time-keeping', compact('drivers', 'entries', 'needsReview'));
    }

    /**
     * Confirm a shift the system closed because the driver never clocked out.
     */
    public function timeKeepingReview(TimeKeeping $entry)
    {
        if (! $entry->wasAutoClosed()) {
            return back()->with('error', 'Only auto-closed shifts need a review.');
        }

        if ($entry->reviewed_at) {
            return back()->with('error', 'This shift was already reviewed.');
        }

        try {
            $entry->update(['reviewed_at' => now()]);
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The review was not saved.');
        }

        return back()->with('success', 'Shift marked as reviewed.');
    }

    public function timeKeepingStore(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'date' => 'required|date',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'sick' => 'nullable|boolean',
            'vacation' => 'nullable|boolean',
        ]);

        $isLeave = $request->boolean('sick') || $request->boolean('vacation');
        $timeIn = $validated['time_in'] ?? null;
        $timeOut = $validated['time_out'] ?? null;

        if (! $isLeave && (! $timeIn || ! $timeOut)) {
            throw ValidationException::withMessages([
                'time_in' => 'The time in and time out are required.',
            ]);
        }

        if ($isLeave) {
            // E3 — Database error: nothing is stored and the Driver Manager is told.
            try {
                TimeKeeping::create([
                    'driver_id' => $validated['driver_id'],
                    'date' => $validated['date'],
                    'time_in' => null,
                    'time_out' => null,
                    'hours_worked' => 0,
                    'overtime_hours' => 0,
                    'sick' => $request->boolean('sick'),
                    'vacation' => $request->boolean('vacation'),
                ]);
            } catch (QueryException $e) {
                return $this->writeFailed($e, 'The time entry was not saved.');
            }

            return back()->with('success', 'Time entry saved successfully.');
        }

        $in = Carbon::parse($validated['date'].' '.$timeIn);
        $out = Carbon::parse($validated['date'].' '.$timeOut);

        // E4 — Clock out is before clock in.
        if ($out->lessThanOrEqualTo($in)) {
            throw ValidationException::withMessages([
                'time_out' => 'The clock-out time must be later than the clock-in time.',
            ]);
        }

        // E5 — Overlapping shifts. time_in/time_out are free-form strings
        // ("08:00" from this form, "08:00 AM" from the driver's own clock-in), so
        // the comparison is done on parsed values rather than in SQL.
        $conflicting = TimeKeeping::where('driver_id', $validated['driver_id'])
            ->whereDate('date', $validated['date'])
            ->get()
            ->filter(fn ($entry) => $entry->time_in && $entry->time_out
                && $this->shiftOverlaps($entry, $in, $out))
            ->first();

        if ($conflicting) {
            throw ValidationException::withMessages([
                'time_in' => 'This shift overlaps an existing time entry for the driver on that date.',
            ]);
        }

        $totalHours = $in->diffInMinutes($out) / 60;
        $overtime = max(0, $totalHours - 8);

        // E3 — Database error: nothing is stored and the Driver Manager is told.
        try {
            TimeKeeping::create([
                'driver_id' => $validated['driver_id'],
                'date' => $validated['date'],
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'hours_worked' => round($totalHours, 2),
                'overtime_hours' => round($overtime, 2),
                'sick' => false,
                'vacation' => false,
            ]);
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The time entry was not saved.');
        }

        return back()
            ->with('success', 'Time entry saved successfully.');
    }

    /**
     * UCN_SC_E013 E3 / E014 E3 / E024 E5 — a failed write is logged, cancelled
     * and reported back to the actor instead of surfacing as a raw 500 page.
     */
    private function writeFailed(QueryException $e, string $detail)
    {
        report($e);

        return back()
            ->withInput()
            ->with('error', 'A database error occurred. '.$detail);
    }

    /**
     * True when an existing entry and the candidate shift share any part of the day.
     */
    private function shiftOverlaps(TimeKeeping $entry, Carbon $in, Carbon $out): bool
    {
        try {
            $existingIn = Carbon::parse($entry->date.' '.$entry->time_in);
            $existingOut = Carbon::parse($entry->date.' '.$entry->time_out);
        } catch (\Exception $e) {
            return false;
        }

        return $existingIn->lessThan($out) && $in->lessThan($existingOut);
    }

    public function violationsLog(Request $request)
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
                'licenseState' => $u->driver->licenseState(),
            ])
            ->values(); // filter() leaves gaps in the keys, which JSON-encodes as an object the form cannot .find() on

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

        // How many times each driver has already been logged for each code, so the
        // form can pre-select the next offense number (1st, 2nd, 3rd, 4th+).
        $offenseCounts = ViolationLog::selectRaw('user_id, vc_id, COUNT(*) as total')
            ->groupBy('user_id', 'vc_id')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('total', 'vc_id')->map(fn ($n) => (int) $n));

        // Violation logs — pull driver info through user->driver
        // A7 — the log can be narrowed by driver name and/or date range.
        $search = trim((string) $request->query('search', ''));
        $from = $request->query('from');
        $to = $request->query('to');

        $violationQuery = ViolationLog::with(['user.driver', 'violationCode'])
            ->when($from, fn ($q) => $q->whereDate('date_of_violation', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_of_violation', '<=', $to))
            ->when($search, fn ($q) => $q->whereHas(
                'user.driver',
                fn ($dq) => $dq->where('name', 'like', '%'.$search.'%')
                    ->orWhere('license_number', 'like', '%'.$search.'%')
            ))
            ->latest();

        $violations = $violationQuery
            ->paginate(10)
            ->withQueryString()
            ->through(function ($v) {
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
                    'paymentStatus' => $v->isPaid() ? ViolationLog::PAID : ViolationLog::UNPAID,
                    'outstanding' => $v->outstandingFine(),
                    'paidAt' => $v->paid_at?->format('d M, Y h:i A'),
                    'paymentUrl' => route('driver-manager.violations-log.payment', $v->id),
                    'penalty' => $v->additional_penalties,
                    'penaltyColor' => $this->getPenaltyColor($v->additional_penalties),
                ];
            });

        return view('driver-manager.violations-log', compact(
            'drivers',
            'violationCodes',
            'offenseCounts',
            'violations',
            'search',
            'from',
            'to'
        ));
    }

    public function storeViolationLog(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'vc_id' => 'required|exists:violation_codes,id',
            'violation_instance' => 'required|integer|min:1',
            'violation_fine' => 'required|numeric|min:0',
            'place_of_violation' => 'required|string|max:255',
            'date_of_violation' => 'required|date|before_or_equal:today',
            'time_of_violation' => 'required',
            'remarks' => 'nullable|string|max:500',
        ], [
            'user_id.required' => 'Select a driver.',
            'user_id.exists' => 'Select a driver from the list.',
            'vc_id.required' => 'Select the violation type.',
            'violation_instance.required' => 'Select the offense.',
            'place_of_violation.required' => 'Enter where it happened.',
            'date_of_violation.required' => 'Enter the date.',
            'date_of_violation.before_or_equal' => 'The date cannot be in the future.',
            'time_of_violation.required' => 'Enter the time.',
        ]);

        try {
            $violation = ViolationLog::create($validated);
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The violation was not logged.');
        }

        Notifier::send(User::find($violation->user_id), new ViolationRecorded($violation));

        return back()->with('success', 'Violation logged!');
    }

    /**
     * Mark a violation's fine as paid (or back to unpaid if it was a mistake).
     * The fine itself stays on record; once paid it no longer counts toward the
     * driver's outstanding balance.
     */
    public function updateViolationPayment(Request $request, ViolationLog $violation)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:'.ViolationLog::PAID.','.ViolationLog::UNPAID,
        ], [
            'payment_status.required' => 'Choose whether the fine is paid or unpaid.',
            'payment_status.in' => 'The payment status must be paid or unpaid.',
        ]);

        $paid = $validated['payment_status'] === ViolationLog::PAID;

        if ($violation->isPaid() === $paid) {
            return back()->with('error', 'This fine is already marked as '.($paid ? 'paid' : 'unpaid').'.');
        }

        try {
            $violation->update([
                'payment_status' => $validated['payment_status'],
                'paid_at' => $paid ? now() : null,
            ]);
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The payment status was not updated.');
        }

        if ($paid) {
            Notifier::send(User::find($violation->user_id), new ViolationPaid($violation));
        }

        return back()->with('success', $paid
            ? 'Fine marked as paid. The outstanding balance was reduced.'
            : 'Fine marked as unpaid again.');
    }

    public function storeViolationLogBulk(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'violations' => 'required|array|min:1',
            'violations.*.vc_id' => 'required|exists:violation_codes,id',
            'violations.*.violation_instance' => 'required|in:1,2,3,4',
            'violations.*.violation_fine' => 'required|numeric|min:0',
            'violations.*.place_of_violation' => 'required|string|max:255',
            'violations.*.date_of_violation' => 'required|date|before_or_equal:today',
            'violations.*.time_of_violation' => 'required',
            'violations.*.remarks' => 'nullable|string|max:500',
        ], [
            'violations.*.vc_id.required' => 'Select the violation type.',
            'violations.*.violation_instance.required' => 'Select the offense.',
            'violations.*.place_of_violation.required' => 'Enter where it happened.',
            'violations.*.date_of_violation.required' => 'Enter the date.',
            'violations.*.date_of_violation.before_or_equal' => 'The date cannot be in the future.',
            'violations.*.time_of_violation.required' => 'Enter the time.',
            'user_id.required' => 'Select a driver.',
            'user_id.exists' => 'Select a driver from the list.',
            'violations.required' => 'Add at least one violation entry.',
        ]);

        // All-or-nothing: a failure part-way through stores none of the entries.
        $created = [];

        try {
            DB::transaction(function () use ($request, &$created) {
                foreach ($request->violations as $v) {
                    $created[] = ViolationLog::create([
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
            });
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'No violations were logged.');
        }

        // One email per violation: each carries its own offense, fine and place.
        $driverUser = User::find($request->user_id);
        foreach ($created as $violation) {
            Notifier::send($driverUser, new ViolationRecorded($violation));
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
        if (Str::contains($lower, ['suspension', 'impound'])) {
            return 'red';
        }
        if (Str::contains($lower, ['warning', 'community'])) {
            return 'amber';
        }

        return 'muted';
    }

    /**
     * UCN_SC_E024 E3 — field messages that name the offense tiers, e.g.
     * "The first offense field is required." rather than "The first field is
     * required." (the form posts them as first / second / third / fourth_plus).
     */
    private function violationCodeMessages(): array
    {
        return [
            'first.numeric' => 'The first offense field must be a number.',
            'second.numeric' => 'The second offense field must be a number.',
            'third.numeric' => 'The third offense field must be a number.',
            'fourth_plus.numeric' => 'The fourth offense field must be a number.',
            'first.required' => 'The first offense field is required.',
            'second.required' => 'The second offense field is required.',
            'third.required' => 'The third offense field is required.',
            'fourth_plus.required' => 'The fourth offense field is required.',
            'first.min' => 'The first offense field must be at least 0.',
            'second.min' => 'The second offense field must be at least 0.',
            'third.min' => 'The third offense field must be at least 0.',
            'fourth_plus.min' => 'The fourth offense field must be at least 0.',
        ];
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
            'code' => 'required|string|max:255|unique:violation_codes,code',
            'name' => 'required|string|max:255',
            'first' => 'required|numeric|min:0',
            'second' => 'required|numeric|min:0',
            'third' => 'required|numeric|min:0',
            'fourth_plus' => 'required|numeric|min:0',
        ], $this->violationCodeMessages());

        try {
            $violationCode = ViolationCode::create([
                'code' => $request->code,
                'violation_name' => $request->name,
                'first_offense' => $request->first,
                'second_offense' => $request->second,
                'third_offense' => $request->third,
                'fourth_offense' => $request->fourth_plus,
            ]);
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The violation code was not added.');
        }

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
            'first' => 'required|numeric|min:0',
            'second' => 'required|numeric|min:0',
            'third' => 'required|numeric|min:0',
            'fourth_plus' => 'required|numeric|min:0',
        ], $this->violationCodeMessages());

        // UCN_SC_E024 E1 — no such violation code: error flash, nothing changes.
        $violationCode = ViolationCode::find($id);

        if (! $violationCode) {
            return back()->with('error', 'Error no violation code found.');
        }

        try {
            $violationCode->update([
                'code' => $request->code,
                'violation_name' => $request->name,
                'first_offense' => $request->first,
                'second_offense' => $request->second,
                'third_offense' => $request->third,
                'fourth_offense' => $request->fourth_plus,
            ]);
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The violation code was not updated.');
        }

        return back()->with('success', 'Violation code successfully updated!');
    }

    public function destroyViolationCode(string $id)
    {

        // E5 — Cannot delete in-use code: the code is referenced by violation logs.
        if (ViolationLog::where('vc_id', $id)->exists()) {
            return back()->with('error', 'This violation code is in use by existing violation logs and cannot be deleted.');
        }

        try {
            $violationCode = ViolationCode::destroy($id);
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The violation code was not deleted.');
        }

        // UCN_SC_E024 E1 — no such violation code.
        if (! $violationCode) {
            return back()->with('error', 'Error no violation code found.');
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
