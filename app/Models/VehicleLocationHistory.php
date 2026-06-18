<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\VehicleLocationHistory
 *
 * @property int $id
 * @property int $vehicle_location_id
 * @property int|null $user_id
 * @property string $distance_from_last_pos
 * @property string $latitude
 * @property string $longitude
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\VehicleLocation|null $location
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereDistanceFromLastPos($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereVehicleLocationId($value)
 * @mixin \Eloquent
 */
class VehicleLocationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_location_id',
        'longitude',
        'latitude',
        'created_at',
        'user_id',
        'distance_from_last_pos'
    ];

    public function location() {
        return $this->belongsTo(VehicleLocation::class);
    }
}
