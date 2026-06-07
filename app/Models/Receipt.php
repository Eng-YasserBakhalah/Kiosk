<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasUuids;

    protected $fillable = [
        'transaction_id',
        'receipt_number',
        'bank_reference',
        'receipt_type',
        'masked_payload',
        'qr_payload',
        'printed_at',
    ];

    protected $casts = [
        'masked_payload' => 'array',
        'printed_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(ServiceTransaction::class, 'transaction_id');
    }
}
