<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChauffeurRoute extends Model
{
    use HasFactory;

    protected $table = 'chauffeur_routes';

    protected $fillable = [
        'provider_id',
        'from_city_id',
        'to_city_id',
        'distance_km',
        'estimated_duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'estimated_duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function fromCity()
    {
        return $this->belongsTo(City::class, 'from_city_id');
    }

    public function toCity()
    {
        return $this->belongsTo(City::class, 'to_city_id');
    }

    public function prices()
    {
        return $this->hasMany(ChauffeurRoutePrice::class, 'chauffeur_route_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor
    |--------------------------------------------------------------------------
    */

    public function getRouteNameAttribute()
    {
        return optional($this->fromCity)->name
            . ' → ' .
            optional($this->toCity)->name;
    }
}