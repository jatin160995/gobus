<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPassenger extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'passenger_id',  // NEW — nullable, links to saved passenger
        'name',
        'phone',
        'gender',
        'seat_number',
        'id_number',     // NEW
    ];

    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}