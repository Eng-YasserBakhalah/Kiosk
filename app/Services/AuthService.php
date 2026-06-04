<?php

namespace App\Services;

use App\Models\DigitalServiceUser;
use App\Models\LoginLog;
use App\Models\TerminalDevice;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    private const MAX_FAILED_LOGIN_ATTEMPTS = 5;

    private const LOCK_MINUTES = 15;

    public function login(array $data): array
    {
        $device = TerminalDevice::where('device_code', $data['device_id'])
            ->first();

        if (! $device) {
            return [
                'success' => false,
                'message' => 'Device not found',
                'status_code' => 404,
            ];
        }

        if ($device->status !== 'ACTIVE') {
            return [
                'success' => false,
                'message' => 'Device inactive',
                'status_code' => 403,
            ];
        }

        $user = DigitalServiceUser::where('username', $data['username'])
            ->first();

        if (! $user) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'status_code' => 401,
            ];
        }

        if ($user->status !== 'ACTIVE') {
            $this->logAttempt($user, $data['device_id'], false, 'Account inactive');

            return [
                'success' => false,
                'message' => 'Account inactive',
                'status_code' => 403,
            ];
        }

        if ($user->locked_until && now()->lessThan($user->locked_until)) {
            $this->logAttempt($user, $data['device_id'], false, 'Account locked');

            return [
                'success' => false,
                'message' => 'Account temporarily locked',
                'locked_until' => $user->locked_until,
                'status_code' => 403,
            ];
        }

        if (! Hash::check($data['password'], $user->password_hash)) {
            $user->increment('failed_login_attempts');
            $user->refresh();

            if ($user->failed_login_attempts >= self::MAX_FAILED_LOGIN_ATTEMPTS) {
                $user->update([
                    'locked_until' => now()->addMinutes(self::LOCK_MINUTES),
                ]);
            }

            $this->logAttempt($user, $data['device_id'], false, 'Invalid password');

            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'status_code' => 401,
            ];
        }

        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);

        $token = JWTAuth::fromUser($user);

        $this->logAttempt($user, $data['device_id'], true);

        return [
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
            'status_code' => 200,
        ];
    }

    public function logout(): array
    {
        try {
            $token = JWTAuth::getToken();

            if (! $token) {
                return [
                    'success' => false,
                    'message' => 'Token not provided',
                    'status_code' => 401,
                ];
            }

            JWTAuth::invalidate($token);

            return [
                'success' => true,
                'message' => 'Logged out successfully',
                'status_code' => 200,
            ];
        } catch (\Throwable) {
            return [
                'success' => false,
                'message' => 'Logout failed',
                'status_code' => 500,
            ];
        }
    }

    private function logAttempt(
        DigitalServiceUser $user,
        string $deviceId,
        bool $success,
        ?string $failureReason = null
    ): void {
        LoginLog::create([
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'login_method' => 'PASSWORD',
            'ip_address' => request()->ip(),
            'success' => $success,
            'failure_reason' => $failureReason,
        ]);
    }
}
