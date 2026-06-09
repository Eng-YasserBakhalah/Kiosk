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

    public function me(): JsonResponse
    {
        $session = $this->sessionContext->current()?->load(['user', 'terminalDevice.branch']);

        if (! $session?->user || ! $session->terminalDevice) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        return ApiResponse::success([
            'user' => [
                'id' => $session->user->id,
                'username' => $session->user->username,
                'bank_customer_ref' => $session->user->bank_customer_ref,
                'phone_masked' => $session->user->phone_masked,
                'status' => $session->user->status,
                'role' => $session->user->role,
                'biometric_enabled' => $session->user->biometric_enabled,
                'last_login_at' => $session->user->last_login_at,
            ],
            'session' => [
                'id' => $session->id,
                'login_method' => $session->login_method,
                'login_at' => $session->login_at,
                'expires_at' => $session->expires_at,
                'status' => $session->status,
            ],
            'device' => [
                'id' => $session->terminalDevice->id,
                'device_code' => $session->terminalDevice->device_code,
                'status' => $session->terminalDevice->status,
                'location_label' => $session->terminalDevice->location_label,
                'kiosk_mode_enabled' => $session->terminalDevice->kiosk_mode_enabled,
                'last_heartbeat_at' => $session->terminalDevice->last_heartbeat_at,
            ],
            'branch' => $session->terminalDevice->branch ? [
                'id' => $session->terminalDevice->branch->id,
                'branch_code' => $session->terminalDevice->branch->branch_code,
                'name' => $session->terminalDevice->branch->name,
                'city' => $session->terminalDevice->branch->city,
                'status' => $session->terminalDevice->branch->status,
            ] : null,
        ], 'Profile loaded successfully');
    }

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
