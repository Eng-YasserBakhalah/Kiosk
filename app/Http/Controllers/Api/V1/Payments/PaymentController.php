<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\BillPaymentRequest;
use App\Http\Requests\Payments\MobileTopUpRequest;
use App\Models\DigitalService;
use App\Models\ServiceTransaction;
use App\Services\Bank\BankApiAdapter;
use App\Services\IdempotencyService;
use App\Services\ReceiptService;
use App\Services\ServiceCatalogService;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected SessionContextService $sessionContext,
        protected ServiceCatalogService $serviceCatalog,
        protected IdempotencyService $idempotency,
        protected BankApiAdapter $bankApi,
        protected ReceiptService $receiptService
    ) {}

    public function mobileTopUp(MobileTopUpRequest $request): JsonResponse
    {
        $session = $this->sessionContext->current()?->load('terminalDevice');

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        if (! $this->serviceCatalog->isEnabledForBranch('MOBILE_TOPUP', $session->terminalDevice->branch_id)) {
            return ApiResponse::error(
                'SERVICE_NOT_ALLOWED_ON_DEVICE',
                'Mobile top-up is not available for this device',
                403,
                null,
                'MOBILE_TOPUP',
                self::class
            );
        }

        if ($missing = $this->idempotency->requireKey()) {
            return ApiResponse::error(
                $missing['code'],
                $missing['message'],
                $missing['status'],
                null,
                'MOBILE_TOPUP',
                self::class
            );
        }

        $idempotencyKey = $this->idempotency->key();
        $existing = $this->idempotency->existing($idempotencyKey);

        if ($existing) {
            $existing->load('receipt');

            return ApiResponse::success([
                'duplicate' => true,
                'transaction' => $this->formatTransaction($existing),
                'receipt' => $existing->receipt ? [
                    'receipt_number' => $existing->receipt->receipt_number,
                ] : null,
            ], 'Duplicate request returned existing transaction');
        }

        $payload = $request->validated();
        $payload['currency'] ??= 'SAR';

        $requestId = $request->attributes->get('request_id');
        $bankResponse = $this->bankApi->mobileTopUp($session, $requestId, $payload);
        $service = DigitalService::where('service_code', 'MOBILE_TOPUP')->first();

        $transaction = ServiceTransaction::create([
            'request_id' => $requestId,
            'idempotency_key' => $idempotencyKey,
            'user_id' => $session->user_id,
            'terminal_device_id' => $session->terminal_device_id,
            'branch_id' => $session->terminalDevice->branch_id,
            'service_id' => $service?->id,
            'bank_reference' => $bankResponse['bank_reference'],
            'transaction_type' => 'MOBILE_TOPUP',
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'status' => $bankResponse['bank_success'] ? 'SUCCESS' : 'FAILED',
            'response_code' => $bankResponse['bank_code'],
            'response_message' => $bankResponse['message'],
            'completed_at' => now(),
            'metadata' => $bankResponse['payload'],
        ]);

        $receipt = $bankResponse['bank_success']
            ? $this->receiptService->createForTransaction($transaction)
            : null;

        return ApiResponse::success([
            'transaction' => $this->formatTransaction($transaction),
            'receipt' => $receipt ? [
                'receipt_number' => $receipt->receipt_number,
            ] : null,
        ], 'Mobile top-up completed successfully');
    }

    public function billPayment(BillPaymentRequest $request): JsonResponse
    {
        $session = $this->sessionContext->current()?->load('terminalDevice');

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        if (! $this->serviceCatalog->isEnabledForBranch('BILL_PAYMENT', $session->terminalDevice->branch_id)) {
            return ApiResponse::error(
                'SERVICE_NOT_ALLOWED_ON_DEVICE',
                'Bill payment is not available for this device',
                403,
                null,
                'BILL_PAYMENT',
                self::class
            );
        }

        if ($missing = $this->idempotency->requireKey()) {
            return ApiResponse::error(
                $missing['code'],
                $missing['message'],
                $missing['status'],
                null,
                'BILL_PAYMENT',
                self::class
            );
        }

        $idempotencyKey = $this->idempotency->key();
        $existing = $this->idempotency->existing($idempotencyKey);

        if ($existing) {
            $existing->load('receipt');

            return ApiResponse::success([
                'duplicate' => true,
                'transaction' => $this->formatTransaction($existing),
                'receipt' => $existing->receipt ? [
                    'receipt_number' => $existing->receipt->receipt_number,
                ] : null,
            ], 'Duplicate request returned existing transaction');
        }

        $payload = $request->validated();
        $payload['currency'] ??= 'SAR';

        $requestId = $request->attributes->get('request_id');
        $bankResponse = $this->bankApi->billPayment($session, $requestId, $payload);
        $service = DigitalService::where('service_code', 'BILL_PAYMENT')->first();

        $transaction = ServiceTransaction::create([
            'request_id' => $requestId,
            'idempotency_key' => $idempotencyKey,
            'user_id' => $session->user_id,
            'terminal_device_id' => $session->terminal_device_id,
            'branch_id' => $session->terminalDevice->branch_id,
            'service_id' => $service?->id,
            'bank_reference' => $bankResponse['bank_reference'],
            'transaction_type' => 'BILL_PAYMENT',
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'status' => $bankResponse['bank_success'] ? 'SUCCESS' : 'FAILED',
            'response_code' => $bankResponse['bank_code'],
            'response_message' => $bankResponse['message'],
            'completed_at' => now(),
            'metadata' => $bankResponse['payload'],
        ]);

        $receipt = $bankResponse['bank_success']
            ? $this->receiptService->createForTransaction($transaction)
            : null;

        return ApiResponse::success([
            'transaction' => $this->formatTransaction($transaction),
            'receipt' => $receipt ? [
                'receipt_number' => $receipt->receipt_number,
            ] : null,
        ], 'Bill payment completed successfully');
    }

    private function formatTransaction(ServiceTransaction $transaction): array
    {
        return [
            'request_id' => $transaction->request_id,
            'bank_reference' => $transaction->bank_reference,
            'transaction_type' => $transaction->transaction_type,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'status' => $transaction->status,
            'response_code' => $transaction->response_code,
            'response_message' => $transaction->response_message,
        ];
    }
}
