<?php

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class StartEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => 'required|string|max:100',
            'customer_identifier' => 'required|string|max:100',
            'phone' => 'required|string|min:9|max:20',
        ];
    }
}
