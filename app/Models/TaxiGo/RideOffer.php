<?php

namespace App\Models\TaxiGo;

use Illuminate\Database\Eloquent\Model;

class RideOffer extends Model
{
    protected $table = 'taxigo_ride_offers';

    protected $fillable = ['ride_id', 'driver_id', 'status', 'sent_at', 'expires_at', 'responded_at'];

    protected $casts = [
        'sent_at'      => 'datetime',
        'expires_at'   => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function ride()
    {
        return $this->belongsTo(Ride::class, 'ride_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
