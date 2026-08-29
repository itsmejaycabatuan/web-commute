<?php

namespace App\Http\Controllers;

use App\Exports\UserDataExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $userId = Auth::user()->id;
        $role = $user->roles->first()->name;

        if ($role == 'commuter') {
            return view('commuter.settings');
        }

        return view('settings');
    }

    public function update(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        Auth::user()->update($request->only('email'));

        return back()->with('success', 'Email updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function logoutOtherDevices(Request $request)
    {
        Auth::logoutOtherDevices($request->password());

        return back()->with('success', 'All other sessions have been terminated.');
    }

    public function exportData()
    {
        $user = Auth::user();
        $fileName = 'smartcommute_data_export_' . $user->id . '_' . now()->format('Y_m_d_His') . '.xlsx';

        return (new UserDataExport($user))->download($fileName);
    }
}
