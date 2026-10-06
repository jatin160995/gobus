<?php

namespace App\Models\TaxiGo;

use Illuminate\Database\Eloquent\Model;

class InterurbanDestination extends Model
{
    protected $table = 'taxigo_interurban_destinations';

    protected $fillable = ['hub_id', 'name', 'fare', 'is_active', 'sort'];

    protected $casts = [
        'fare'      => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function hub()
    {
        return $this->belongsTo(Hub::class, 'hub_id');
    }
}
