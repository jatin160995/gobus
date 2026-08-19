<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'transaction_reference',
        'payment_order_id',
        'booking_id',
        'transaction_type',
        'recipient_type',
        'recipient_id',
        'amount',
        'currency',
        'transaction_status',
        'booking_type',
        'payment_method',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function payoutAttempts()
    {
        return $this->hasMany(PayoutAttempt::class, 'payment_transaction_id');
    }

    public function order()
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_order_id');
    }
}