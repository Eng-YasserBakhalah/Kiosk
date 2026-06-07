<?php

use App\Http\Controllers\Api\V1\Accounts\AccountController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Device\DeviceController;
use App\Http\Controllers\Api\V1\Enrollment\EnrollmentController;
use App\Http\Controllers\Api\V1\Services\ServiceCatalogController;
use App\Http\Controllers\Api\V1\Transfers\TransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return auth('api')->user();
})->middleware('jwt.auth');

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

    Route::post(
        '/devices/register',
        [DeviceController::class, 'register']
    );

    Route::get(
        '/devices/{device_id}/status',
        [DeviceController::class, 'status']
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

    Route::post(
        '/auth/logout',
        [AuthController::class, 'logout']
    );

    Route::middleware('jwt.auth')->group(function () {
        Route::get(
            '/services',
            [ServiceCatalogController::class, 'index']
        );

        Route::get(
            '/accounts',
            [AccountController::class, 'index']
        );

        Route::get(
            '/accounts/{account_id}/balance',
            [AccountController::class, 'balance']
        );

        Route::get(
            '/accounts/{account_id}/statement',
            [AccountController::class, 'statement']
        );

        Route::post(
            '/transfers/internal',
            [TransferController::class, 'internal']
        );

        Route::post(
            '/admin/devices/{device_id}/enable',
            [DeviceController::class, 'enable']
        );

        Route::post(
            '/admin/devices/{device_id}/disable',
            [DeviceController::class, 'disable']
        );
    });
});
