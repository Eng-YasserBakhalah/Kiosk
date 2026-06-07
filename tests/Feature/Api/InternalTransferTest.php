<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\BranchServiceSetting;
use App\Models\DigitalService;
use App\Models\DigitalServiceUser;
use App\Models\Receipt;
use App\Models\ServiceTransaction;
use App\Models\TerminalDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class InternalTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_transfer_requires_idempotency_key(): void
    {
        $token = $this->loginWithTransferService();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/transfers/internal', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'IDEMPOTENCY_KEY_REQUIRED');

        $this->assertDatabaseHas('error_logs', [
            'error_code' => 'IDEMPOTENCY_KEY_REQUIRED',
            'service_code' => 'INTERNAL_TRANSFER',
        ]);
    }

    public function test_internal_transfer_creates_transaction_and_receipt(): void
    {
        $token = $this->loginWithTransferService();
        $key = (string) Str::uuid();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/transfers/internal', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.transaction.transaction_type', 'INTERNAL_TRANSFER')
            ->assertJsonPath('data.transaction.status', 'SUCCESS')
            ->assertJsonStructure([
                'data' => [
                    'receipt' => ['receipt_number'],
                ],
            ]);

        $this->assertDatabaseHas('service_transactions', [
            'idempotency_key' => $key,
            'transaction_type' => 'INTERNAL_TRANSFER',
            'status' => 'SUCCESS',
        ]);

        $this->assertDatabaseCount('receipts', 1);
        $this->assertDatabaseHas('api_integration_logs', [
            'endpoint_key' => 'transfers.internal',
            'http_method' => 'POST',
            'success' => true,
        ]);
    }

    public function test_duplicate_internal_transfer_returns_existing_transaction(): void
    {
        $token = $this->loginWithTransferService();
        $key = (string) Str::uuid();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/transfers/internal', $this->payload())
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/transfers/internal', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.duplicate', true);

        $this->assertDatabaseCount('service_transactions', 1);
        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_receipt_can_be_loaded_by_receipt_number_or_bank_reference(): void
    {
        $token = $this->loginWithTransferService();
        $key = (string) Str::uuid();

        $transfer = $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/transfers/internal', $this->payload());

        $receiptNumber = $transfer->json('data.receipt.receipt_number');
        $bankReference = $transfer->json('data.transaction.bank_reference');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/receipts/{$receiptNumber}")
            ->assertOk()
            ->assertJsonPath('data.receipt.receipt_number', $receiptNumber)
            ->assertJsonPath('data.receipt.bank_reference', $bankReference);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/receipts/{$bankReference}")
            ->assertOk()
            ->assertJsonPath('data.receipt.receipt_number', $receiptNumber)
            ->assertJsonPath('data.receipt.bank_reference', $bankReference);
    }

    public function test_receipt_for_another_user_is_not_returned(): void
    {
        $token = $this->loginWithTransferService();

        $otherTransaction = ServiceTransaction::create([
            'request_id' => 'REQ-OTHER',
            'user_id' => DigitalServiceUser::create([
                'bank_customer_ref' => 'BANK-200001',
                'username' => 'USR20001',
                'phone_masked' => '+966*******111',
                'password_hash' => Hash::make('Password1'),
                'status' => 'ACTIVE',
            ])->id,
            'transaction_type' => 'INTERNAL_TRANSFER',
            'bank_reference' => 'OTHER-BANK-REF',
            'status' => 'SUCCESS',
        ]);

        Receipt::create([
            'transaction_id' => $otherTransaction->id,
            'receipt_number' => 'RCT-OTHER',
            'bank_reference' => 'OTHER-BANK-REF',
            'masked_payload' => ['status' => 'SUCCESS'],
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/receipts/RCT-OTHER')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'RECEIPT_NOT_FOUND');
    }

    private function payload(): array
    {
        return [
            'from_account_id' => 'ACC-001',
            'to_account_identifier' => 'ACC-002',
            'amount' => 100,
            'currency' => 'SAR',
            'purpose' => 'Test transfer',
            'otp' => '123456',
        ];
    }

    private function loginWithTransferService(): string
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
            'service_code' => 'INTERNAL_TRANSFER',
            'service_name' => 'Internal Transfer',
            'category' => 'transfers',
            'requires_otp' => true,
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
