<?php

namespace App\Http\Controllers\Api\V1\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\Device\ChangeDeviceStatusRequest;
use App\Http\Requests\Device\HeartbeatRequest;
use App\Http\Requests\Device\RegisterDeviceRequest;
use App\Services\DeviceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService
    ) {}

    public function heartbeat(
        HeartbeatRequest $request
    ): JsonResponse {
        $result = $this->deviceService
            ->heartbeat(
                $request->validated()
            );

        return response()->json(
            collect($result)
                ->except('status_code'),
            $result['status_code']
        );
    }

    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        $result = $this->deviceService->register($request->validated());

        if (! $result['success']) {
            return ApiResponse::error(
                $result['error_code'],
                $result['message'],
                $result['status_code']
            );
        }

        return ApiResponse::success(
            ['device' => $result['device']],
            $result['message'],
            $result['status_code']
        );
    }

    public function status(string $deviceId): JsonResponse
    {
        $result = $this->deviceService->status($deviceId);

        if (! $result['success']) {
            return ApiResponse::error(
                $result['error_code'],
                $result['message'],
                $result['status_code']
            );
        }

        return ApiResponse::success(
            ['device' => $result['device']],
            $result['message']
        );
    }

    public function enable(ChangeDeviceStatusRequest $request, string $deviceId): JsonResponse
    {
        $result = $this->deviceService->enable($deviceId);

        if (! $result['success']) {
            return ApiResponse::error(
                $result['error_code'],
                $result['message'],
                $result['status_code']
            );
        }

        return ApiResponse::success(
            ['device' => $result['device']],
            $result['message']
        );
    }

    public function disable(ChangeDeviceStatusRequest $request, string $deviceId): JsonResponse
    {
        $result = $this->deviceService->disable($deviceId);

        if (! $result['success']) {
            return ApiResponse::error(
                $result['error_code'],
                $result['message'],
                $result['status_code']
            );
        }

        return ApiResponse::success(
            ['device' => $result['device']],
            $result['message']
        );
    }
}
