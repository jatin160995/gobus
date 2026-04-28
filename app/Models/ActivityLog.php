<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    //
     protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'reference_id',
        'ip_address',
        'user_agent',
        'description',
    ];

    public $timestamps = false; // table has only created_at

    // If your table uses created_at automatically but not updated_at
    const UPDATED_AT = null;

    /**
     * Connected User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
