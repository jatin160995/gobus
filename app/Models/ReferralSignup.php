<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralSignup extends Model
{
    protected $fillable = ['partner_id', 'user_id', 'phone', 'commission_amount', 'status', 'paid_at', 'paid_by_user_id'];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'paid_at'           => 'datetime',
    ];

    public function partner()
    {
        return $this->belongsTo(ReferralPartner::class, 'partner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
