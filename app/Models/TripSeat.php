<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripSeat extends Model
{
    protected $table = 'trip_seats';

    protected $fillable = [
        'schedule_id', 'seat_number', 'status'
    ];

    public $timestamps = true;

    public function schedule()
    {
        return $this->belongsTo(TripSchedule::class, 'schedule_id');
    }
}
