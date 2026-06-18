<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FareRate
 *
 * @property int $id
 * @property int $fare_id
 * @property int $km
 * @property float $regular
 * @property float $discount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fare $fare
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate query()
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereFareId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereRegular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FareRate extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'fare_id',
        'km',
        'regular',
        'discount'
    ];

    public function fare() {
        return $this->belongsTo(Fare::class);
    }
}
