<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'cities';

   protected $fillable = [
        'name',
        'city_code',
        'country',
    ];

    public $timestamps = true; // your table has created_at

    public function departureRoutes()
    {
        return $this->hasMany(TravelRoute::class, 'departure_city_id');
    }

    public function arrivalRoutes()
    {
        return $this->hasMany(TravelRoute::class, 'arrival_city_id');
    }
}
