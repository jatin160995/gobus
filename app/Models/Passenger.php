<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'gender',
        'id_number',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookingPassengers()
    {
        return $this->hasMany(BookingPassenger::class);
    }
}