<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CommuterController extends Controller
{
    public function index()
    {
        $commuters = User::role('commuter')->orderByDesc('created_at')->get();

        return view('admin.commuters.index', compact('commuters'));
    }

    public function create()
    {
        return view('admin.commuters.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => $request->boolean('mark_verified') ? now() : null,
            'driver_approval_status' => null,
        ]);

        $user->syncRoles(['commuter']);

        return redirect()
            ->route('admin.commuters.index')
            ->with('success', 'Commuter account created.');
    }

    public function edit(User $user)
    {
        $this->assertCommuter($user);

        return view('admin.commuters.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->assertCommuter($user);

        $validated = $request->validate([
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->email = $validated['email'];
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->email_verified_at = $request->boolean('mark_verified') ? now() : null;
        $user->save();

        return redirect()
            ->route('admin.commuters.index')
            ->with('success', 'Commuter updated.');
    }

    public function destroy(User $user)
    {
        $this->assertCommuter($user);

        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.commuters.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.commuters.index')
            ->with('success', 'Commuter removed.');
    }

    private function assertCommuter(User $user): void
    {
        if (! $user->hasRole('commuter')) {
            abort(404);
        }
    }
}
