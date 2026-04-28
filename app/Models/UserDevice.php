<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id', 'device_name', 'device_os', 'device_model',
        'fcm_token', 'app_version', 'last_login_at'
    ];

    protected $table = 'user_devices';
}
