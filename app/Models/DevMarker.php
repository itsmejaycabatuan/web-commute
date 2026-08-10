<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
