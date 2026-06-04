<?php

namespace App\Http\Controllers\Api\V1\Device;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\DeviceService;
use App\Http\Requests\Device\HeartbeatRequest;

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
}