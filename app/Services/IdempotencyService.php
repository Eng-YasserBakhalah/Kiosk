<?php

namespace App\Services;

use App\Models\ServiceTransaction;

class IdempotencyService
{
    public function key(): ?string
    {
        return request()->header('Idempotency-Key');
    }

    public function existing(?string $key): ?ServiceTransaction
    {
        if (! $key) {
            return null;
        }

        return ServiceTransaction::where('idempotency_key', $key)->first();
    }

    public function requireKey(): ?array
    {
        if ($this->key()) {
            return null;
        }

        return [
            'code' => 'IDEMPOTENCY_KEY_REQUIRED',
            'message' => 'Idempotency-Key header is required',
            'status' => 422,
        ];
    }
}
