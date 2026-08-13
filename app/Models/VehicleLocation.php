<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\VehicleLocation
 *
 * @property int $id
 * @property string $vehicle_id
 * @property int|null $user_id
 * @property string $latitude
 * @property string $longitude
 * @property string|null $accuracy
 * @property string|null $speed
 * @property string $last_update
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereLastUpdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereVehicleId($value)
 * @mixin \Eloquent
 */
class VehicleLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'latitude',
        'longitude',
        'speed',
        'accuracy',
        'last_update',
        'user_id',
    ];
}
