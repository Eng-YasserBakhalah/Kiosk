<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\DigitalServiceUser;
use App\Models\TerminalDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_can_be_registered_for_active_branch(): void
    {
        Branch::create([
            'branch_code' => 'BR-001',
            'name' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        $this->postJson('/api/v1/devices/register', [
            'branch_code' => 'BR-001',
            'device_code' => 'DEV001',
            'serial_number' => 'SN-001',
            'location_label' => 'Lobby',
            'app_version' => '1.0.0',
            'os_version' => 'Windows 11',
        ])
            ->assertOk()
            ->assertJsonPath('data.device.device_code', 'DEV001')
            ->assertJsonPath('data.device.branch.branch_code', 'BR-001');

        $this->assertDatabaseHas('terminal_devices', [
            'device_code' => 'DEV001',
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'DEVICE_REGISTERED',
        ]);
    }

    public function test_device_status_can_be_loaded(): void
    {
        $device = $this->createDevice();

        $this->getJson("/api/v1/devices/{$device->device_code}/status")
            ->assertOk()
            ->assertJsonPath('data.device.device_code', 'DEV001')
            ->assertJsonPath('data.device.status', 'ACTIVE');
    }

    public function test_authenticated_admin_can_disable_and_enable_device(): void
    {
        $device = $this->createDevice();
        $token = $this->login($device);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/admin/devices/{$device->device_code}/disable")
            ->assertOk()
            ->assertJsonPath('data.device.status', 'INACTIVE');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/admin/devices/{$device->device_code}/enable")
            ->assertOk()
            ->assertJsonPath('data.device.status', 'ACTIVE');
    }

    private function createDevice(): TerminalDevice
    {
        $branch = Branch::create([
            'branch_code' => 'BR-001',
            'name' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        return TerminalDevice::create([
            'branch_id' => $branch->id,
            'device_code' => 'DEV001',
            'status' => 'ACTIVE',
        ]);
    }

    private function login(TerminalDevice $device): string
    {
        DigitalServiceUser::create([
            'bank_customer_ref' => 'BANK-100001',
            'username' => 'USR10001',
            'phone_masked' => '+966*******000',
            'password_hash' => Hash::make('Password1'),
            'status' => 'ACTIVE',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'device_id' => $device->device_code,
            'username' => 'USR10001',
            'password' => 'Password1',
        ]);

        return $login->json('token');
    }
}
