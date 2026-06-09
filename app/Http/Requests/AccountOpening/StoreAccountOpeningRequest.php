<?php

namespace App\Http\Requests\AccountOpening;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_type' => 'required|string|max:80',
            'currency' => 'nullable|string|size:3',
            'full_name' => 'required|string|max:180',
            'phone' => 'required|string|regex:/^\+?[0-9]{9,20}$/',
            'national_id' => 'required|string|max:40',
            'address' => 'nullable|string|max:500',
            'income_source' => 'nullable|string|max:120',
        ];
    }
}
