<?php

namespace App\Http\Controllers\Api\V1\Accounts;

use App\Http\Controllers\Controller;
use App\Models\DigitalService;
use App\Models\ServiceTransaction;
use App\Services\Bank\BankApiAdapter;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        protected SessionContextService $sessionContext,
        protected BankApiAdapter $bankApi
    ) {}

    public function index(): JsonResponse
    {
        $session = $this->activeSession();

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        $requestId = request()->attributes->get('request_id');
        $bankResponse = $this->bankApi->accounts($session, $requestId);

        $this->recordTransaction($session, 'ACCOUNTS_LIST', $bankResponse);

        return ApiResponse::success([
            'accounts' => $bankResponse['payload'],
        ], 'Accounts loaded successfully');
    }

    public function balance(string $accountId): JsonResponse
    {
        $session = $this->activeSession();

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        $requestId = request()->attributes->get('request_id');
        $bankResponse = $this->bankApi->balance($session, $requestId, $accountId);

        $this->recordTransaction($session, 'BALANCE_INQUIRY', $bankResponse);

        return ApiResponse::success([
            'balance' => $bankResponse['payload'],
        ], 'Balance loaded successfully');
    }

    public function statement(Request $request, string $accountId): JsonResponse
    {
        $session = $this->activeSession();

        if (! $session) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        $requestId = $request->attributes->get('request_id');
        $bankResponse = $this->bankApi->statement($session, $requestId, $accountId);

        $this->recordTransaction($session, 'SHORT_STATEMENT', $bankResponse);

        return ApiResponse::success([
            'statement' => $bankResponse['payload'],
        ], 'Statement loaded successfully');
    }

    private function activeSession()
    {
        return $this->sessionContext->current()?->load('terminalDevice');
    }

    private function recordTransaction($session, string $type, array $bankResponse): void
    {
        $service = DigitalService::where('service_code', $type)->first();

        ServiceTransaction::create([
            'request_id' => request()->attributes->get('request_id'),
            'user_id' => $session->user_id,
            'terminal_device_id' => $session->terminal_device_id,
            'branch_id' => $session->terminalDevice->branch_id,
            'service_id' => $service?->id,
            'bank_reference' => $bankResponse['bank_reference'],
            'transaction_type' => $type,
            'status' => $bankResponse['bank_success'] ? 'SUCCESS' : 'FAILED',
            'response_code' => $bankResponse['bank_code'],
            'response_message' => $bankResponse['message'],
            'completed_at' => now(),
            'metadata' => $bankResponse['payload'],
        ]);
    }
}
