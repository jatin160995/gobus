<?php

namespace App\Models\TaxiGo;

use Illuminate\Database\Eloquent\Model;

class SplitBeneficiary extends Model
{
    protected $table = 'taxigo_split_beneficiaries';

    protected $fillable = [
        'key', 'name', 'percent', 'channel', 'msisdn',
        'bank_name', 'bank_account_name', 'bank_account_number',
        'is_driver_share', 'is_active', 'sort',
    ];

    protected $casts = [
        'percent'         => 'decimal:2',
        'is_driver_share' => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort');
    }
}
