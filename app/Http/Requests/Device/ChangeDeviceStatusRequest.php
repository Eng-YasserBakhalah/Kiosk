<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class ChangeDeviceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:200',
        ];
    }
}
