<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChauffeurVehicle extends Model
{
    protected $table = 'chauffeur_vehicles';

    protected $fillable = [
        'provider_id',
        'brand',
        'model',
        'year',
        'category',
        'seats',
        'plate_number',
        'color',
        'fuel_type',
        'transmission',
        'is_active'
    ];
   

    public function images()
    {
        return $this->hasMany(ChauffeurVehicleImage::class, 'chauffeur_vehicle_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}