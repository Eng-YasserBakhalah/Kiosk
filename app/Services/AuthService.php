<?php

namespace App\Services;

use App\Models\LoginLog;
use App\Models\TerminalDevice;
use App\Models\DigitalServiceUser;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;


class AuthService
{
    public function login(array $data): array
    {
        /*
        Check Device
        */

        $device = TerminalDevice::where(
            'device_code',
            $data['device_id']
        )->first();

        if (!$device) {

            return [
                'success' => false,
                'message' => 'Device not found',
                'status_code' => 404
            ];
        }

        /*
        Find User
        */

        $user = DigitalServiceUser::where(
            'username',
            $data['username']
        )->first();

        if (!$user) {

            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'status_code' => 401
            ];
        }

        /*
        Account locked
        */

        if (
            $user->locked_until &&
            now()->lessThan(
                $user->locked_until
            )
        ) {

            return [
                'success' => false,
                'message' =>
                    'Account temporarily locked',
                'locked_until' =>
                    $user->locked_until,
                'status_code' => 403
            ];
        }

        /*
        Password Check
        */

        if (!Hash::check(
            $data['password'],
            $user->password_hash
        )) {

            $user->increment(
                'failed_login_attempts'
            );

            /*
            Lock after 5 attempts
            */

            if (
                $user->failed_login_attempts >= 5
            ) {

                $user->update([
                    'locked_until' =>
                        now()->addMinutes(15)
                ]);
            }

            LoginLog::create([
                'user_id' => $user->id,
                'device_id' =>
                    $data['device_id'],
                'login_method' =>
                    'PASSWORD',
                'ip_address' =>
                    request()->ip(),
                'success' => false,
                'failure_reason' =>
                    'Invalid password'
            ]);

            return [
                'success' => false,
                'message' =>
                    'Invalid credentials',
                'status_code' => 401
            ];
        }

        /*
        Reset failed attempts
        */

        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);

        /*
        Generate JWT
        */

        $token = JWTAuth::fromUser(
            $user
        );

        /*
        Login log
        */

        LoginLog::create([
            'user_id' => $user->id,
            'device_id' =>
                $data['device_id'],
            'login_method' =>
                'PASSWORD',
            'ip_address' =>
                request()->ip(),
            'success' => true
        ]);

        return [
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' =>
                    $user->username
            ],
            'status_code' => 200
        ];
    }

    public function logout(): array
{
    try {

        JWTAuth::invalidate(
            JWTAuth::getToken()
        );

        return [
            'success' => true,
            'message' => 'Logged out successfully',
            'status_code' => 200
        ];

    } catch (\Exception $e) {

        return [
            'success' => false,
            'message' => 'Logout failed',
            'status_code' => 500
        ];
    }
}
}