<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopupHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount_added',
        'payment_method'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function wallet() {
        return $this->belongsTo(Wallet::class);
    }
}
