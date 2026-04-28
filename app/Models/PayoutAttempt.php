<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutAttempt extends Model
{
    protected $fillable = [
        'attempt_reference',
        'payment_transaction_id',
        'payout_type',
        'recipient_id',
        'amount',
        'currency',
        'gateway_name',
        'gateway_reference',
        'attempt_number',
        'status',
        'failure_reason',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}