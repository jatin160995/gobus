<?php

namespace App\Models\TaxiGo;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $table = 'taxigo_vehicles';

    protected $fillable = ['model', 'plate', 'color', 'seats', 'photo', 'driver_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
