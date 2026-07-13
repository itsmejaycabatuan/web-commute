<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vc_id',
        'violation_instance',
        'violation_fine',
        'additional_penalties',
        'date_of_violation',
        'time_of_violation',
        'place_of_violation',
        'remarks',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function violationCode()
    {
        return $this->belongsTo(ViolationCode::class, 'vc_id');
    }
}
