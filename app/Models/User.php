<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Support\Audit;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $license_number
 * @property string|null $license_code
 * @property string|null $license_image_path
 * @property string|null $license_image_data
 * @property string|null $license_image_mime
 * @property string|null $driver_approval_status
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Payment> $payment
 * @property-read int|null $payment_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDriverApprovalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLicenseCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLicenseImageData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLicenseImageMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLicenseImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 *
 * @property string|null $expiration_date
 * @property string|null $contact_info
 *
 * @method static \Illuminate\Database\Eloquent\Builder|User whereContactInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereExpirationDate($value)
 *
 * @property string|null $driver_code
 *
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDriverCode($value)
 *
 * @property-read Driver|null $driver
 * @property-read UserPreference|null $preference
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements CanResetPassword, MustVerifyEmail
{
    use Audited;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    // Creation is logged explicitly (Audit::log) so the entry can say how and by whom.
    protected static $recordEvents = ['updated', 'deleted'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'driver_code',
        'email',
        'contact_info',
        'password',
        'license_number',
        'license_code',
        'expiration_date',
        'license_image_path',
        'license_image_data',
        'license_image_mime',
        'driver_approval_status',
        'email_verified_at',
        'is_suspended',
        'suspended_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'license_image_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
        'anonymised_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->hasMany(Payment::class, 'paid_by');
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function topupHistories()
    {
        return $this->hasMany(TopupHistory::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function preference()
    {
        return $this->hasOne(UserPreference::class);
    }

    /**
     * End every session this account has open except the one in use now. Only
     * the database session driver keeps sessions per user, so any other driver
     * has nothing to remove here.
     */
    public function forgetOtherSessions(?string $keepSessionId): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->id)
            ->when($keepSessionId, fn ($query) => $query->where('id', '!=', $keepSessionId))
            ->delete();
    }

    /**
     * UCN_SC_E018 (commuters) A4 and UCN_SC_E012 (drivers) — an account is
     * suspended either on the user record (commuter) or on the driver profile.
     */
    public function isSuspended(): bool
    {
        if ($this->is_suspended) {
            return true;
        }

        $driver = $this->relationLoaded('driver') ? $this->driver : $this->driver()->first();

        return (bool) $driver?->is_suspended;
    }

    public function suspend(): void
    {
        $this->forceFill([
            'is_suspended' => true,
            'suspended_at' => now(),
        ])->save();
    }

    public function unsuspend(): void
    {
        $this->forceFill([
            'is_suspended' => false,
            'suspended_at' => null,
        ])->save();
    }

    // ─────────────────────────── account removal ───────────────────────────

    /** The account was erased but kept as an empty shell because payments or top-ups point at it. */
    public function isAnonymised(): bool
    {
        return $this->anonymised_at !== null;
    }

    /** Payments and top-ups are financial records: they must outlive the account that made them. */
    public function hasFinancialHistory(): bool
    {
        return $this->payment()->exists() || $this->topupHistories()->exists();
    }

    /**
     * Remove an account without destroying the ledger: with nothing financial attached it is deleted
     * outright; with payments or top-ups it is anonymised instead. UCN_SC_E027 / E018 / E012 / E028.
     *
     * Callers audit the action themselves (they know the actor), so the model's own events are off here.
     *
     * @return string 'deleted' or 'anonymised'
     */
    public function eraseAccount(): string
    {
        $this->disableLogging();

        if (! $this->hasFinancialHistory()) {
            $this->delete();

            return 'deleted';
        }

        $this->anonymise();

        return 'anonymised';
    }

    /**
     * Strip everything personal and everything that grants access, and keep the row so the payments,
     * top-ups and wallet that reference it stay intact and auditable.
     *
     * Gone: email (replaced by a placeholder, which also frees the address), password, remember token,
     * verification, roles, API tokens, sessions, preferences, reset links, map markers and the driver
     * profile with its licence image. Kept: the user row (id), wallet, payments, top-ups and any
     * violation logs. The account can never sign in again: it is suspended, has no roles and an
     * unknown password.
     */
    public function anonymise(): void
    {
        $this->disableLogging();

        DB::transaction(function () {
            $id = $this->id;
            $oldEmail = $this->email;

            if ($driver = $this->driver()->first()) {
                // Release the vehicle first: deleting the driver would cascade into the fleet record.
                $driver->vehicle()->update(['driver_id' => null]);

                if ($driver->license_image_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($driver->license_image_path);
                }

                $driver->disableLogging()->delete();
            }

            $this->tokens()->delete();
            UserPreference::where('user_id', $id)->delete();
            DevMarker::where('user_id', $id)->delete();
            DB::table('password_resets')->where('email', $oldEmail)->delete();
            DB::table(config('session.table', 'sessions'))->where('user_id', $id)->delete();
            $this->syncRoles([]);

            $this->forceFill([
                'email' => "deleted-{$id}@deleted.invalid",
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(64)),
                'remember_token' => null,
                'email_verified_at' => null,
                'is_suspended' => true,
                'suspended_at' => now(),
                'anonymised_at' => now(),
            ])->save();
        });
    }

    // Creation, suspension, role changes and password changes are logged explicitly (Audit::log) because they need context a model event does not have.
    protected function auditCategory(): string
    {
        return Audit::ACCOUNT;
    }

    protected function auditAttributes(): array
    {
        return ['email', 'email_verified_at'];
    }

    protected function auditLabel(): string
    {
        return 'user '.$this->email;
    }
}
