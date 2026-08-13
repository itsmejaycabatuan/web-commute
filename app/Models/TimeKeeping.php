<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeKeeping extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'date',
        'time_in',
        'time_out',
        'hours_worked',
        'overtime_hours',
        'sick',
        'vacation',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
