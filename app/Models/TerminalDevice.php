<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TerminalDevice extends Model
{
    use HasUuids;
    protected $keyType = 'uuid';
    protected $guarded = [];
      protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'kiosk_mode_enabled' => 'boolean',
    ];


    
    //
}
