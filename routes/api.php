<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Device\DeviceController;
use App\Http\Controllers\Api\V1\Enrollment\EnrollmentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Device
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/device/heartbeat',
        [DeviceController::class, 'heartbeat']
    );

    /*
    |--------------------------------------------------------------------------
    | Enrollment
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/enrollment/start',
        [EnrollmentController::class, 'start']
    );

    Route::post(
        '/enrollment/verify-otp',
        [EnrollmentController::class, 'verifyOtp']
    );

    Route::post(
        '/enrollment/set-password',
        [EnrollmentController::class, 'setPassword']
    );

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/auth/login',
        [AuthController::class, 'login']
    );

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('jwt.auth')
        ->group(function () {

        Route::post(
            '/auth/logout',
            [AuthController::class, 'logout']
        );

    });
});