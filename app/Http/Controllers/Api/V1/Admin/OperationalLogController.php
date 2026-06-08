<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegrationLog;
use App\Models\AuditLog;
use App\Models\ErrorLog;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperationalLogController extends Controller
{
    public function __construct(
        protected SessionContextService $sessionContext
    ) {}

    public function auditLogs(Request $request): JsonResponse
    {
        if (! $this->sessionContext->current()) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        $validated = $request->validate([
            'action' => 'nullable|string|max:120',
            'entity_type' => 'nullable|string|max:80',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $logs = AuditLog::query()
            ->when($validated['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($validated['entity_type'] ?? null, fn ($query, string $entityType) => $query->where('entity_type', $entityType))
            ->latest()
            ->limit($validated['limit'] ?? 50)
            ->get();

        return ApiResponse::success([
            'audit_logs' => $logs->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'actor_type' => $log->actor_type,
                'actor_id' => $log->actor_id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'old_value' => $log->old_value,
                'new_value' => $log->new_value,
                'ip_address' => $log->ip_address,
                'terminal_device_id' => $log->terminal_device_id,
                'created_at' => $log->created_at,
            ])->values(),
        ], 'Audit logs loaded successfully');
    }

    public function integrationLogs(Request $request): JsonResponse
    {
        if (! $this->sessionContext->current()) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        $validated = $request->validate([
            'endpoint_key' => 'nullable|string|max:120',
            'success' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $logs = ApiIntegrationLog::query()
            ->when($validated['endpoint_key'] ?? null, fn ($query, string $endpointKey) => $query->where('endpoint_key', $endpointKey))
            ->when(array_key_exists('success', $validated), fn ($query) => $query->where('success', $validated['success']))
            ->latest()
            ->limit($validated['limit'] ?? 50)
            ->get();

        return ApiResponse::success([
            'integration_logs' => $logs->map(fn (ApiIntegrationLog $log): array => [
                'id' => $log->id,
                'request_id' => $log->request_id,
                'user_id' => $log->user_id,
                'terminal_device_id' => $log->terminal_device_id,
                'external_api_name' => $log->external_api_name,
                'endpoint_key' => $log->endpoint_key,
                'http_method' => $log->http_method,
                'response_status' => $log->response_status,
                'bank_response_code' => $log->bank_response_code,
                'duration_ms' => $log->duration_ms,
                'success' => $log->success,
                'error_message' => $log->error_message,
                'masked_request' => $log->masked_request,
                'masked_response' => $log->masked_response,
                'created_at' => $log->created_at,
            ])->values(),
        ], 'Integration logs loaded successfully');
    }

    public function errorLogs(Request $request): JsonResponse
    {
        if (! $this->sessionContext->current()) {
            return ApiResponse::error('SESSION_EXPIRED', 'Session is not active', 401);
        }

        $validated = $request->validate([
            'error_code' => 'nullable|string|max:80',
            'service_code' => 'nullable|string|max:80',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $logs = ErrorLog::query()
            ->when($validated['error_code'] ?? null, fn ($query, string $errorCode) => $query->where('error_code', $errorCode))
            ->when($validated['service_code'] ?? null, fn ($query, string $serviceCode) => $query->where('service_code', $serviceCode))
            ->latest()
            ->limit($validated['limit'] ?? 50)
            ->get();

        return ApiResponse::success([
            'error_logs' => $logs->map(fn (ErrorLog $log): array => [
                'id' => $log->id,
                'request_id' => $log->request_id,
                'user_id' => $log->user_id,
                'terminal_device_id' => $log->terminal_device_id,
                'service_code' => $log->service_code,
                'error_type' => $log->error_type,
                'error_level' => $log->error_level,
                'error_code' => $log->error_code,
                'error_message' => $log->error_message,
                'source' => $log->source,
                'created_at' => $log->created_at,
            ])->values(),
        ], 'Error logs loaded successfully');
    }
}
