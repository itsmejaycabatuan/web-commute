<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
