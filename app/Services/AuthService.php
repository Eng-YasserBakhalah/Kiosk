<?php

namespace App\Services;

use App\Models\AuthSession;
use App\Models\DigitalServiceUser;
use App\Models\LoginLog;
use App\Models\TerminalDevice;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
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
        $refreshToken = $this->newRefreshToken();

        AuthSession::create([
            'user_id' => $user->id,
            'terminal_device_id' => $device->id,
            'access_token_hash' => hash('sha256', $token),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'login_method' => 'PASSWORD',
            'login_at' => now(),
            'expires_at' => now()->addMinutes((int) config('jwt.ttl', 60)),
            'status' => 'ACTIVE',
        ]);

        $this->logAttempt($user, $data['device_id'], true);

        return [
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
            'status_code' => 200,
        ];
    }

    public function refresh(array $data): array
    {
        $session = AuthSession::query()
            ->with(['terminalDevice', 'user'])
            ->where('refresh_token_hash', hash('sha256', $data['refresh_token']))
            ->where('status', 'ACTIVE')
            ->first();

        if (! $session || $session->login_at->lt(now()->subMinutes((int) config('jwt.refresh_ttl', 20160)))) {
            return [
                'success' => false,
                'message' => 'Invalid refresh token',
                'status_code' => 401,
            ];
        }

        if ($session->user?->status !== 'ACTIVE') {
            return [
                'success' => false,
                'message' => 'Account inactive',
                'status_code' => 403,
            ];
        }

        if ($session->terminalDevice?->status !== 'ACTIVE') {
            return [
                'success' => false,
                'message' => 'Device inactive',
                'status_code' => 403,
            ];
        }

        $token = JWTAuth::fromUser($session->user);
        $refreshToken = $this->newRefreshToken();

        $session->update([
            'access_token_hash' => hash('sha256', $token),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'expires_at' => now()->addMinutes((int) config('jwt.ttl', 60)),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'success' => true,
            'message' => 'Token refreshed successfully',
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
            'status_code' => 200,
        ];
    }

    public function logout(): array
    {
        try {
            $token = request()->bearerToken();

            if (! $token) {
                return [
                    'success' => false,
                    'message' => 'Token not provided',
                    'status_code' => 401,
                ];
            }

            JWTAuth::setToken($token)->checkOrFail();
            JWTAuth::setToken($token)->invalidate();

            AuthSession::where('access_token_hash', hash('sha256', $token))
                ->where('status', 'ACTIVE')
                ->update([
                    'status' => 'LOGGED_OUT',
                    'logout_at' => now(),
                ]);

            return [
                'success' => true,
                'message' => 'Logged out successfully',
                'status_code' => 200,
            ];
        } catch (TokenBlacklistedException) {
            return [
                'success' => false,
                'message' => 'Token already logged out',
                'status_code' => 401,
            ];
        } catch (TokenExpiredException) {
            return [
                'success' => false,
                'message' => 'Token expired',
                'status_code' => 401,
            ];
        } catch (TokenInvalidException|JWTException) {
            return [
                'success' => false,
                'message' => 'Invalid token',
                'status_code' => 401,
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

    private function newRefreshToken(): string
    {
        return Str::random(80);
    }
}
