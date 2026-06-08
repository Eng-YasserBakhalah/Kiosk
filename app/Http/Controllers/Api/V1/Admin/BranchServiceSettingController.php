<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBranchServiceSettingRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchServiceSetting;
use App\Models\DigitalService;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BranchServiceSettingController extends Controller
{
    public function __construct(
        protected SessionContextService $sessionContext
    ) {}

    public function index(string $branchCode): JsonResponse
    {
        $branch = Branch::where('branch_code', $branchCode)->first();

        if (! $branch) {
            return ApiResponse::error(
                'BRANCH_NOT_FOUND',
                'Branch not found',
                404,
                null,
                null,
                self::class
            );
        }

        $settings = BranchServiceSetting::query()
            ->with('service')
            ->where('branch_id', $branch->id)
            ->get();

        return ApiResponse::success([
            'branch' => [
                'branch_code' => $branch->branch_code,
                'name' => $branch->name,
                'status' => $branch->status,
            ],
            'services' => $settings->map(fn (BranchServiceSetting $setting): array => $this->formatSetting($setting))->values(),
        ], 'Branch service settings loaded successfully');
    }

    public function update(
        UpdateBranchServiceSettingRequest $request,
        string $branchCode,
        string $serviceCode
    ): JsonResponse {
        $branch = Branch::where('branch_code', $branchCode)->first();

        if (! $branch) {
            return ApiResponse::error(
                'BRANCH_NOT_FOUND',
                'Branch not found',
                404,
                null,
                null,
                self::class
            );
        }

        $service = DigitalService::where('service_code', $serviceCode)->first();

        if (! $service) {
            return ApiResponse::error(
                'SERVICE_NOT_FOUND',
                'Service not found',
                404,
                null,
                $serviceCode,
                self::class
            );
        }

        $setting = BranchServiceSetting::firstOrCreate(
            [
                'branch_id' => $branch->id,
                'service_id' => $service->id,
            ],
            [
                'enabled' => true,
            ]
        );

        $oldValue = [
            'enabled' => $setting->enabled,
            'daily_limit' => $setting->daily_limit,
        ];

        $setting->fill($request->validated())->save();

        AuditLog::create([
            'actor_type' => 'USER',
            'actor_id' => $this->sessionContext->current()?->user_id,
            'action' => 'BRANCH_SERVICE_SETTING_UPDATED',
            'entity_type' => 'BranchServiceSetting',
            'entity_id' => $setting->id,
            'old_value' => $oldValue,
            'new_value' => [
                'enabled' => $setting->enabled,
                'daily_limit' => $setting->daily_limit,
            ],
            'ip_address' => $request->ip(),
            'terminal_device_id' => $this->sessionContext->current()?->terminal_device_id,
        ]);

        return ApiResponse::success([
            'setting' => $this->formatSetting($setting->load('service')),
        ], 'Branch service setting updated successfully');
    }

    private function formatSetting(BranchServiceSetting $setting): array
    {
        return [
            'service_code' => $setting->service?->service_code,
            'service_name' => $setting->service?->service_name,
            'category' => $setting->service?->category,
            'enabled' => $setting->enabled,
            'daily_limit' => $setting->daily_limit,
        ];
    }
}
