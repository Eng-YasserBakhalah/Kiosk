<?php

namespace App\Http\Controllers\Api\V1\AccountOpening;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountOpening\StoreAccountOpeningRequest;
use App\Models\AccountOpeningRequest;
use App\Services\Bank\BankApiAdapter;
use App\Services\ServiceCatalogService;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AccountOpeningController extends Controller
{
    public function __construct(
        protected SessionContextService $sessionContext,
        protected ServiceCatalogService $serviceCatalog,
        protected BankApiAdapter $bankApi
    ) {}

    public function store(StoreAccountOpeningRequest $request): JsonResponse
    {
        $session = $this->sessionContext->current()?->load('terminalDevice');

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        if (! $this->serviceCatalog->isEnabledForBranch('ACCOUNT_OPENING_REQUEST', $session->terminalDevice->branch_id)) {
            return ApiResponse::error(
                'SERVICE_NOT_ALLOWED_ON_DEVICE',
                'Account opening request is not available for this device',
                403,
                null,
                'ACCOUNT_OPENING_REQUEST',
                self::class
            );
        }

        $payload = $request->validated();
        $payload['currency'] ??= 'SAR';

        $requestId = $request->attributes->get('request_id');
        $bankResponse = $this->bankApi->accountOpeningRequest($session, $requestId, $payload);

        $openingRequest = AccountOpeningRequest::create([
            'request_id' => $requestId,
            'user_id' => $session->user_id,
            'terminal_device_id' => $session->terminal_device_id,
            'branch_id' => $session->terminalDevice->branch_id,
            'tracking_number' => $bankResponse['payload']['tracking_number'],
            'bank_reference' => $bankResponse['bank_reference'],
            'account_type' => $payload['account_type'],
            'currency' => $payload['currency'],
            'full_name' => $payload['full_name'],
            'phone_masked' => $this->maskPhone($payload['phone']),
            'national_id_masked' => $this->maskReference($payload['national_id']),
            'address' => $payload['address'] ?? null,
            'income_source' => $payload['income_source'] ?? null,
            'status' => $bankResponse['payload']['status'],
            'metadata' => $bankResponse['payload'],
        ]);

        return ApiResponse::success([
            'request' => [
                'request_id' => $openingRequest->request_id,
                'tracking_number' => $openingRequest->tracking_number,
                'bank_reference' => $openingRequest->bank_reference,
                'account_type' => $openingRequest->account_type,
                'currency' => $openingRequest->currency,
                'status' => $openingRequest->status,
            ],
        ], 'Account opening request submitted successfully');
    }

    private function maskPhone(string $value): string
    {
        $length = strlen($value);

        if ($length <= 4) {
            return '****';
        }

        return substr($value, 0, 2).str_repeat('*', $length - 4).substr($value, -2);
    }

    private function maskReference(string $value): string
    {
        return '****'.substr($value, -4);
    }
}
