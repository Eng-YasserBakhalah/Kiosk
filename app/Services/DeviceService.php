<?php

namespace App\Services;

use App\Models\TerminalDevice;

class DeviceService
{
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
}
