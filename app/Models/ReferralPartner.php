<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralPartner extends Model
{
    protected $fillable = ['name', 'phone', 'code', 'payout_channel', 'payout_msisdn', 'user_id', 'is_active', 'notes'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function signups()
    {
        return $this->hasMany(ReferralSignup::class, 'partner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
