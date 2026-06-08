<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\BranchServiceSetting;
use App\Models\DigitalService;
use App\Models\DigitalServiceUser;
use App\Models\TerminalDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileTopUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_topup_requires_idempotency_key(): void
    {
        $token = $this->loginWithMobileTopUpService();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/payments/mobile-topup', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'IDEMPOTENCY_KEY_REQUIRED');

        $this->assertDatabaseHas('error_logs', [
            'error_code' => 'IDEMPOTENCY_KEY_REQUIRED',
            'service_code' => 'MOBILE_TOPUP',
        ]);
    }

    public function test_mobile_topup_creates_transaction_and_receipt(): void
    {
        $token = $this->loginWithMobileTopUpService();
        $key = (string) Str::uuid();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/payments/mobile-topup', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.transaction.transaction_type', 'MOBILE_TOPUP')
            ->assertJsonPath('data.transaction.status', 'SUCCESS')
            ->assertJsonStructure([
                'data' => [
                    'receipt' => ['receipt_number'],
                ],
            ]);

        $this->assertDatabaseHas('service_transactions', [
            'idempotency_key' => $key,
            'transaction_type' => 'MOBILE_TOPUP',
            'status' => 'SUCCESS',
        ]);

        $this->assertDatabaseCount('receipts', 1);
        $this->assertDatabaseHas('api_integration_logs', [
            'endpoint_key' => 'payments.mobile_topup',
            'http_method' => 'POST',
            'success' => true,
        ]);
    }

    public function test_duplicate_mobile_topup_returns_existing_transaction(): void
    {
        $token = $this->loginWithMobileTopUpService();
        $key = (string) Str::uuid();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/payments/mobile-topup', $this->payload())
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/payments/mobile-topup', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.duplicate', true)
            ->assertJsonPath('data.transaction.transaction_type', 'MOBILE_TOPUP');

        $this->assertDatabaseCount('service_transactions', 1);
        $this->assertDatabaseCount('receipts', 1);
    }

    private function payload(): array
    {
        return [
            'from_account_id' => 'ACC-001',
            'operator' => 'STC',
            'phone' => '+966500000000',
            'amount' => 50,
            'currency' => 'SAR',
        ];
    }

    private function loginWithMobileTopUpService(): string
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

        DigitalServiceUser::create([
            'bank_customer_ref' => 'BANK-100001',
            'username' => 'USR10001',
            'phone_masked' => '+966*******000',
            'password_hash' => Hash::make('Password1'),
            'status' => 'ACTIVE',
        ]);

        $service = DigitalService::create([
            'service_code' => 'MOBILE_TOPUP',
            'service_name' => 'Mobile Top-up',
            'category' => 'payments',
            'requires_otp' => false,
            'requires_password' => true,
            'enabled' => true,
        ]);

        BranchServiceSetting::create([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'enabled' => true,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'device_id' => 'KIOSK-001',
            'username' => 'USR10001',
            'password' => 'Password1',
        ]);

        return $login->json('token');
    }
}
