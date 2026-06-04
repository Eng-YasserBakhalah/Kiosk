<?php

namespace App\Http\Controllers\Api\V1\Enrollment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\SetPasswordRequest;
use App\Http\Requests\Enrollment\StartEnrollmentRequest;
use App\Http\Requests\Enrollment\VerifyOtpRequest;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService
    ) {}

    public function start(
        StartEnrollmentRequest $request
    ): JsonResponse {
        $result = $this->enrollmentService
            ->start(
                $request->validated()
            );

        return response()->json(
            collect($result)
                ->except('status_code'),
            $result['status_code']
        );
    }

    public function verifyOtp(
        VerifyOtpRequest $request
    ): JsonResponse {
        $result = $this->enrollmentService
            ->verifyOtp(
                $request->validated()
            );

        return response()->json(
            collect($result)
                ->except('status_code'),
            $result['status_code']
        );
    }

    public function setPassword(
        SetPasswordRequest $request
    ): JsonResponse {
        $result = $this->enrollmentService
            ->setPassword(
                $request->validated()
            );

        return response()->json(
            collect($result)
                ->except('status_code'),
            $result['status_code']
        );
    }
}
