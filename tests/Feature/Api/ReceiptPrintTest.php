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

class ReceiptPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_print_marks_receipt_as_printed_and_audits_action(): void
    {
        [$token, $user] = $this->loginUser();
        $receipt = $this->receiptFor($user, 'RCT-OWN', 'OWN-REF');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/receipts/'.$receipt->receipt_number.'/print')
            ->assertOk()
            ->assertJsonPath('data.print.printed', true)
            ->assertJsonPath('data.print.first_print', true);

        $this->assertDatabaseHas('receipts', [
            'receipt_number' => 'RCT-OWN',
        ]);

        $this->assertNotNull($receipt->refresh()->printed_at);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'RECEIPT_PRINTED',
            'entity_id' => $receipt->id,
        ]);
    }

    public function test_receipt_reprint_keeps_original_print_time_and_audits_reprint(): void
    {
        [$token, $user] = $this->loginUser();
        $receipt = $this->receiptFor($user, 'RCT-OWN', 'OWN-REF');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/receipts/'.$receipt->receipt_number.'/print')
            ->assertOk();

        $printedAt = $receipt->refresh()->printed_at;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/receipts/'.$receipt->receipt_number.'/print')
            ->assertOk()
            ->assertJsonPath('data.print.first_print', false);

        $this->assertTrue($printedAt->equalTo($receipt->refresh()->printed_at));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'RECEIPT_REPRINTED',
            'entity_id' => $receipt->id,
        ]);
    }

    public function test_receipt_print_for_another_user_is_not_allowed(): void
    {
        [$token] = $this->loginUser();
        $receipt = $this->receiptFor($this->otherUser(), 'RCT-OTHER', 'OTHER-REF');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/receipts/'.$receipt->receipt_number.'/print')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'RECEIPT_NOT_FOUND');

        $this->assertNull($receipt->refresh()->printed_at);
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

    private function receiptFor(DigitalServiceUser $user, string $receiptNumber, string $bankReference): Receipt
    {
        $transaction = ServiceTransaction::create([
            'request_id' => 'REQ-'.$bankReference,
            'user_id' => $user->id,
            'bank_reference' => $bankReference,
            'transaction_type' => 'BILL_PAYMENT',
            'amount' => 150,
            'currency' => 'SAR',
            'status' => 'SUCCESS',
            'response_code' => '00',
            'response_message' => 'Approved',
            'completed_at' => now(),
        ]);

        return Receipt::create([
            'transaction_id' => $transaction->id,
            'receipt_number' => $receiptNumber,
            'bank_reference' => $bankReference,
            'masked_payload' => ['status' => 'SUCCESS'],
        ]);
    }
}
