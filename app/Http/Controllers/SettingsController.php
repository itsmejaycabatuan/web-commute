<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function edit()
    {
        activity()->event('Edit')->log('Action performed: edit');
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
        activity()->event('Update')->log('Action performed: update');
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        Auth::user()->update($request->only('email'));

        return back()->with('success', 'Email updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        activity()->event('Updatepassword')->log('Action performed: updatePassword');
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            Auth::user()->update([
                'password' => Hash::make($request->password),
            ]);

            // A password change invalidates every other active session/token, so
            // a hijacked session cannot outlive the credentials it was opened
            // with. The current device keeps its session.
            //
            // NOTE: logoutOtherDevices() *re-saves* the password it is given, so
            // it must be handed the NEW plaintext — passing the old one would
            // throw (the record has already changed) or revert the change.
            Auth::logoutOtherDevices($request->password);
        } catch (\Exception $e) {
            activity()->event('Updatepassword')->log('Database error during password change.');

            return back()->with('error', 'Password could not be changed. Please try again later.');
        }

        return back()->with('success', 'Password changed successfully.');
    }

    public function logoutOtherDevices(Request $request)
    {
        activity()->event('Logoutotherdevices')->log('Action performed: logoutOtherDevices');
        Auth::logoutOtherDevices($request->password());

        return back()->with('success', 'All other sessions have been terminated.');
    }


}
