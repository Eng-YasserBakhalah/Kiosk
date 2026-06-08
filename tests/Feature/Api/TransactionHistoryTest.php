<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\DigitalServiceUser;
use App\Models\Receipt;
use App\Models\ServiceTransaction;
use App\Models\TerminalDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_own_transactions(): void
    {
        [$token, $user] = $this->loginUser();
        $ownTransaction = $this->transactionFor($user, 'BILL_PAYMENT', 'OWN-REF');
        $this->transactionFor($this->otherUser(), 'MOBILE_TOPUP', 'OTHER-REF');

        Receipt::create([
            'transaction_id' => $ownTransaction->id,
            'receipt_number' => 'RCT-OWN',
            'bank_reference' => 'OWN-REF',
            'masked_payload' => ['status' => 'SUCCESS'],
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data.transactions')
            ->assertJsonPath('data.transactions.0.transaction_type', 'BILL_PAYMENT')
            ->assertJsonPath('data.transactions.0.receipt.receipt_number', 'RCT-OWN');
    }

    public function test_transactions_can_be_filtered_by_type(): void
    {
        [$token, $user] = $this->loginUser();

        $this->transactionFor($user, 'BILL_PAYMENT', 'BILL-REF');
        $this->transactionFor($user, 'MOBILE_TOPUP', 'TOPUP-REF');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/transactions?transaction_type=MOBILE_TOPUP')
            ->assertOk()
            ->assertJsonCount(1, 'data.transactions')
            ->assertJsonPath('data.transactions.0.transaction_type', 'MOBILE_TOPUP')
            ->assertJsonPath('data.transactions.0.bank_reference', 'TOPUP-REF');
    }

    private function loginUser(): array
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

        $user = DigitalServiceUser::create([
            'bank_customer_ref' => 'BANK-100001',
            'username' => 'USR10001',
            'phone_masked' => '+966*******000',
            'password_hash' => Hash::make('Password1'),
            'status' => 'ACTIVE',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'device_id' => 'KIOSK-001',
            'username' => 'USR10001',
            'password' => 'Password1',
        ]);

        return [$login->json('token'), $user];
    }

    private function otherUser(): DigitalServiceUser
    {
        return DigitalServiceUser::create([
            'bank_customer_ref' => 'BANK-200001',
            'username' => 'USR20001',
            'phone_masked' => '+966*******111',
            'password_hash' => Hash::make('Password1'),
            'status' => 'ACTIVE',
        ]);
    }

    private function transactionFor(DigitalServiceUser $user, string $type, string $bankReference): ServiceTransaction
    {
        return ServiceTransaction::create([
            'request_id' => 'REQ-'.$bankReference,
            'user_id' => $user->id,
            'bank_reference' => $bankReference,
            'transaction_type' => $type,
            'amount' => 100,
            'currency' => 'SAR',
            'status' => 'SUCCESS',
            'response_code' => '00',
            'response_message' => 'Approved',
            'completed_at' => now(),
        ]);
    }
}
