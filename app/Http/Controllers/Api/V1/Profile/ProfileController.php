<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Models\AuditLog;
use App\Models\AuthSession;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(
        protected SessionContextService $sessionContext
    ) {}

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $session = $this->sessionContext->current()?->load('user');

        if (! $session?->user) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        if (! Hash::check($request->validated('current_password'), $session->user->password_hash)) {
            return ApiResponse::error(
                'CURRENT_PASSWORD_INVALID',
                'Current password is invalid',
                422,
                null,
                null,
                self::class
            );
        }

        $session->user->update([
            'password_hash' => Hash::make($request->validated('password')),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        AuthSession::query()
            ->where('user_id', $session->user_id)
            ->where('id', '!=', $session->id)
            ->where('status', 'ACTIVE')
            ->update([
                'status' => 'LOGGED_OUT',
                'logout_at' => now(),
            ]);

        AuditLog::create([
            'actor_type' => 'USER',
            'actor_id' => $session->user_id,
            'action' => 'PASSWORD_CHANGED',
            'entity_type' => 'DigitalServiceUser',
            'entity_id' => $session->user_id,
            'ip_address' => $request->ip(),
            'terminal_device_id' => $session->terminal_device_id,
        ]);

        return ApiResponse::success([], 'Password changed successfully');
    }
}
