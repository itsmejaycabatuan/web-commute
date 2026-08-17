<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FleetInventory
 *
 * @property int $id
 * @property int $vehicle_id
 * @property string|null $maintenance_cost
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Vehicle $vehicle
 * @method static \Illuminate\Database\Eloquent\Builder|FleetInventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FleetInventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FleetInventory query()
 * @method static \Illuminate\Database\Eloquent\Builder|FleetInventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FleetInventory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FleetInventory whereMaintenanceCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FleetInventory whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FleetInventory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FleetInventory whereVehicleId($value)
 * @mixin \Eloquent
 */
class FleetInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'maintenance_cost',
        'notes',
    ];

    protected $casts = [
        'maintenance_cost' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
