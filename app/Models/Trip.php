<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'provider_id','route_id','vehicle_id','departure_datetime','price',
        'seats_total','seats_available','comfort_type','status','transport_type',
        'start_date','end_date','recurrence','weekdays', "round_trip_price",'child_price',
    ];

    protected $casts = [
        'weekdays' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'departure_datetime' => 'datetime',
        'price'              => 'float',         
        'round_trip_price'   => 'float',         
    ];

    public function route()
    {
        return $this->belongsTo(\App\Models\TravelRoute::class, 'route_id');
    }


    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function stops()
    {
        return $this->hasMany(TripStop::class)->orderBy('sequence');
    }

    public function schedules()
    {
        return $this->hasMany(TripSchedule::class);
    }
     public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
