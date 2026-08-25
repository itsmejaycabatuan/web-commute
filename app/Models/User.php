<?php

namespace App\Models;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
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
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements CanResetPassword, MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

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
    ];

    public function payment()
    {
        return $this->hasMany(Payment::class);
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function preference()
    {
        return $this->hasOne(UserPreference::class);
    }
}
