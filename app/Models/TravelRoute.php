<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelRoute extends Model
{
    protected $table = 'routes'; // use existing table

    protected $fillable = [
        'provider_id',
        'departure_city_id',
        'arrival_city_id',
        'distance_km',
        'duration_minutes',
        'transport_type'
    ];

    public function departureCity()
    {
        return $this->belongsTo(City::class, 'departure_city_id');
    }

    public function arrivalCity()
    {
        return $this->belongsTo(City::class, 'arrival_city_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'route_id');
    }
}
