<?php

namespace App\Services;

use App\Models\DigitalServiceUser;
use App\Models\OtpRequest;
use App\Models\TerminalDevice;
use Illuminate\Support\Facades\Hash;

class EnrollmentService
{
    private const OTP_MAX_ATTEMPTS = 5;

    public function start(array $data): array
    {
        $device = TerminalDevice::where('device_code', $data['device_id'])
            ->first();

        if (! $device) {
            return [
                'success' => false,
                'message' => 'Device not registered',
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

        // Placeholder until a real core-banking customer verification API is wired.
        $customerExists = true;

        if (! $customerExists) {
            return [
                'success' => false,
                'message' => 'Customer not found',
                'status_code' => 404,
            ];
        }

        $otp = random_int(100000, 999999);

        $otpRequest = OtpRequest::create([
            'phone_masked' => $data['phone'],
            'purpose' => 'ENROLLMENT',
            'otp_hash' => Hash::make((string) $otp),
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = [
            'success' => true,
            'message' => 'OTP sent successfully',
            'request_id' => $otpRequest->id,
            'expires_in' => 300,
            'status_code' => 200,
        ];

        if (config('services.otp.debug_response') && ! app()->environment('production')) {
            $response['debug_otp'] = (string) $otp;
        }

        return $response;
    }

    public function verifyOtp(array $data): array
    {
        $otpRequest = OtpRequest::find($data['request_id']);

        if (! $otpRequest) {
            return [
                'success' => false,
                'message' => 'Invalid request',
                'status_code' => 404,
            ];
        }

        if ($otpRequest->status !== 'PENDING') {
            return [
                'success' => false,
                'message' => 'OTP is not pending',
                'status_code' => 400,
            ];
        }

        if (now()->greaterThan($otpRequest->expires_at)) {
            $otpRequest->update([
                'status' => 'EXPIRED',
            ]);

            return [
                'success' => false,
                'message' => 'OTP expired',
                'status_code' => 400,
            ];
        }

        if (! Hash::check($data['otp'], $otpRequest->otp_hash)) {
            $otpRequest->increment('attempts');
            $otpRequest->refresh();

            if ($otpRequest->attempts >= self::OTP_MAX_ATTEMPTS) {
                $otpRequest->update([
                    'status' => 'FAILED',
                ]);
            }

            return [
                'success' => false,
                'message' => 'Invalid OTP',
                'status_code' => 400,
            ];
        }

        $otpRequest->update([
            'status' => 'VERIFIED',
        ]);

        return [
            'success' => true,
            'message' => 'OTP verified successfully',
            'request_id' => $otpRequest->id,
            'status_code' => 200,
        ];
    }

    public function setPassword(array $data): array
    {
        $otpRequest = OtpRequest::find($data['request_id']);

        if (! $otpRequest) {
            return [
                'success' => false,
                'message' => 'Invalid request',
                'status_code' => 404,
            ];
        }

        if ($otpRequest->status !== 'VERIFIED') {
            return [
                'success' => false,
                'message' => 'OTP verification required',
                'status_code' => 400,
            ];
        }

        if ($otpRequest->user_id !== null) {
            return [
                'success' => false,
                'message' => 'OTP already consumed',
                'status_code' => 400,
            ];
        }

        if (now()->greaterThan($otpRequest->expires_at)) {
            $otpRequest->update([
                'status' => 'EXPIRED',
            ]);

            return [
                'success' => false,
                'message' => 'OTP expired',
                'status_code' => 400,
            ];
        }

        $user = DigitalServiceUser::create([
            'bank_customer_ref' => $this->generateUniqueValue('bank_customer_ref', 'BANK-', 100000, 999999),
            'username' => $this->generateUniqueValue('username', 'USR', 10000, 99999),
            'phone_masked' => $otpRequest->phone_masked,
            'password_hash' => Hash::make($data['password']),
            'status' => 'ACTIVE',
        ]);

        $otpRequest->update([
            'user_id' => $user->id,
        ]);

        return [
            'success' => true,
            'message' => 'Account activated successfully',
            'username' => $user->username,
            'status_code' => 200,
        ];
    }

    private function generateUniqueValue(
        string $column,
        string $prefix,
        int $min,
        int $max
    ): string {
        do {
            $value = $prefix.random_int($min, $max);
        } while (DigitalServiceUser::where($column, $value)->exists());

        return $value;
    }
}
