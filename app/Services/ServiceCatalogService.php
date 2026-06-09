<?php

namespace App\Services;

use App\Models\BranchServiceSetting;
use App\Models\DigitalService;
use App\Models\ServiceTransaction;

class ServiceCatalogService
{
    public function forBranch(string $branchId): array
    {
        $allowedServiceIds = BranchServiceSetting::query()
            ->where('branch_id', $branchId)
            ->where('enabled', true)
            ->pluck('service_id');

        return DigitalService::query()
            ->where('enabled', true)
            ->whereIn('id', $allowedServiceIds)
            ->orderBy('category')
            ->orderBy('service_name')
            ->get()
            ->map(fn (DigitalService $service): array => [
                'code' => $service->service_code,
                'name' => $service->service_name,
                'category' => $service->category,
                'requires_otp' => $service->requires_otp,
                'requires_password' => $service->requires_password,
                'requires_biometric' => $service->requires_biometric,
                'min_amount' => $service->min_amount,
                'max_amount' => $service->max_amount,
            ])
            ->all();
    }

    public function isEnabledForBranch(string $serviceCode, string $branchId): bool
    {
        return DigitalService::query()
            ->where('service_code', $serviceCode)
            ->where('enabled', true)
            ->whereHas('branchSettings', fn ($query) => $query
                ->where('branch_id', $branchId)
                ->where('enabled', true)
            )
            ->exists();
    }

    public function validateAmountForBranch(string $serviceCode, string $branchId, float $amount): ?array
    {
        $setting = BranchServiceSetting::query()
            ->with('service')
            ->where('branch_id', $branchId)
            ->whereHas('service', fn ($query) => $query->where('service_code', $serviceCode))
            ->first();

        if (! $setting?->service) {
            return [
                'code' => 'SERVICE_NOT_ALLOWED_ON_DEVICE',
                'message' => 'Service is not available for this device',
                'status' => 403,
            ];
        }

        $service = $setting->service;

        if ($service->min_amount !== null && $amount < (float) $service->min_amount) {
            return [
                'code' => 'AMOUNT_BELOW_SERVICE_MINIMUM',
                'message' => 'Amount is below the allowed service minimum',
                'status' => 422,
            ];
        }

        if ($service->max_amount !== null && $amount > (float) $service->max_amount) {
            return [
                'code' => 'AMOUNT_ABOVE_SERVICE_MAXIMUM',
                'message' => 'Amount is above the allowed service maximum',
                'status' => 422,
            ];
        }

        if ($setting->daily_limit !== null && ($this->usedToday($serviceCode, $branchId) + $amount) > (float) $setting->daily_limit) {
            return [
                'code' => 'DAILY_LIMIT_EXCEEDED',
                'message' => 'Daily service limit exceeded for this branch',
                'status' => 422,
            ];
        }

        return null;
    }

    private function usedToday(string $serviceCode, string $branchId): float
    {
        return (float) ServiceTransaction::query()
            ->where('branch_id', $branchId)
            ->where('transaction_type', $serviceCode)
            ->where('status', 'SUCCESS')
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');
    }
}
