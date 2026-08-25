<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPreferenceController extends Controller
{
    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|in:light,dark',
        ]);

        UserPreference::updateOrCreate(
            ['user_id' => Auth::id()],
            ['theme' => $request->theme]
        );

        return response()->noContent();
    }

    public function updateFontSize(Request $request)
    {
        $request->validate([
            'font_size' => 'required|integer|in:10,11,12,13',
        ]);

        $pref = UserPreference::firstOrCreate(
            ['user_id' => auth()->id()],
            ['theme' => 'light', 'font_size' => 11]
        );

        $pref->update([
            'font_size' => $request->font_size,
        ]);

        return response()->json(['success' => true]);
    }
}
