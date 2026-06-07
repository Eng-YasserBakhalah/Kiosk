<?php

namespace App\Services\Bank;

use App\Models\ApiIntegrationLog;
use App\Models\AuthSession;

class BankApiAdapter
{
    public function accounts(AuthSession $session, string $requestId): array
    {
        return $this->recordMockCall($session, $requestId, 'accounts.list', [
            'bank_success' => true,
            'bank_reference' => 'MOCK-ACCOUNTS-'.$session->id,
            'bank_code' => '00',
            'message' => 'Approved',
            'payload' => [
                [
                    'id' => 'ACC-001',
                    'type' => 'CURRENT',
                    'currency' => 'SAR',
                    'masked_account' => '****1234',
                    'iban_masked' => 'SA****************1234',
                ],
                [
                    'id' => 'ACC-002',
                    'type' => 'SAVINGS',
                    'currency' => 'SAR',
                    'masked_account' => '****5678',
                    'iban_masked' => 'SA****************5678',
                ],
            ],
        ]);
    }

    public function balance(AuthSession $session, string $requestId, string $accountId): array
    {
        return $this->recordMockCall($session, $requestId, 'accounts.balance', [
            'bank_success' => true,
            'bank_reference' => 'MOCK-BALANCE-'.$accountId,
            'bank_code' => '00',
            'message' => 'Approved',
            'payload' => [
                'account_id' => $accountId,
                'available_balance' => '12500.75',
                'currency' => 'SAR',
                'masked_account' => '****'.substr($accountId, -4),
            ],
        ]);
    }

    public function statement(AuthSession $session, string $requestId, string $accountId): array
    {
        return $this->recordMockCall($session, $requestId, 'accounts.statement', [
            'bank_success' => true,
            'bank_reference' => 'MOCK-STMT-'.$accountId,
            'bank_code' => '00',
            'message' => 'Approved',
            'payload' => [
                'account_id' => $accountId,
                'transactions' => [
                    [
                        'date' => now()->subDay()->toDateString(),
                        'description' => 'POS Purchase',
                        'amount' => '-50.00',
                        'currency' => 'SAR',
                    ],
                    [
                        'date' => now()->subDays(2)->toDateString(),
                        'description' => 'Salary',
                        'amount' => '8500.00',
                        'currency' => 'SAR',
                    ],
                ],
            ],
        ]);
    }

    public function internalTransfer(AuthSession $session, string $requestId, array $payload): array
    {
        return $this->recordMockCall($session, $requestId, 'transfers.internal', [
            'bank_success' => true,
            'bank_reference' => 'MOCK-TRF-'.now()->format('YmdHis'),
            'bank_code' => '00',
            'message' => 'Approved',
            'payload' => [
                'from_account_id' => $payload['from_account_id'],
                'to_account_identifier' => $this->maskAccount($payload['to_account_identifier']),
                'amount' => number_format((float) $payload['amount'], 2, '.', ''),
                'currency' => $payload['currency'],
                'purpose' => $payload['purpose'] ?? null,
                'status' => 'APPROVED',
            ],
        ], 'POST', [
            'from_account_id' => $payload['from_account_id'],
            'to_account_identifier' => $this->maskAccount($payload['to_account_identifier']),
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
        ]);
    }

    private function recordMockCall(
        AuthSession $session,
        string $requestId,
        string $endpointKey,
        array $response,
        string $method = 'GET',
        ?array $maskedRequest = null
    ): array {
        ApiIntegrationLog::create([
            'request_id' => $requestId,
            'user_id' => $session->user_id,
            'terminal_device_id' => $session->terminal_device_id,
            'external_api_name' => 'mock_bank_core',
            'endpoint_key' => $endpointKey,
            'http_method' => $method,
            'response_status' => 200,
            'bank_response_code' => $response['bank_code'],
            'duration_ms' => 1,
            'success' => $response['bank_success'],
            'masked_request' => $maskedRequest ?? [
                'user_id' => $session->user_id,
            ],
            'masked_response' => $response['payload'],
        ]);

        return $response;
    }

    private function maskAccount(string $value): string
    {
        return '****'.substr($value, -4);
    }
}
