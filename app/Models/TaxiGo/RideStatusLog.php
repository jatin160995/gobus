<?php

namespace App\Models\TaxiGo;

use Illuminate\Database\Eloquent\Model;

class RideStatusLog extends Model
{
    protected $table = 'taxigo_ride_status_logs';

    protected $fillable = ['ride_id', 'from_status', 'to_status', 'actor_type', 'actor_id', 'lat', 'lng', 'note'];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function ride()
    {
        return $this->belongsTo(Ride::class, 'ride_id');
    }
}
