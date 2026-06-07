<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ApiIntegrationLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'request_id',
        'service_id',
        'user_id',
        'terminal_device_id',
        'external_api_name',
        'endpoint_key',
        'http_method',
        'response_status',
        'bank_response_code',
        'duration_ms',
        'success',
        'error_message',
        'masked_request',
        'masked_response',
    ];

    protected $casts = [
        'success' => 'boolean',
        'masked_request' => 'array',
        'masked_response' => 'array',
    ];
}
