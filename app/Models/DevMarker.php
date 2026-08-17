<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DevMarker
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $lat
 * @property string $lng
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Driver $driver
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker query()
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereUserId($value)
 * @mixin \Eloquent
 */
class DevMarker extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'lat',
        'lng',
        'status',
    ];

    // app/Models/DevMarker.php
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'user_id', 'user_id');
    }
}
