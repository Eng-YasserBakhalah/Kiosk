<?php

namespace App\Http\Requests\Transfers;

use Illuminate\Foundation\Http\FormRequest;

class InternalTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => 'required|string|max:120',
            'to_account_identifier' => 'required|string|max:120|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'purpose' => 'nullable|string|max:200',
            'otp' => 'nullable|string|regex:/^[0-9]{6}$/',
        ];
    }
}
