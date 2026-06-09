<?php

namespace App\Http\Controllers\Api\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Models\ServiceTransaction;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected SessionContextService $sessionContext
    ) {}

    public function index(Request $request): JsonResponse
    {
        $session = $this->sessionContext->current();

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        $validated = $request->validate([
            'transaction_type' => 'nullable|string|max:80',
            'status' => 'nullable|string|max:40',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $transactions = ServiceTransaction::query()
            ->with('receipt')
            ->where('user_id', $session->user_id)
            ->when($validated['transaction_type'] ?? null, function ($query, string $type): void {
                $query->where('transaction_type', $type);
            })
            ->when($validated['status'] ?? null, function ($query, string $status): void {
                $query->where('status', $status);
            })
            ->latest('created_at')
            ->limit($validated['limit'] ?? 20)
            ->get();

        return ApiResponse::success([
            'transactions' => $transactions->map(
                fn (ServiceTransaction $transaction): array => $this->formatTransaction($transaction)
            )->values(),
        ], 'Transactions loaded successfully');
    }

    public function show(string $requestId): JsonResponse
    {
        $session = $this->sessionContext->current();

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        $transaction = ServiceTransaction::query()
            ->with('receipt')
            ->where('request_id', $requestId)
            ->where('user_id', $session->user_id)
            ->first();

        if (! $transaction) {
            return ApiResponse::error(
                'TRANSACTION_NOT_FOUND',
                'Transaction not found',
                404,
                null,
                null,
                self::class
            );
        }

        return ApiResponse::success([
            'transaction' => $this->formatTransaction($transaction, includeMetadata: true),
        ], 'Transaction loaded successfully');
    }

    private function formatTransaction(ServiceTransaction $transaction, bool $includeMetadata = false): array
    {
        $data = [
            'request_id' => $transaction->request_id,
            'bank_reference' => $transaction->bank_reference,
            'transaction_type' => $transaction->transaction_type,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'status' => $transaction->status,
            'response_code' => $transaction->response_code,
            'response_message' => $transaction->response_message,
            'started_at' => $transaction->started_at,
            'completed_at' => $transaction->completed_at,
            'receipt' => $transaction->receipt ? [
                'receipt_number' => $transaction->receipt->receipt_number,
                'bank_reference' => $transaction->receipt->bank_reference,
                'printed_at' => $transaction->receipt->printed_at,
            ] : null,
        ];

        if ($includeMetadata) {
            $data['metadata'] = $transaction->metadata;
        }

        return $data;
    }
}
