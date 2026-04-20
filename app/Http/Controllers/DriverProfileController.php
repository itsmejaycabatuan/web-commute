<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverProfileController extends Controller
{
    public function show()
    {
        return view('driverprofile', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        if (! $request->filled('password')) {
            return redirect()
                ->route('driverprofile')
                ->with('info', 'Enter a new password to update, or leave both fields empty.');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()
            ->route('driverprofile')
            ->with('success', 'Password updated successfully.');
    }
}
