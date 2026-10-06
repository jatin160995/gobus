<?php

namespace App\Models\TaxiGo;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $table = 'taxigo_zones';

    protected $fillable = ['hub_id', 'code', 'name', 'fare', 'polygon', 'is_active', 'sort'];

    protected $casts = [
        'fare'      => 'decimal:2',
        'polygon'   => 'array',
        'is_active' => 'boolean',
    ];

    public function hub()
    {
        return $this->belongsTo(Hub::class, 'hub_id');
    }

    public function neighbourhoods()
    {
        return $this->hasMany(Neighbourhood::class, 'zone_id')->orderBy('name');
    }
}
