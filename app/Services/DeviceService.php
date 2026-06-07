<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\TerminalDevice;

class DeviceService
{
    public function register(array $data): array
    {
        $branch = Branch::where('branch_code', $data['branch_code'])->first();

        if (! $branch) {
            return [
                'success' => false,
                'error_code' => 'BRANCH_NOT_FOUND',
                'message' => 'Branch not found',
                'status_code' => 404,
            ];
        }

        if ($branch->status !== 'ACTIVE') {
            return [
                'success' => false,
                'error_code' => 'BRANCH_DISABLED',
                'message' => 'Branch is not active',
                'status_code' => 403,
            ];
        }

        $device = TerminalDevice::updateOrCreate(
            ['device_code' => $data['device_code']],
            [
                'branch_id' => $branch->id,
                'serial_number' => $data['serial_number'] ?? null,
                'location_label' => $data['location_label'] ?? null,
                'ip_address' => $data['ip_address'] ?? request()->ip(),
                'app_version' => $data['app_version'] ?? null,
                'os_version' => $data['os_version'] ?? null,
                'status' => 'ACTIVE',
                'kiosk_mode_enabled' => $data['kiosk_mode_enabled'] ?? true,
            ]
        );

        AuditLog::create([
            'actor_type' => 'SYSTEM',
            'action' => 'DEVICE_REGISTERED',
            'entity_type' => TerminalDevice::class,
            'entity_id' => $device->id,
            'new_value' => [
                'device_code' => $device->device_code,
                'branch_code' => $branch->branch_code,
            ],
            'ip_address' => request()->ip(),
            'terminal_device_id' => $device->id,
        ]);

        return [
            'success' => true,
            'message' => 'Device registered successfully',
            'device' => $this->formatDevice($device->load('branch')),
            'status_code' => 200,
        ];
    }

    public function status(string $deviceId): array
    {
        $device = TerminalDevice::with('branch')
            ->where('device_code', $deviceId)
            ->orWhere('id', $deviceId)
            ->first();

        if (! $device) {
            return [
                'success' => false,
                'error_code' => 'DEVICE_NOT_REGISTERED',
                'message' => 'Device not registered',
                'status_code' => 404,
            ];
        }

        return [
            'success' => true,
            'message' => 'Device status loaded successfully',
            'device' => $this->formatDevice($device),
            'status_code' => 200,
        ];
    }

    public function enable(string $deviceId): array
    {
        return $this->changeStatus($deviceId, 'ACTIVE', 'DEVICE_ENABLED');
    }

    public function disable(string $deviceId): array
    {
        return $this->changeStatus($deviceId, 'INACTIVE', 'DEVICE_DISABLED');
    }

    public function heartbeat(array $data): array
    {
        $device = TerminalDevice::where('device_code', $data['device_id'])
            ->first();

        if (! $device) {
            return [
                'success' => false,
                'message' => 'Device not registered',
                'status_code' => 404,
            ];
        }

        if ($device->status !== 'ACTIVE') {
            return [
                'success' => false,
                'message' => 'Device inactive',
                'status_code' => 403,
            ];
        }

        $device->update([
            'app_version' => $data['app_version'],
            'ip_address' => $data['ip_address'],
            'last_heartbeat_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Heartbeat accepted',
            'device_id' => $device->device_code,
            'last_heartbeat_at' => $device->last_heartbeat_at,
            'status_code' => 200,
        ];
    }

    private function changeStatus(string $deviceId, string $status, string $action): array
    {
        $device = TerminalDevice::where('device_code', $deviceId)
            ->orWhere('id', $deviceId)
            ->first();

        if (! $device) {
            return [
                'success' => false,
                'error_code' => 'DEVICE_NOT_REGISTERED',
                'message' => 'Device not registered',
                'status_code' => 404,
            ];
        }

        $oldValue = ['status' => $device->status];

        $device->update(['status' => $status]);

        AuditLog::create([
            'actor_type' => 'SYSTEM',
            'action' => $action,
            'entity_type' => TerminalDevice::class,
            'entity_id' => $device->id,
            'old_value' => $oldValue,
            'new_value' => ['status' => $status],
            'ip_address' => request()->ip(),
            'terminal_device_id' => $device->id,
        ]);

        return [
            'success' => true,
            'message' => 'Device status updated successfully',
            'device' => $this->formatDevice($device->fresh('branch')),
            'status_code' => 200,
        ];
    }

    private function formatDevice(TerminalDevice $device): array
    {
        return [
            'id' => $device->id,
            'device_code' => $device->device_code,
            'serial_number' => $device->serial_number,
            'location_label' => $device->location_label,
            'ip_address' => $device->ip_address,
            'app_version' => $device->app_version,
            'os_version' => $device->os_version,
            'status' => $device->status,
            'kiosk_mode_enabled' => $device->kiosk_mode_enabled,
            'last_heartbeat_at' => $device->last_heartbeat_at,
            'branch' => $device->branch ? [
                'id' => $device->branch->id,
                'branch_code' => $device->branch->branch_code,
                'name' => $device->branch->name,
                'status' => $device->branch->status,
            ] : null,
        ];
    }
}
