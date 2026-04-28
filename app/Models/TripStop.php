<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripStop extends Model
{
    protected $fillable = ['trip_id','city_id','arrival_time','departure_time','sequence'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
