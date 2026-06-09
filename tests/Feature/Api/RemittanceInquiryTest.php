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

class RemittanceInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_inquire_remittance_status(): void
    {
        $token = $this->loginWithRemittanceService();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/remittances/inquiry', [
                'remittance_number' => 'REM-123456789',
                'phone' => '+966500000000',
            ])
            ->assertOk()
            ->assertJsonPath('data.remittance.status', 'AVAILABLE')
            ->assertJsonPath('data.remittance.remittance_number', '****6789');

        $this->assertDatabaseHas('service_transactions', [
            'transaction_type' => 'REMITTANCE_INQUIRY',
            'status' => 'SUCCESS',
        ]);

        $this->assertDatabaseHas('api_integration_logs', [
            'endpoint_key' => 'remittances.inquiry',
            'http_method' => 'POST',
            'success' => true,
        ]);
    }

    public function test_remittance_inquiry_rejected_when_service_disabled_for_branch(): void
    {
        $token = $this->loginWithRemittanceService(enabled: false);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/remittances/inquiry', [
                'remittance_number' => 'REM-123456789',
            ])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'SERVICE_NOT_ALLOWED_ON_DEVICE');
    }

    private function loginWithRemittanceService(bool $enabled = true): string
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
            'service_code' => 'REMITTANCE_INQUIRY',
            'service_name' => 'Remittance Inquiry',
            'category' => 'remittances',
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
