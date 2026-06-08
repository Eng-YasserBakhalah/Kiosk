<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (! $user || $user->role !== 'ADMIN') {
            return ApiResponse::error(
                'FORBIDDEN',
                'Admin privileges are required',
                403
            );
        }

        return $next($request);
    }
}
