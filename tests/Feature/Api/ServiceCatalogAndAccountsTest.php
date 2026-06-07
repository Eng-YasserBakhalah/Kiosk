<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\BranchServiceSetting;
use App\Models\DigitalService;
use App\Models\DigitalServiceUser;
use App\Models\TerminalDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServiceCatalogAndAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_enabled_services(): void
    {
        $token = $this->loginWithSeededChannel();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Request-Id', 'REQ-TEST-SERVICES')
            ->getJson('/api/v1/services')
            ->assertOk()
            ->assertHeader('X-Request-Id', 'REQ-TEST-SERVICES')
            ->assertJsonPath('request_id', 'REQ-TEST-SERVICES')
            ->assertJsonPath('data.services.0.code', 'ACCOUNTS_LIST');
    }

    public function test_authenticated_user_can_list_accounts_from_bank_adapter(): void
    {
        $token = $this->loginWithSeededChannel();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Request-Id', 'REQ-TEST-ACCOUNTS')
            ->getJson('/api/v1/accounts')
            ->assertOk()
            ->assertJsonPath('request_id', 'REQ-TEST-ACCOUNTS')
            ->assertJsonPath('data.accounts.0.masked_account', '****1234');

        $this->assertDatabaseHas('api_integration_logs', [
            'request_id' => 'REQ-TEST-ACCOUNTS',
            'endpoint_key' => 'accounts.list',
            'success' => true,
        ]);

        $this->assertDatabaseHas('service_transactions', [
            'request_id' => 'REQ-TEST-ACCOUNTS',
            'transaction_type' => 'ACCOUNTS_LIST',
            'status' => 'SUCCESS',
        ]);
    }

    public function test_authenticated_user_can_get_balance_and_statement(): void
    {
        $token = $this->loginWithSeededChannel();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/accounts/ACC-001/balance')
            ->assertOk()
            ->assertJsonPath('data.balance.account_id', 'ACC-001');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/accounts/ACC-001/statement')
            ->assertOk()
            ->assertJsonPath('data.statement.account_id', 'ACC-001');

        $this->assertDatabaseHas('service_transactions', [
            'transaction_type' => 'BALANCE_INQUIRY',
            'status' => 'SUCCESS',
        ]);

        $this->assertDatabaseHas('service_transactions', [
            'transaction_type' => 'SHORT_STATEMENT',
            'status' => 'SUCCESS',
        ]);
    }

    private function loginWithSeededChannel(): string
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

        foreach (['ACCOUNTS_LIST', 'BALANCE_INQUIRY', 'SHORT_STATEMENT'] as $serviceCode) {
            $service = DigitalService::create([
                'service_code' => $serviceCode,
                'service_name' => str($serviceCode)->replace('_', ' ')->title()->toString(),
                'category' => 'accounts',
                'enabled' => true,
            ]);

            BranchServiceSetting::create([
                'branch_id' => $branch->id,
                'service_id' => $service->id,
                'enabled' => true,
            ]);
        }

        $login = $this->postJson('/api/v1/auth/login', [
            'device_id' => 'KIOSK-001',
            'username' => 'USR10001',
            'password' => 'Password1',
        ]);

        return $login->json('token');
    }
}
