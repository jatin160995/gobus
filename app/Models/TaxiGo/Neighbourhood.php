<?php

namespace App\Models\TaxiGo;

use Illuminate\Database\Eloquent\Model;

class Neighbourhood extends Model
{
    protected $table = 'taxigo_neighbourhoods';

    protected $fillable = ['zone_id', 'name', 'lat', 'lng', 'is_active'];

    protected $casts = [
        'lat'       => 'decimal:7',
        'lng'       => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}
