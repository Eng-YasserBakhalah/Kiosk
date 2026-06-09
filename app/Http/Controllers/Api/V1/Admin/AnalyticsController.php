<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountOpeningRequest;
use App\Models\ApiIntegrationLog;
use App\Models\ErrorLog;
use App\Models\ServiceTransaction;
use App\Models\TerminalDevice;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function summary(): JsonResponse
    {
        return ApiResponse::success([
            'devices' => $this->deviceSummary(),
            'transactions' => $this->transactionSummary(),
            'integrations' => $this->integrationSummary(),
            'errors' => $this->errorSummary(),
            'account_opening_requests' => $this->accountOpeningSummary(),
        ], 'Analytics summary loaded successfully');
    }

    private function deviceSummary(): array
    {
        $staleHeartbeatCutoff = now()->subMinutes(5);

        return [
            'total' => TerminalDevice::count(),
            'active' => TerminalDevice::where('status', 'ACTIVE')->count(),
            'inactive' => TerminalDevice::where('status', 'INACTIVE')->count(),
            'offline' => TerminalDevice::where('status', 'OFFLINE')->count(),
            'stale_heartbeat' => TerminalDevice::where('status', 'ACTIVE')
                ->where(function ($query) use ($staleHeartbeatCutoff): void {
                    $query->whereNull('last_heartbeat_at')
                        ->orWhere('last_heartbeat_at', '<', $staleHeartbeatCutoff);
                })
                ->count(),
        ];
    }

    private function transactionSummary(): array
    {
        $today = now()->toDateString();

        return [
            'today_total' => ServiceTransaction::whereDate('created_at', $today)->count(),
            'today_success' => ServiceTransaction::whereDate('created_at', $today)->where('status', 'SUCCESS')->count(),
            'today_failed' => ServiceTransaction::whereDate('created_at', $today)->where('status', 'FAILED')->count(),
            'today_success_amount' => (string) ServiceTransaction::whereDate('created_at', $today)
                ->where('status', 'SUCCESS')
                ->sum('amount'),
            'by_type' => ServiceTransaction::query()
                ->select('transaction_type', DB::raw('count(*) as total'))
                ->whereDate('created_at', $today)
                ->groupBy('transaction_type')
                ->orderBy('transaction_type')
                ->get()
                ->map(fn (ServiceTransaction $transaction): array => [
                    'transaction_type' => $transaction->transaction_type,
                    'total' => (int) $transaction->total,
                ])
                ->values(),
        ];
    }

    private function integrationSummary(): array
    {
        return [
            'total' => ApiIntegrationLog::count(),
            'success' => ApiIntegrationLog::where('success', true)->count(),
            'failed' => ApiIntegrationLog::where('success', false)->count(),
            'average_duration_ms' => round((float) ApiIntegrationLog::avg('duration_ms'), 2),
            'by_endpoint' => ApiIntegrationLog::query()
                ->select('endpoint_key', DB::raw('count(*) as total'))
                ->groupBy('endpoint_key')
                ->orderBy('endpoint_key')
                ->get()
                ->map(fn (ApiIntegrationLog $log): array => [
                    'endpoint_key' => $log->endpoint_key,
                    'total' => (int) $log->total,
                ])
                ->values(),
        ];
    }

    private function errorSummary(): array
    {
        $today = now()->toDateString();

        return [
            'today_total' => ErrorLog::whereDate('created_at', $today)->count(),
            'by_code' => ErrorLog::query()
                ->select('error_code', DB::raw('count(*) as total'))
                ->whereDate('created_at', $today)
                ->groupBy('error_code')
                ->orderBy('error_code')
                ->get()
                ->map(fn (ErrorLog $log): array => [
                    'error_code' => $log->error_code,
                    'total' => (int) $log->total,
                ])
                ->values(),
        ];
    }

    private function accountOpeningSummary(): array
    {
        return [
            'total' => AccountOpeningRequest::count(),
            'submitted' => AccountOpeningRequest::where('status', 'SUBMITTED')->count(),
            'approved' => AccountOpeningRequest::where('status', 'APPROVED')->count(),
            'rejected' => AccountOpeningRequest::where('status', 'REJECTED')->count(),
        ];
    }
}
