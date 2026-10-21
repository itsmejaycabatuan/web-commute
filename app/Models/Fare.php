<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Fare
 *
 * @property int $id
 * @property string $location
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FareRate> $rate
 * @property-read int|null $rate_count
 * @method static \Illuminate\Database\Eloquent\Builder|Fare newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Fare newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Fare query()
 * @method static \Illuminate\Database\Eloquent\Builder|Fare whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fare whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fare whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fare whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Fare extends Model
{
    use Audited;
    use HasFactory;

    protected $fillable = [
        'location'
    ];

    public function rate() {
        return $this->hasMany(FareRate::class);
    }

    // Individual fare rates are written in bulk with the query builder, so the controller logs those explicitly.
    protected function auditCategory(): string
    {
        return Audit::FARE;
    }

    protected function auditAttributes(): array
    {
        return ['location'];
    }

    protected function auditLabel(): string
    {
        return 'fare guide '.$this->location;
    }
}
