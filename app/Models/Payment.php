<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'paid_by',
        'starting_point',
        'destination',
        'total_distance',
        'is_discounted',
        'payment_method',
        'price',
        'transaction_id',
        'paid_at'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
