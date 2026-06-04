<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Http\Requests\Auth\LoginRequest;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(
        LoginRequest $request
    ): JsonResponse {

        $result = $this->authService
            ->login(
                $request->validated()
            );

        return response()->json(
            collect($result)
                ->except('status_code'),
            $result['status_code']
        );
    }
    public function logout(): JsonResponse
{
    $result = $this->authService
        ->logout();

    return response()->json(
        collect($result)
            ->except('status_code'),
        $result['status_code']
    );
}
}