<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fare extends Model
{
    use HasFactory;

    protected $fillable = [
        'location'
    ];

    public function rate() {
        return $this->hasMany(FareRate::class);
    }
}
