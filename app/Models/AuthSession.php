<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuthSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'terminal_device_id',
        'access_token_hash',
        'refresh_token_hash',
        'ip_address',
        'user_agent',
        'login_method',
        'login_at',
        'expires_at',
        'logout_at',
        'status',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'expires_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function terminalDevice()
    {
        return $this->belongsTo(TerminalDevice::class);
    }

    public function user()
    {
        return $this->belongsTo(DigitalServiceUser::class, 'user_id');
    }
}
