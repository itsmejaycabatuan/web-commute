<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Support\Audit;
use App\Models\Wallet;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CommuterController extends Controller
{
    public function index()
    {

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
        ], $this->passwordMessages());

        // E3 — the account and its role are written together or not at all; the
        // Admin is told when the database refuses.
        try {
            $created = DB::transaction(function () use ($validated, $request) {
                $user = User::create([
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'email_verified_at' => $request->boolean('mark_verified') ? now() : null,
                ]);

                $user->syncRoles(['commuter']);

                return $user;
            });
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The commuter account was not created.');
        }

        Audit::log(Audit::ACCOUNT, 'created', 'Created commuter account '.$created->email, $created, [
            'role' => 'commuter',
            'source' => 'admin',
        ]);

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
        ], $this->passwordMessages());

        try {
            $user->email = $validated['email'];
            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            $user->email_verified_at = $request->boolean('mark_verified') ? now() : null;
            $user->save();
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The commuter was not updated.');
        }

        // E-mail / verification changes are recorded by the model (Audited); the password is not a model column.
        if (! empty($validated['password'])) {
            Audit::log(Audit::AUTH, 'password_set_by_admin', 'Admin set a new password for '.$user->email, $user);
        }

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

        $email = $user->email;

        try {
            // A commuter with payments or top-ups is anonymised, not deleted, so the ledger survives.
            $outcome = $user->eraseAccount();
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The commuter was not removed.');
        }

        $anonymised = $outcome === 'anonymised';
        Audit::log(Audit::ACCOUNT, $outcome, ($anonymised ? 'Anonymised commuter account ' : 'Deleted commuter account ').$email, $user, [
            'email' => $email,
            'role' => 'commuter',
            'wallet_balance' => $balance,
            'outcome' => $outcome,
        ]);

        return redirect()
            ->route('commuters.index')
            ->with('success', $anonymised
                ? 'Commuter removed. Their payments and top-ups were kept for the records.'
                : 'Commuter removed.');
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

        try {
            if ($user->isSuspended()) {
                $user->unsuspend();
                Audit::log(Audit::ACCOUNT, 'reactivated', 'Reactivated commuter account '.$user->email, $user);

                return redirect()
                    ->route('commuters.index')
                    ->with('success', $user->email.' has been reactivated.');
            }

            $user->suspend();
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The commuter account was not changed.');
        }

        Audit::log(Audit::ACCOUNT, 'suspended', 'Suspended commuter account '.$user->email, $user);

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
            'trips' => $user->payment()->latest('paid_at')->paginate(10, ['*'], 'trips_page')->withQueryString(),
            // Payment History — wallet top-ups/reloads made by this commuter.
            'topups' => $user->topupHistories()->latest()->paginate(10, ['*'], 'topups_page')->withQueryString(),
            'totalToppedUp' => (float) $user->topupHistories()->completed()->sum('amount_added'),
        ]);
    }

    /**
     * UCN_SC_E018 E6 — the minimum-length message is worded without "field".
     */
    private function passwordMessages(): array
    {
        return [
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }

    /**
     * UCN_SC_E018 E3 — a failed write is logged, cancelled and reported to the Admin.
     */
    private function writeFailed(QueryException $e, string $detail)
    {
        report($e);

        return redirect()
            ->route('commuters.index')
            ->withInput()
            ->with('error', 'A database error occurred. '.$detail);
    }

    private function assertCommuter(User $user): void
    {
        if (! $user->hasRole('commuter')) {
            abort(404);
        }
    }
}
