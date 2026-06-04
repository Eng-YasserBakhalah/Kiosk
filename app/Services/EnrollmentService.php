<?php

namespace App\Services;

use App\Models\OtpRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\DigitalServiceUser;




class EnrollmentService
{
    public function verifyOtp(array $data): array
{
    $otpRequest = OtpRequest::find(
        $data['request_id']
    );

    if (!$otpRequest) {

        return [
            'success' => false,
            'message' => 'Invalid request',
            'status_code' => 404
        ];
    }

    if ($otpRequest->status !== 'PENDING') {

        return [
            'success' => false,
            'message' => 'OTP already used',
            'status_code' => 400
        ];
    }

    if (now()->greaterThan(
        $otpRequest->expires_at
    )) {

        $otpRequest->update([
            'status' => 'EXPIRED'
        ]);

        return [
            'success' => false,
            'message' => 'OTP expired',
            'status_code' => 400
        ];
    }

    if (!Hash::check(
        $data['otp'],
        $otpRequest->otp_hash
    )) {

        $otpRequest->increment('attempts');

        return [
            'success' => false,
            'message' => 'Invalid OTP',
            'status_code' => 400
        ];
    }

    $otpRequest->update([
        'status' => 'VERIFIED'
    ]);

    return [
        'success' => true,
        'message' => 'OTP verified successfully',
        'request_id' => $otpRequest->id,
        'status_code' => 200
    ];
}
    public function start(array $data): array
    {
        /*
        Mock Bank Verification
        لاحقًا سنستبدله بـ API حقيقي
        */

        $customerExists = true;

        if (!$customerExists) {

            return [
                'success' => false,
                'message' => 'Customer not found',
                'status_code' => 404
            ];
        }

        /*
        Generate OTP
        */

        $otp = rand(100000, 999999);

        /*
        Save OTP Request
        */

        $otpRequest = OtpRequest::create([
            'phone_masked' => $data['phone'],
            'purpose' => 'ENROLLMENT',
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
        ]);

        /*
        Temporary log
        */

        logger("OTP: {$otp}");

        return [
            'success' => true,
            'message' => 'OTP sent successfully',
            'request_id' => $otpRequest->id,
            'expires_in' => 300,
            'status_code' => 200
        ];
    }

    public function setPassword(array $data): array
{
    $otpRequest = OtpRequest::find(
        $data['request_id']
    );

    if (!$otpRequest) {

        return [
            'success' => false,
            'message' => 'Invalid request',
            'status_code' => 404
        ];
    }

    if ($otpRequest->status !== 'VERIFIED') {

        return [
            'success' => false,
            'message' => 'OTP verification required',
            'status_code' => 400
        ];
    }

    $user = DigitalServiceUser::create([
        'bank_customer_ref' =>
            'BANK-' . rand(100000, 999999),

        'username' =>
            'USR' . rand(10000, 99999),

        'phone_masked' =>
            $otpRequest->phone_masked,

        'password_hash' =>
            Hash::make($data['password']),

        'status' => 'ACTIVE'
    ]);

    return [
        'success' => true,
        'message' => 'Account activated successfully',
        'username' => $user->username,
        'status_code' => 200
    ];
}
}