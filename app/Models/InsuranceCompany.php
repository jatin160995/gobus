<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    protected $fillable = [
        'name',
        'orange_msisdn',
        'contact_person',
        'phone',
        'email',
        'address',
        'user_id',
        'status',
    ];
}