<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BranchServiceSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'service_id',
        'enabled',
        'daily_limit',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'daily_limit' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(DigitalService::class, 'service_id');
    }
}
