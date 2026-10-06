<?php

namespace App\Models\TaxiGo;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $table = 'taxigo_drivers';

    protected $fillable = [
        'user_id', 'hub_id', 'photo', 'licence_number',
        'payout_channel', 'payout_msisdn', 'is_active',
        'is_online', 'last_lat', 'last_lng', 'last_location_at', 'went_online_at',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'is_online'        => 'boolean',
        'last_lat'         => 'decimal:7',
        'last_lng'         => 'decimal:7',
        'last_location_at' => 'datetime',
        'went_online_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class, 'hub_id');
    }

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class, 'driver_id');
    }

    public function rides()
    {
        return $this->hasMany(Ride::class, 'driver_id');
    }

    public function offers()
    {
        return $this->hasMany(RideOffer::class, 'driver_id');
    }
}
