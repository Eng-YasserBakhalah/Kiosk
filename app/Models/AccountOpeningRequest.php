<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AccountOpeningRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'request_id',
        'user_id',
        'terminal_device_id',
        'branch_id',
        'tracking_number',
        'bank_reference',
        'account_type',
        'currency',
        'full_name',
        'phone_masked',
        'national_id_masked',
        'address',
        'income_source',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
