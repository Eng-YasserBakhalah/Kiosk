<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\ServiceTransaction;
use Illuminate\Support\Str;

class ReceiptService
{
    public function createForTransaction(ServiceTransaction $transaction): Receipt
    {
        return Receipt::create([
            'transaction_id' => $transaction->id,
            'receipt_number' => $this->receiptNumber(),
            'bank_reference' => $transaction->bank_reference,
            'receipt_type' => 'DIGITAL',
            'masked_payload' => [
                'request_id' => $transaction->request_id,
                'transaction_type' => $transaction->transaction_type,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'bank_reference' => $transaction->bank_reference,
            ],
            'qr_payload' => $transaction->bank_reference,
        ]);
    }

    private function receiptNumber(): string
    {
        do {
            $number = 'RCT-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        } while (Receipt::where('receipt_number', $number)->exists());

        return $number;
    }
}
