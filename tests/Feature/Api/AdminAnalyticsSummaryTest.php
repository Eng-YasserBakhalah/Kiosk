<?php

namespace Tests\Feature\Api;

use App\Models\AccountOpeningRequest;
use App\Models\ApiIntegrationLog;
use App\Models\Branch;
use App\Models\DigitalServiceUser;
use App\Models\ErrorLog;
use App\Models\ServiceTransaction;
use App\Models\TerminalDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAnalyticsSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_load_analytics_summary(): void
    {
        [$token, $device, $user] = $this->loginUser('ADMIN');

        ServiceTransaction::create([
            'request_id' => 'REQ-TRX-1',
            'user_id' => $user->id,
            'terminal_device_id' => $device->id,
            'branch_id' => $device->branch_id,
            'transaction_type' => 'BILL_PAYMENT',
            'amount' => 150,
            'currency' => 'SAR',
            'status' => 'SUCCESS',
        ]);

        ApiIntegrationLog::create([
            'request_id' => 'REQ-API-1',
            'user_id' => $user->id,
            'terminal_device_id' => $device->id,
            'external_api_name' => 'mock_bank_core',
            'endpoint_key' => 'payments.bill_payment',
            'http_method' => 'POST',
            'response_status' => 200,
            'bank_response_code' => '00',
            'duration_ms' => 5,
            'success' => true,
        ]);

        ErrorLog::create([
            'request_id' => 'REQ-ERR-1',
            'user_id' => $user->id,
            'terminal_device_id' => $device->id,
            'error_code' => 'TEST_ERROR',
            'error_message' => 'Test error',
        ]);

        AccountOpeningRequest::create([
            'request_id' => 'REQ-AO-1',
            'user_id' => $user->id,
            'terminal_device_id' => $device->id,
            'branch_id' => $device->branch_id,
            'tracking_number' => 'AOR-TEST-1',
            'account_type' => 'CURRENT',
            'currency' => 'SAR',
            'full_name' => 'Test Customer',
            'phone_masked' => '+9*********00',
            'national_id_masked' => '****7890',
            'status' => 'SUBMITTED',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/analytics/summary')
            ->assertOk()
            ->assertJsonPath('data.devices.total', 1)
            ->assertJsonPath('data.transactions.today_total', 1)
            ->assertJsonPath('data.integrations.success', 1)
            ->assertJsonPath('data.errors.today_total', 1)
            ->assertJsonPath('data.account_opening_requests.submitted', 1);
    }

    public function test_customer_cannot_load_analytics_summary(): void
    {
        [$token] = $this->loginUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/analytics/summary')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    private function loginUser(string $role = 'CUSTOMER'): array
    {
        $branch = Branch::create([
            'branch_code' => 'BR-001',
            'name' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        $device = TerminalDevice::create([
            'branch_id' => $branch->id,
            'device_code' => 'KIOSK-001',
            'status' => 'ACTIVE',
            'last_heartbeat_at' => now(),
        ]);

        $user = DigitalServiceUser::create([
            'bank_customer_ref' => 'BANK-100001',
            'username' => 'USR10001',
            'phone_masked' => '+966*******000',
            'password_hash' => Hash::make('Password1'),
            'status' => 'ACTIVE',
            'role' => $role,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'device_id' => 'KIOSK-001',
            'username' => 'USR10001',
            'password' => 'Password1',
        ])->assertOk();

        return [$login->json('token'), $device, $user];
    }
}
