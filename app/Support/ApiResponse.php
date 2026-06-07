<?php

namespace App\Support;

use App\Services\ErrorLogService;
use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        array $data = [],
        string $message = 'Operation completed successfully',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'request_id' => request()->attributes->get('request_id'),
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    public static function error(
        string $code,
        string $message,
        int $status = 400,
        mixed $details = null,
        ?string $serviceCode = null,
        ?string $source = null,
    ): JsonResponse {
        app(ErrorLogService::class)->log(
            $code,
            $message,
            $serviceCode,
            $source,
            $details
        );

        return response()->json([
            'success' => false,
            'request_id' => request()->attributes->get('request_id'),
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }
}
