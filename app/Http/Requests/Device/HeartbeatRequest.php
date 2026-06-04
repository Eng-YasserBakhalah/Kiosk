<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => 'required|string',
            'app_version' => 'required|string|max:50',
            'ip_address' => 'required|ip',
            'status' => 'nullable|in:ONLINE,DEGRADED,OFFLINE',
        ];
    }
}