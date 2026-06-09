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

class AccountOpeningRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_account_opening_request(): void
    {
        $token = $this->loginWithAccountOpeningService();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/account-opening/requests', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.request.account_type', 'CURRENT')
            ->assertJsonPath('data.request.currency', 'SAR')
            ->assertJsonPath('data.request.status', 'SUBMITTED')
            ->assertJsonStructure([
                'data' => [
                    'request' => ['tracking_number', 'bank_reference'],
                ],
            ]);

        $this->assertDatabaseHas('account_opening_requests', [
            'account_type' => 'CURRENT',
            'phone_masked' => '+9*********00',
            'national_id_masked' => '****7890',
            'status' => 'SUBMITTED',
        ]);

        $this->assertDatabaseHas('api_integration_logs', [
            'endpoint_key' => 'account_opening.requests.create',
            'http_method' => 'POST',
            'success' => true,
        ]);
    }

    public function test_account_opening_request_rejected_when_service_disabled_for_branch(): void
    {
        $token = $this->loginWithAccountOpeningService(enabled: false);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/account-opening/requests', $this->payload())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'SERVICE_NOT_ALLOWED_ON_DEVICE');
    }

    private function payload(): array
    {
        return [
            'account_type' => 'CURRENT',
            'currency' => 'SAR',
            'full_name' => 'Test Customer',
            'phone' => '+966500000000',
            'national_id' => '1234567890',
            'address' => 'Riyadh',
            'income_source' => 'Salary',
        ];
    }

    private function loginWithAccountOpeningService(bool $enabled = true): string
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
            'service_code' => 'ACCOUNT_OPENING_REQUEST',
            'service_name' => 'Account Opening Request',
            'category' => 'account_opening',
            'requires_password' => true,
            'enabled' => true,
        ]);

        BranchServiceSetting::create([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'enabled' => $enabled,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'device_id' => 'KIOSK-001',
            'username' => 'USR10001',
            'password' => 'Password1',
        ]);

        return $login->json('token');
    }
}
