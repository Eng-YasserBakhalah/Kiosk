<?php

namespace App\Http\Controllers\Api\V1\Remittances;

use App\Http\Controllers\Controller;
use App\Http\Requests\Remittances\RemittanceInquiryRequest;
use App\Models\DigitalService;
use App\Models\ServiceTransaction;
use App\Services\Bank\BankApiAdapter;
use App\Services\ServiceCatalogService;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RemittanceController extends Controller
{
    public function __construct(
        protected SessionContextService $sessionContext,
        protected ServiceCatalogService $serviceCatalog,
        protected BankApiAdapter $bankApi
    ) {}

    public function inquiry(RemittanceInquiryRequest $request): JsonResponse
    {
        $session = $this->sessionContext->current()?->load('terminalDevice');

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        if (! $this->serviceCatalog->isEnabledForBranch('REMITTANCE_INQUIRY', $session->terminalDevice->branch_id)) {
            return ApiResponse::error(
                'SERVICE_NOT_ALLOWED_ON_DEVICE',
                'Remittance inquiry is not available for this device',
                403,
                null,
                'REMITTANCE_INQUIRY',
                self::class
            );
        }

        $requestId = $request->attributes->get('request_id');
        $bankResponse = $this->bankApi->remittanceInquiry($session, $requestId, $request->validated());
        $service = DigitalService::where('service_code', 'REMITTANCE_INQUIRY')->first();

        ServiceTransaction::create([
            'request_id' => $requestId,
            'user_id' => $session->user_id,
            'terminal_device_id' => $session->terminal_device_id,
            'branch_id' => $session->terminalDevice->branch_id,
            'service_id' => $service?->id,
            'bank_reference' => $bankResponse['bank_reference'],
            'transaction_type' => 'REMITTANCE_INQUIRY',
            'status' => $bankResponse['bank_success'] ? 'SUCCESS' : 'FAILED',
            'response_code' => $bankResponse['bank_code'],
            'response_message' => $bankResponse['message'],
            'completed_at' => now(),
            'metadata' => $bankResponse['payload'],
        ]);

        return ApiResponse::success([
            'remittance' => $bankResponse['payload'],
        ], 'Remittance inquiry loaded successfully');
    }
}
