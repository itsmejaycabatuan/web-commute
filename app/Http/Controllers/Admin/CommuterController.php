<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CommuterController extends Controller
{
    public function index()
    {
        activity()->event('Index')->log('Action performed: index');

        $commuters = User::role('commuter')
            ->with('wallet')
            ->withCount(['payment as fares_paid'])
            ->orderByDesc('created_at')
            ->get();

        $balances = Wallet::whereIn('user_id', $commuters->pluck('id'))->pluck('balance', 'user_id');

        return view('admin.commuters.index', compact('commuters', 'balances'));
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
        ]);

        $user->syncRoles(['commuter']);

        return redirect()
            ->route('commuters.index')
            ->with('success', 'Commuter account created.');
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
            ->route('commuters.index')
            ->with('success', 'Commuter updated.');
    }

    public function destroy(User $user)
    {
        $this->assertCommuter($user);

        if ($user->id === auth()->id()) {
            return redirect()
                ->route('commuters.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // E8 — Cannot delete commuter with balance: the wallet still holds money,
        // so the account must be settled before it can be removed.
        $balance = (float) ($user->wallet?->balance ?? 0);

        if ($balance > 0) {
            return redirect()
                ->route('commuters.index')
                ->with('error', 'Cannot delete commuter with a balance of PHP '.number_format($balance, 2).'. Settle the wallet first.');
        }

        $user->delete();

        return redirect()
            ->route('commuters.index')
            ->with('success', 'Commuter removed.');
    }

    /**
     * A4 — suspend / unsuspend a commuter. A suspended account cannot sign in but
     * keeps its wallet and receipts for auditing.
     */
    public function toggleSuspension(User $user)
    {
        $this->assertCommuter($user);

        if ($user->id === auth()->id()) {
            return redirect()
                ->route('commuters.index')
                ->with('error', 'You cannot suspend your own account.');
        }

        if ($user->isSuspended()) {
            $user->unsuspend();

            return redirect()
                ->route('commuters.index')
                ->with('success', $user->email.' has been reactivated.');
        }

        $user->suspend();

        activity()->causedBy(auth()->user())
            ->event('Suspend Commuter')
            ->log('Suspended commuter: '.$user->email);

        return redirect()
            ->route('commuters.index')
            ->with('success', $user->email.' has been suspended.');
    }

    /**
     * A5 — read-only commuter details (balance, fares paid, last activity).
     */
    public function show(User $user)
    {
        $this->assertCommuter($user);

        return view('admin.commuters.show', [
            'user' => $user->loadCount(['payment as fares_paid']),
            'balance' => (float) ($user->wallet?->balance ?? 0),
            'lastFare' => Payment::where('paid_by', $user->id)->latest('paid_at')->first(),
            'totalFareSpent' => (float) Payment::where('paid_by', $user->id)->sum('price'),
            // Trip History — every fare this commuter has paid, newest first.
            'trips' => $user->payment()->latest('paid_at')->get(),
            // Payment History — wallet top-ups/reloads made by this commuter.
            'topups' => $user->topupHistories()->latest()->get(),
            'totalToppedUp' => (float) $user->topupHistories()->sum('amount_added'),
        ]);
    }

    private function assertCommuter(User $user): void
    {
        if (! $user->hasRole('commuter')) {
            abort(404);
        }
    }
}
