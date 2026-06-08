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

class BranchServiceSettingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_branch_service_settings(): void
    {
        $token = $this->loginWithServices('ADMIN');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/branches/BR-001/services')
            ->assertOk()
            ->assertJsonPath('data.branch.branch_code', 'BR-001')
            ->assertJsonCount(2, 'data.services');
    }

    public function test_admin_can_disable_service_for_branch(): void
    {
        $token = $this->loginWithServices('ADMIN');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/admin/branches/BR-001/services/BALANCE_INQUIRY', [
                'enabled' => false,
                'daily_limit' => 1000,
            ])
            ->assertOk()
            ->assertJsonPath('data.setting.service_code', 'BALANCE_INQUIRY')
            ->assertJsonPath('data.setting.enabled', false);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'BRANCH_SERVICE_SETTING_UPDATED',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/services')
            ->assertOk()
            ->assertJsonMissing([
                'code' => 'BALANCE_INQUIRY',
            ]);
    }

    public function test_customer_cannot_update_branch_service_settings(): void
    {
        $token = $this->loginWithServices();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/admin/branches/BR-001/services/BALANCE_INQUIRY', [
                'enabled' => false,
            ])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    private function loginWithServices(string $role = 'CUSTOMER'): string
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
            'role' => $role,
        ]);

        foreach (['ACCOUNTS_LIST', 'BALANCE_INQUIRY'] as $serviceCode) {
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
