<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OtpRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'phone_masked',
        'purpose',
        'otp_hash',
        'attempts',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
