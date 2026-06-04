<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'device_id',
        'login_method',
        'ip_address',
        'success',
        'failure_reason',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];
}
