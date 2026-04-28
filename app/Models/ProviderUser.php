<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderUser extends Model
{
    protected $fillable = ['provider_id', 'user_id', 'role'];

    public function provider()
    {
        return $this->belongsTo(\App\Models\Provider::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
