<?php

namespace App\Http\Requests\Remittances;

use Illuminate\Foundation\Http\FormRequest;

class RemittanceInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remittance_number' => 'required|string|max:120',
            'phone' => 'nullable|string|regex:/^\+?[0-9]{9,20}$/',
        ];
    }
}
