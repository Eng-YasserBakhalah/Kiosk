<?php

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_id' => 'required|uuid',
            'otp' => 'required|string|regex:/^[0-9]{6}$/',
        ];
    }
}
