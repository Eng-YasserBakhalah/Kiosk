<?php

namespace Tests\Feature\Api;

use App\Models\ApiIntegrationLog;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DigitalServiceUser;
use App\Models\ErrorLog;
use App\Models\TerminalDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperationalLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_audit_logs(): void
    {
        [$token, $device] = $this->loginUser();

        AuditLog::create([
            'actor_type' => 'USER',
            'action' => 'RECEIPT_PRINTED',
            'entity_type' => 'Receipt',
            'entity_id' => 'RCT-001',
            'terminal_device_id' => $device->id,
        ]);

        AuditLog::create([
            'actor_type' => 'SYSTEM',
            'action' => 'DEVICE_HEARTBEAT',
            'entity_type' => 'TerminalDevice',
            'entity_id' => $device->id,
            'terminal_device_id' => $device->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/audit-logs?action=RECEIPT_PRINTED')
            ->assertOk()
            ->assertJsonCount(1, 'data.audit_logs')
            ->assertJsonPath('data.audit_logs.0.action', 'RECEIPT_PRINTED');
    }

    public function test_authenticated_user_can_list_integration_logs(): void
    {
        [$token, $device, $user] = $this->loginUser();

        ApiIntegrationLog::create([
            'request_id' => 'REQ-001',
            'user_id' => $user->id,
            'terminal_device_id' => $device->id,
            'external_api_name' => 'mock_bank_core',
            'endpoint_key' => 'payments.bill_payment',
            'http_method' => 'POST',
            'response_status' => 200,
            'bank_response_code' => '00',
            'duration_ms' => 1,
            'success' => true,
            'masked_request' => ['amount' => 150],
            'masked_response' => ['status' => 'APPROVED'],
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/integration-logs?endpoint_key=payments.bill_payment&success=1')
            ->assertOk()
            ->assertJsonCount(1, 'data.integration_logs')
            ->assertJsonPath('data.integration_logs.0.endpoint_key', 'payments.bill_payment')
            ->assertJsonPath('data.integration_logs.0.success', true);
    }

    public function test_authenticated_user_can_list_error_logs(): void
    {
        [$token, $device, $user] = $this->loginUser();

        ErrorLog::create([
            'request_id' => 'REQ-001',
            'user_id' => $user->id,
            'terminal_device_id' => $device->id,
            'service_code' => 'BILL_PAYMENT',
            'error_type' => 'IDEMPOTENCY_KEY_REQUIRED',
            'error_level' => 'ERROR',
            'error_code' => 'IDEMPOTENCY_KEY_REQUIRED',
            'error_message' => 'Idempotency-Key header is required',
            'source' => 'test',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/error-logs?service_code=BILL_PAYMENT')
            ->assertOk()
            ->assertJsonCount(1, 'data.error_logs')
            ->assertJsonPath('data.error_logs.0.error_code', 'IDEMPOTENCY_KEY_REQUIRED');
    }

    private function loginUser(): array
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

        return [$login->json('token'), $device, $user];
    }
}
