<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChauffeurDriver extends Model
{
    use HasFactory;

    protected $table = 'chauffeur_drivers';

    protected $fillable = [
        'provider_id',
        'name',
        'phone',
        'license_number',
        'license_expiry',
        'rating',
        'is_active',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'rating' => 'decimal:2',
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

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeByProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}