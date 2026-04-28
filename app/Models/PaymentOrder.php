<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentOrder extends Model
{
    protected $fillable = [
        'order_reference',
        'booking_id',
        'user_id',
        'provider_id',
        'total_amount',
        'currency',
        'payment_method',
        'payment_status',
        'gateway_transaction_id',
        'paid_at',
        'booking_type'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'payment_order_id');
    }
}