<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TerminalDevice extends Model
{
    use HasUuids;

    protected $keyType = 'uuid';

    protected $fillable = [
        'branch_id',
        'device_code',
        'serial_number',
        'location_label',
        'ip_address',
        'app_version',
        'os_version',
        'status',
        'kiosk_mode_enabled',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'kiosk_mode_enabled' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
