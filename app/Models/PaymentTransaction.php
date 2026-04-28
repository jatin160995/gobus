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
        'booking_type'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}