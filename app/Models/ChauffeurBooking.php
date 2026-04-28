<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChauffeurBooking extends Model
{
    protected $table = 'chauffeur_bookings';

    protected $fillable = [
        'booking_reference',
        'user_id',
        'provider_id',
        'chauffeur_route_id',
        'chauffeur_vehicle_id',
        'chauffeur_driver_id',
        'trip_type',
        'pickup_datetime',
        'return_datetime',
        'pickup_address',
        'drop_address',
        'base_price',
        'total_price',
        'currency',
        'status',
        'payment_status'
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function chauffeurRoute()
    {
        return $this->belongsTo(\App\Models\ChauffeurRoute::class, 'chauffeur_route_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}