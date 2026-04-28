<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVerification extends Model
{
    protected $fillable = [
        'user_id', 'otp_code', 'expires_at', 'verified_at'
    ];

    public $timestamps = true;

    protected $table = "user_verifications";
}
