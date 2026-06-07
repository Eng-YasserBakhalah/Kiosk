<?php

namespace App\Services;

use App\Models\BranchServiceSetting;
use App\Models\DigitalService;

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
}
