<?php

use App\Http\Controllers\Api\V1\Accounts\AccountController;
use App\Http\Controllers\Api\V1\Admin\BranchServiceSettingController;
use App\Http\Controllers\Api\V1\Admin\OperationalLogController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Device\DeviceController;
use App\Http\Controllers\Api\V1\Enrollment\EnrollmentController;
use App\Http\Controllers\Api\V1\Payments\PaymentController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Receipts\ReceiptController;
use App\Http\Controllers\Api\V1\Remittances\RemittanceController;
use App\Http\Controllers\Api\V1\Services\ServiceCatalogController;
use App\Http\Controllers\Api\V1\Transactions\TransactionController;
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

    Route::post(
        '/auth/refresh',
        [AuthController::class, 'refresh']
    );

    Route::middleware('jwt.auth')->group(function () {
        Route::get(
            '/services',
            [ServiceCatalogController::class, 'index']
        );

        Route::get(
            '/profile/me',
            [ProfileController::class, 'me']
        );

        Route::post(
            '/profile/change-password',
            [ProfileController::class, 'changePassword']
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
            '/payments/mobile-topup',
            [PaymentController::class, 'mobileTopUp']
        );

        Route::post(
            '/payments/bill-payment',
            [PaymentController::class, 'billPayment']
        );

        Route::post(
            '/remittances/inquiry',
            [RemittanceController::class, 'inquiry']
        );

        Route::get(
            '/receipts/{reference}',
            [ReceiptController::class, 'show']
        );

        Route::post(
            '/receipts/{reference}/print',
            [ReceiptController::class, 'print']
        );

        Route::get(
            '/transactions',
            [TransactionController::class, 'index']
        );

        Route::get(
            '/transactions/{request_id}',
            [TransactionController::class, 'show']
        );

        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get(
                '/devices',
                [DeviceController::class, 'index']
            );

            Route::post(
                '/devices/{device_id}/enable',
                [DeviceController::class, 'enable']
            );

            Route::post(
                '/devices/{device_id}/disable',
                [DeviceController::class, 'disable']
            );

            Route::get(
                '/audit-logs',
                [OperationalLogController::class, 'auditLogs']
            );

            Route::get(
                '/integration-logs',
                [OperationalLogController::class, 'integrationLogs']
            );

            Route::get(
                '/error-logs',
                [OperationalLogController::class, 'errorLogs']
            );

            Route::get(
                '/branches/{branch_code}/services',
                [BranchServiceSettingController::class, 'index']
            );

            Route::put(
                '/branches/{branch_code}/services/{service_code}',
                [BranchServiceSettingController::class, 'update']
            );
        });
    });
});
