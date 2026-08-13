<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TopupHistory
 *
 * @property int $id
 * @property int $user_id
 * @property int $wallet_id
 * @property string $amount_added
 * @property string $payment_method
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Wallet $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereAmountAdded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereWalletId($value)
 * @mixin \Eloquent
 */
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
