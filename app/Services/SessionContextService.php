<?php

namespace App\Services;

use App\Models\AuthSession;

class SessionContextService
{
    public function current(): ?AuthSession
    {
        $token = request()->bearerToken();

        if (! $token) {
            return null;
        }

        return AuthSession::query()
            ->where('access_token_hash', hash('sha256', $token))
            ->where('status', 'ACTIVE')
            ->where('expires_at', '>', now())
            ->first();
    }
}
