<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'request_id',
        'idempotency_key',
        'user_id',
        'terminal_device_id',
        'branch_id',
        'service_id',
        'bank_reference',
        'transaction_type',
        'amount',
        'currency',
        'status',
        'response_code',
        'response_message',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
