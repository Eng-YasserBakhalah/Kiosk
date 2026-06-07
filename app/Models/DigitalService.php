<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DigitalService extends Model
{
    use HasUuids;

    protected $fillable = [
        'service_code',
        'service_name',
        'category',
        'api_endpoint_key',
        'requires_otp',
        'requires_password',
        'requires_biometric',
        'enabled',
        'min_amount',
        'max_amount',
    ];

    protected $casts = [
        'requires_otp' => 'boolean',
        'requires_password' => 'boolean',
        'requires_biometric' => 'boolean',
        'enabled' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];
}
