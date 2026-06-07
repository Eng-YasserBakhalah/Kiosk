<?php

namespace App\Http\Controllers\Api\V1\Receipts;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReceiptController extends Controller
{
    public function __construct(
        protected SessionContextService $sessionContext
    ) {}

    public function show(string $reference): JsonResponse
    {
        $session = $this->sessionContext->current();

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        $receipt = Receipt::with('transaction')
            ->where('receipt_number', $reference)
            ->orWhere('bank_reference', $reference)
            ->first();

        if (! $receipt) {
            return ApiResponse::error(
                'RECEIPT_NOT_FOUND',
                'Receipt not found',
                404,
                null,
                null,
                self::class
            );
        }

        if ($receipt->transaction?->user_id !== $session->user_id) {
            return ApiResponse::error(
                'RECEIPT_NOT_FOUND',
                'Receipt not found',
                404,
                null,
                null,
                self::class
            );
        }

        return ApiResponse::success([
            'receipt' => [
                'receipt_number' => $receipt->receipt_number,
                'bank_reference' => $receipt->bank_reference,
                'receipt_type' => $receipt->receipt_type,
                'payload' => $receipt->masked_payload,
                'qr_payload' => $receipt->qr_payload,
                'printed_at' => $receipt->printed_at,
                'created_at' => $receipt->created_at,
            ],
        ], 'Receipt loaded successfully');
    }
}
