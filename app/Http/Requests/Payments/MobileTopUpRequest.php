<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class MobileTopUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => 'required|string|max:120',
            'operator' => 'required|string|max:80',
            'phone' => 'required|string|regex:/^\+?[0-9]{9,20}$/',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
        ];
    }
}
