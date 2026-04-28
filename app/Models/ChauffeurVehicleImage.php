<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChauffeurVehicleImage extends Model
{
    protected $table = 'chauffeur_vehicle_images';

    protected $fillable = [
        'chauffeur_vehicle_id',
        'image_type',
        'image_path',
        'sort_order',
        'is_active'
    ];

    public function vehicle()
    {
        return $this->belongsTo(ChauffeurVehicle::class, 'chauffeur_vehicle_id');
    }
}