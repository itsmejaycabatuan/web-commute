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
}
