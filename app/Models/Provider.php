<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
protected $table = 'providers';

    protected $fillable = [
        'name',
        'logo',
        'contact_person',
        'phone',
        'email',
        'address',
        'commission_rate',
        'payout_method',
        'status',
        'type',
        'orange_msisdn',
        'mtn_msisdn'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'provider_users', 'provider_id', 'user_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function routes()
    {
        return $this->hasMany(TravelRoute::class);
    }

    public function chauffeurVehicles()
    {
        return $this->hasMany(\App\Models\ChauffeurVehicle::class, 'provider_id');
    }

    public function chauffeurRoutes()
    {
        return $this->hasMany(\App\Models\ChauffeurRoute::class, 'provider_id');
    }

    public function chauffeurDrivers()
    {
        return $this->hasMany(\App\Models\ChauffeurDriver::class, 'provider_id');
    }

    public function chauffeurBookings()
    {
        return $this->hasMany(\App\Models\ChauffeurBooking::class, 'provider_id');
    }

}
