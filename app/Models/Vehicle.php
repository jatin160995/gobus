<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'provider_id',
        'plate_number',
        'model',
        'photo',
        'layout', // ✅ added
        'capacity',
        'comfort_type'
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function images()
    {
        return $this->hasMany(VehicleImage::class);
    }
}
