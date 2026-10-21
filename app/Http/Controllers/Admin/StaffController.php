<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Admin CRUD for staff accounts: the Driver Managers and Maintenance Managers
 * who run the back office. Admin accounts and commuters are not managed here.
 */
class StaffController extends Controller
{
    public const ROLES = [
        'driver_manager' => 'Driver Manager',
        'maintenance_manager' => 'Maintenance Manager',
    ];

    public function index(Request $request)
    {

        $search = trim((string) $request->query('search', ''));
        $role = $request->query('role');
        $status = $request->query('status');

        $staff = User::role(array_keys(self::ROLES))
            ->with('roles')
            ->when($search !== '', fn ($q) => $q->where('email', 'like', '%'.$search.'%'))
            ->when(array_key_exists((string) $role, self::ROLES), fn ($q) => $q->role($role))
            ->when($status === 'active', fn ($q) => $q->where('is_suspended', false))
            ->when($status === 'suspended', fn ($q) => $q->where('is_suspended', true))
            ->orderBy('email')
            ->paginate(10)
            ->withQueryString();

        return view('admin.staff.index', [
            'staff' => $staff,
            'roles' => self::ROLES,
            'filters' => ['search' => $search, 'role' => $role, 'status' => $status],
            'totals' => [
                'all' => User::role(array_keys(self::ROLES))->count(),
                'suspended' => User::role(array_keys(self::ROLES))->where('is_suspended', true)->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'password' => 'required|string|min:8|confirmed',
        ], $this->messages());

        try {
            $created = DB::transaction(function () use ($validated) {
                $user = User::create([
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    // Back-office accounts are created by an admin, not by e-mail sign-up.
                    'email_verified_at' => now(),
                ]);

                $user->syncRoles([$validated['role']]);

                return $user;
            });
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The staff account was not created.');
        }

        Audit::log(Audit::ACCOUNT, 'created', 'Created staff account '.$created->email, $created, [
            'role' => $validated['role'],
        ]);

        return redirect()->route('staff.index')->with('success', 'Staff account created.');
    }

    public function update(Request $request, User $user)
    {
        $this->assertStaff($user);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'password' => 'nullable|string|min:8|confirmed',
        ], $this->messages());

        $oldRole = $user->getRoleNames()->first();

        try {
            DB::transaction(function () use ($user, $validated) {
                $user->email = $validated['email'];

                if (! empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                }

                $user->save();
                $user->syncRoles([$validated['role']]);
            });
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The staff account was not updated.');
        }

        // The e-mail change is recorded by the model (Audited); role and password are not model columns.
        if ($oldRole !== $validated['role']) {
            Audit::log(Audit::ACCOUNT, 'role_changed', 'Changed role of '.$user->email, $user, [
                'old' => ['role' => $oldRole],
                'attributes' => ['role' => $validated['role']],
            ]);
        }
        if (! empty($validated['password'])) {
            Audit::log(Audit::AUTH, 'password_set_by_admin', 'Admin set a new password for '.$user->email, $user);
        }

        return redirect()->route('staff.index')->with('success', 'Staff account updated.');
    }

    public function toggleSuspension(User $user)
    {
        $this->assertStaff($user);

        if ($user->id === auth()->id()) {
            return redirect()->route('staff.index')->with('error', 'You cannot suspend your own account.');
        }

        try {
            if ($user->is_suspended) {
                $user->unsuspend();
                Audit::log(Audit::ACCOUNT, 'reactivated', 'Reactivated staff account '.$user->email, $user);

                return redirect()->route('staff.index')->with('success', $user->email.' has been reactivated.');
            }

            $user->suspend();
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The staff account was not changed.');
        }

        Audit::log(Audit::ACCOUNT, 'suspended', 'Suspended staff account '.$user->email, $user);

        return redirect()->route('staff.index')->with('success', $user->email.' has been suspended.');
    }

    public function destroy(User $user)
    {
        $this->assertStaff($user);

        if ($user->id === auth()->id()) {
            return redirect()->route('staff.index')->with('error', 'You cannot delete your own account.');
        }

        $role = $user->getRoleNames()->first();

        $email = $user->email;

        try {
            // Staff normally have no payments; if one does, the account is anonymised, not deleted.
            $outcome = $user->eraseAccount();
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The staff account was not removed.');
        }

        Audit::log(Audit::ACCOUNT, $outcome, ($outcome === 'anonymised' ? 'Anonymised staff account ' : 'Deleted staff account ').$email, $user, [
            'email' => $email,
            'role' => $role,
            'outcome' => $outcome,
        ]);

        return redirect()->route('staff.index')->with('success', 'Staff account removed.');
    }

    private function messages(): array
    {
        return [
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role.in' => 'Choose Driver Manager or Maintenance Manager.',
        ];
    }

    private function writeFailed(QueryException $e, string $detail)
    {
        report($e);

        return redirect()
            ->route('staff.index')
            ->withInput()
            ->with('error', 'A database error occurred. '.$detail);
    }

    /** Only back-office staff accounts are reachable through this controller. */
    private function assertStaff(User $user): void
    {
        if (! $user->hasAnyRole(array_keys(self::ROLES))) {
            abort(404);
        }
    }
}
