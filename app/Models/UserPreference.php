<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserPreference
 *
 * @property int $id
 * @property int $user_id
 * @property string $theme
 * @property int $font_size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|UserPreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserPreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserPreference query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserPreference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPreference whereFontSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPreference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPreference whereTheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPreference whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPreference whereUserId($value)
 * @mixin \Eloquent
 */
class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme',
        'font_size',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
