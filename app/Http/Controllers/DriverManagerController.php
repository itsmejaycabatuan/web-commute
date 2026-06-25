<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ViolationCode;
use Auth;
use Illuminate\Http\Request;

class DriverManagerController extends Controller
{
    public function timeKeeping()
    {
        return view('driver-manager.time-keeping');
    }

    public function violationsLog()
    {
        return view('driver-manager.violations-log');
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
