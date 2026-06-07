<?php

namespace App\Services;

use App\Models\ErrorLog;

class ErrorLogService
{
    public function log(
        string $code,
        string $message,
        ?string $serviceCode = null,
        ?string $source = null,
        mixed $details = null
    ): void {
        $session = app(SessionContextService::class)->current();

        ErrorLog::create([
            'request_id' => request()->attributes->get('request_id'),
            'user_id' => $session?->user_id,
            'terminal_device_id' => $session?->terminal_device_id,
            'service_code' => $serviceCode,
            'error_type' => $code,
            'error_level' => 'ERROR',
            'error_code' => $code,
            'error_message' => $message,
            'source' => $source,
            'stack_trace' => app()->environment('production') ? null : $this->formatDetails($details),
        ]);
    }

    private function formatDetails(mixed $details): ?string
    {
        if ($details === null) {
            return null;
        }

        if (is_string($details)) {
            return $details;
        }

        return json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
