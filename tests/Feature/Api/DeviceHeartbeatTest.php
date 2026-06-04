<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\TerminalDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_device_can_send_heartbeat(): void
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

        $response = $this->postJson('/api/v1/device/heartbeat', [
            'device_id' => 'KIOSK-001',
            'app_version' => '1.0.0',
            'ip_address' => '127.0.0.1',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'device_id' => 'KIOSK-001',
            ]);

        $this->assertDatabaseHas('terminal_devices', [
            'device_code' => 'KIOSK-001',
            'app_version' => '1.0.0',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_inactive_device_heartbeat_is_rejected(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-001',
            'name' => 'Main Branch',
            'status' => 'ACTIVE',
        ]);

        TerminalDevice::create([
            'branch_id' => $branch->id,
            'device_code' => 'KIOSK-001',
            'status' => 'INACTIVE',
        ]);

        $this->postJson('/api/v1/device/heartbeat', [
            'device_id' => 'KIOSK-001',
            'app_version' => '1.0.0',
            'ip_address' => '127.0.0.1',
        ])->assertForbidden();
    }
}
