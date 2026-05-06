<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPassenger extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'name',
        'phone',
        'gender',
        'id_number',
        'seat_number',
        'trip_seat_id',
        'return_seat_number',
        'return_trip_seat_id',
        'ticket_number',
    ];

    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    public function seat()
    {
        return $this->belongsTo(TripSeat::class, 'trip_seat_id');
    }

    public function returnSeat()
    {
        return $this->belongsTo(TripSeat::class, 'return_trip_seat_id');
    }
}