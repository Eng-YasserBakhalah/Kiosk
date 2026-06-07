<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'request_id',
        'user_id',
        'terminal_device_id',
        'service_code',
        'error_type',
        'error_level',
        'error_code',
        'error_message',
        'source',
        'stack_trace',
    ];
}
