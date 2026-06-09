<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class ListDevicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:ACTIVE,INACTIVE,OFFLINE',
            'branch_code' => 'nullable|string|max:60',
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }
}
