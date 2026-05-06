<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'trip_id',
        'schedule_id',
        'return_trip_id',
        'return_schedule_id',
        'offer_id',
        'booking_ref',
        'trip_type',
        'passenger_count',
        'base_price',
        'commission_amount',
        'platform_commission',
        'insurance_amount',
        'vat_amount',
        'total_amount',
        'discount_amount',
        'currency',
        'qr_code',
        'booking_status',
        'payment_status',
        'notes',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }

    public function returnTrip()
    {
        return $this->belongsTo(Trip::class, 'return_trip_id');
    }

    public function schedule()
    {
        return $this->belongsTo(TripSchedule::class, 'schedule_id');
    }

    public function returnSchedule()
    {
        return $this->belongsTo(TripSchedule::class, 'return_schedule_id');
    }

    public function bookingPassengers()
    {
        return $this->hasMany(BookingPassenger::class);
    }

    public function paymentOrder()
    {
        return $this->hasOne(PaymentOrder::class, 'booking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}