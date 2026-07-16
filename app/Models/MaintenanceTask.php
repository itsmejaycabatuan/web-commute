<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'tasks_performed',
        'miles_between_service',
        'months_between_service',
    ];
}
