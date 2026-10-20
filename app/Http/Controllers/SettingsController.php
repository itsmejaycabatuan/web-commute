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

        // A driver's e-mail address is part of the account the administrator
        // approved, so only an administrator can change it.
        if (Auth::user()->hasRole('driver')) {
            return back()->with('error', 'Drivers cannot change their email address. Contact the administrator.');
        }

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
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
            'password.different' => 'The new password must be different from your current password.',
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
            Auth::user()->forgetOtherSessions($request->session()->getId());
        } catch (\Exception $e) {
            activity()->event('Updatepassword')->log('Database error during password change.');

            return back()->with('error', 'Password could not be changed. Please try again later.');
        }

        return back()->with('success', 'Password changed successfully.');
    }

    /**
     * Sign the account out everywhere except the device it is used on now.
     *
     * The current password is required so a hijacked session cannot be used to
     * lock the real owner out. With database sessions the other sessions are
     * removed outright; logoutOtherDevices() additionally invalidates any
     * remember-me cookie.
     */
    public function logoutOtherDevices(Request $request)
    {
        activity()->event('Logoutotherdevices')->log('Action performed: logoutOtherDevices');

        $request->validate([
            'current_password' => 'required|current_password',
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
        ]);

        $user = Auth::user();

        try {
            Auth::logoutOtherDevices($request->current_password);

            $user->forgetOtherSessions($request->session()->getId());
        } catch (\Exception $e) {
            report($e);

            return back()->with('error', 'Other devices could not be signed out. Please try again later.');
        }

        return back()->with('success', 'You have been signed out of all other devices.');
    }
}
