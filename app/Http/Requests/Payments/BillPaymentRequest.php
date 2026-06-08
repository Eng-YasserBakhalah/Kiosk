<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class BillPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => 'required|string|max:120',
            'biller_code' => 'required|string|max:80',
            'bill_number' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
        ];
    }
}
