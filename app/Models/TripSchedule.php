<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripSchedule extends Model
{
    protected $table = 'trip_schedules';

    protected $fillable = [
        'trip_id','date','departure_datetime','seats_available','status'
    ];

    protected $dates = ['date','departure_datetime'];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function seats()
    {
        return $this->hasMany(TripSeat::class, 'schedule_id');
    }
}
