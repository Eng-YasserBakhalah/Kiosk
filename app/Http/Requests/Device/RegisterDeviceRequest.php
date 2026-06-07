<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_code' => 'required|string|max:50',
            'device_code' => 'required|string|max:80',
            'serial_number' => 'nullable|string|max:120',
            'location_label' => 'nullable|string|max:200',
            'ip_address' => 'nullable|ip',
            'app_version' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:80',
            'kiosk_mode_enabled' => 'nullable|boolean',
        ];
    }
}
