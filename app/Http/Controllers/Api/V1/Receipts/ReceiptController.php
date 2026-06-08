<?php

namespace App\Http\Controllers\Api\V1\Receipts;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Receipt;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $receipt = $this->findOwnedReceipt($reference, $session->user_id);

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

        return ApiResponse::success([
            'receipt' => $this->formatReceipt($receipt),
        ], 'Receipt loaded successfully');
    }

    public function print(string $reference, Request $request): JsonResponse
    {
        $session = $this->sessionContext->current();

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        $receipt = $this->findOwnedReceipt($reference, $session->user_id);

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

        $wasPrinted = $receipt->printed_at !== null;

        if (! $wasPrinted) {
            $receipt->forceFill(['printed_at' => now()])->save();
        }

        AuditLog::create([
            'actor_type' => 'USER',
            'actor_id' => $session->user_id,
            'action' => $wasPrinted ? 'RECEIPT_REPRINTED' : 'RECEIPT_PRINTED',
            'entity_type' => 'Receipt',
            'entity_id' => $receipt->id,
            'old_value' => ['printed_at' => $wasPrinted ? $receipt->printed_at : null],
            'new_value' => ['printed_at' => $receipt->printed_at],
            'ip_address' => $request->ip(),
            'terminal_device_id' => $session->terminal_device_id,
        ]);

        return ApiResponse::success([
            'receipt' => $this->formatReceipt($receipt),
            'print' => [
                'printed' => true,
                'first_print' => ! $wasPrinted,
            ],
        ], $wasPrinted ? 'Receipt was already printed' : 'Receipt print recorded successfully');
    }

    private function findOwnedReceipt(string $reference, string $userId): ?Receipt
    {
        return Receipt::with('transaction')
            ->where(function ($query) use ($reference): void {
                $query->where('receipt_number', $reference)
                    ->orWhere('bank_reference', $reference);
            })
            ->whereHas('transaction', function ($query) use ($userId): void {
                $query->where('user_id', $userId);
            })
            ->first();
    }

    private function formatReceipt(Receipt $receipt): array
    {
        return [
            'receipt_number' => $receipt->receipt_number,
            'bank_reference' => $receipt->bank_reference,
            'receipt_type' => $receipt->receipt_type,
            'payload' => $receipt->masked_payload,
            'qr_payload' => $receipt->qr_payload,
            'printed_at' => $receipt->printed_at,
            'created_at' => $receipt->created_at,
        ];
    }
}
