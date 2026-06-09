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

class BillPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_bill_payment_requires_idempotency_key(): void
    {
        $token = $this->loginWithBillPaymentService();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/payments/bill-payment', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'IDEMPOTENCY_KEY_REQUIRED');

        $this->assertDatabaseHas('error_logs', [
            'error_code' => 'IDEMPOTENCY_KEY_REQUIRED',
            'service_code' => 'BILL_PAYMENT',
        ]);
    }

    public function test_bill_payment_creates_transaction_and_receipt(): void
    {
        $token = $this->loginWithBillPaymentService();
        $key = (string) Str::uuid();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/payments/bill-payment', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.transaction.transaction_type', 'BILL_PAYMENT')
            ->assertJsonPath('data.transaction.status', 'SUCCESS')
            ->assertJsonStructure([
                'data' => [
                    'receipt' => ['receipt_number'],
                ],
            ]);

        $this->assertDatabaseHas('service_transactions', [
            'idempotency_key' => $key,
            'transaction_type' => 'BILL_PAYMENT',
            'status' => 'SUCCESS',
        ]);

        $this->assertDatabaseCount('receipts', 1);
        $this->assertDatabaseHas('api_integration_logs', [
            'endpoint_key' => 'payments.bill_payment',
            'http_method' => 'POST',
            'success' => true,
        ]);
    }

    public function test_duplicate_bill_payment_returns_existing_transaction(): void
    {
        $token = $this->loginWithBillPaymentService();
        $key = (string) Str::uuid();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/payments/bill-payment', $this->payload())
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/payments/bill-payment', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.duplicate', true)
            ->assertJsonPath('data.transaction.transaction_type', 'BILL_PAYMENT');

        $this->assertDatabaseCount('service_transactions', 1);
        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_bill_payment_rejects_amount_above_service_maximum(): void
    {
        $token = $this->loginWithBillPaymentService(maxAmount: 100);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/payments/bill-payment', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'AMOUNT_ABOVE_SERVICE_MAXIMUM');

        $this->assertDatabaseMissing('service_transactions', [
            'transaction_type' => 'BILL_PAYMENT',
        ]);
    }

    public function test_bill_payment_rejects_daily_limit_exceeded(): void
    {
        $token = $this->loginWithBillPaymentService(dailyLimit: 200);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/payments/bill-payment', $this->payload())
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/payments/bill-payment', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'DAILY_LIMIT_EXCEEDED');

        $this->assertDatabaseCount('service_transactions', 1);
    }

    private function payload(): array
    {
        return [
            'from_account_id' => 'ACC-001',
            'biller_code' => 'STC',
            'bill_number' => '123456789',
            'amount' => 150,
            'currency' => 'SAR',
        ];
    }

    private function loginWithBillPaymentService(?float $maxAmount = null, ?float $dailyLimit = null): string
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
            'service_code' => 'BILL_PAYMENT',
            'service_name' => 'Bill Payment',
            'category' => 'payments',
            'requires_otp' => false,
            'requires_password' => true,
            'enabled' => true,
            'max_amount' => $maxAmount,
        ]);

        BranchServiceSetting::create([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'enabled' => true,
            'daily_limit' => $dailyLimit,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'device_id' => 'KIOSK-001',
            'username' => 'USR10001',
            'password' => 'Password1',
        ]);

        return $login->json('token');
    }
}
