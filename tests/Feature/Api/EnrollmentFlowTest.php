<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\OtpRequest;
use App\Models\TerminalDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnrollmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_start_requires_active_device(): void
    {
        $this->postJson('/api/v1/enrollment/start', [
            'device_id' => 'UNKNOWN',
            'customer_identifier' => 'CUST-001',
            'phone' => '+966500000000',
        ])->assertNotFound();
    }

    public function test_otp_cannot_be_consumed_twice(): void
    {
        $otpRequest = OtpRequest::create([
            'phone_masked' => '+966*******000',
            'purpose' => 'ENROLLMENT',
            'otp_hash' => Hash::make('123456'),
            'status' => 'VERIFIED',
            'expires_at' => now()->addMinutes(5),
        ]);

        $payload = [
            'request_id' => $otpRequest->id,
            'password' => 'Password1',
        ];

        $this->postJson('/api/v1/enrollment/set-password', $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/enrollment/set-password', $payload)
            ->assertStatus(400)
            ->assertJsonPath('message', 'OTP already consumed');
    }

    public function test_otp_is_marked_failed_after_max_invalid_attempts(): void
    {
        $otpRequest = OtpRequest::create([
            'phone_masked' => '+966*******000',
            'purpose' => 'ENROLLMENT',
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/enrollment/verify-otp', [
                'request_id' => $otpRequest->id,
                'otp' => '999999',
            ])->assertStatus(400);
        }

        $this->assertDatabaseHas('otp_requests', [
            'id' => $otpRequest->id,
            'attempts' => 5,
            'status' => 'FAILED',
        ]);
    }

    public function test_enrollment_start_creates_pending_otp_for_active_device(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-001',
            'name' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        TerminalDevice::create([
            'branch_id' => $branch->id,
            'device_code' => 'KIOSK-001',
            'status' => 'ACTIVE',
        ]);

        $this->postJson('/api/v1/enrollment/start', [
            'device_id' => 'KIOSK-001',
            'customer_identifier' => 'CUST-001',
            'phone' => '+966500000000',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['request_id', 'expires_in', 'debug_otp']);

        $this->assertDatabaseHas('otp_requests', [
            'phone_masked' => '+966500000000',
            'purpose' => 'ENROLLMENT',
            'status' => 'PENDING',
        ]);
    }

    public function test_debug_otp_is_hidden_when_disabled(): void
    {
        config(['services.otp.debug_response' => false]);

        $branch = Branch::create([
            'branch_code' => 'BR-001',
            'name' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        TerminalDevice::create([
            'branch_id' => $branch->id,
            'device_code' => 'KIOSK-001',
            'status' => 'ACTIVE',
        ]);

        $this->postJson('/api/v1/enrollment/start', [
            'device_id' => 'KIOSK-001',
            'customer_identifier' => 'CUST-001',
            'phone' => '+966500000000',
        ])
            ->assertOk()
            ->assertJsonMissingPath('debug_otp');
    }
}
