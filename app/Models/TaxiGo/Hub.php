<?php

namespace App\Models\TaxiGo;

use Illuminate\Database\Eloquent\Model;

class Hub extends Model
{
    protected $table = 'taxigo_hubs';

    protected $fillable = [
        'name', 'code', 'city', 'lat', 'lng',
        'night_start', 'night_end', 'night_surcharge',
        'vip_hourly_rate', 'vip_min_hours', 'is_active', 'sort',
    ];

    protected $casts = [
        'lat'             => 'decimal:7',
        'lng'             => 'decimal:7',
        'night_surcharge' => 'decimal:2',
        'vip_hourly_rate' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    public function zones()
    {
        return $this->hasMany(Zone::class, 'hub_id')->orderBy('sort');
    }

    public function interurbanDestinations()
    {
        return $this->hasMany(InterurbanDestination::class, 'hub_id')->orderBy('sort');
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class, 'hub_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
