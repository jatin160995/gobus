<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChauffeurRoutePrice extends Model
{
    use HasFactory;

    protected $table = 'chauffeur_route_prices';

    protected $fillable = [
        'chauffeur_route_id',
        'vehicle_category',
        'one_way_price',
        'round_trip_price',
        'per_day_price',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'one_way_price' => 'decimal:2',
        'round_trip_price' => 'decimal:2',
        'per_day_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function route()
    {
        return $this->belongsTo(ChauffeurRoute::class, 'chauffeur_route_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('vehicle_category', $category);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedOneWayPriceAttribute()
    {
        return number_format($this->one_way_price, 2) . ' ' . $this->currency;
    }

    public function getFormattedRoundTripPriceAttribute()
    {
        return number_format($this->round_trip_price, 2) . ' ' . $this->currency;
    }
   

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

   public function getPriceByTripType($tripType, $days = 1)
    {
        if ($tripType === 'one_way') {
            return $this->one_way_price;
        }

        if ($tripType === 'round_trip') {

            if ($days <= 1) {
                return $this->round_trip_price;
            }

            return $this->per_day_price * $days;
        }

        return 0;
    }
}