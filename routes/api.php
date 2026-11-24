<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CreditsController as ApiCreditsController;
use App\Http\Controllers\Api\V1\GenerationEstimateController as ApiGenerationEstimateController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1 (v1)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->as('api.v1.')->group(function () {
    // Auth endpoints under /api/v1/auth/*
    Route::prefix('auth')->as('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');

        Route::middleware('auth:api')->group(function () {
            Route::get('user', [AuthController::class, 'user'])->name('user');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    // Direct endpoints under /api/v1/*
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
    Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);

    Route::middleware('auth:api')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('credits/top-up', [ApiCreditsController::class, 'topUp'])
            ->middleware('throttle:30,1')
            ->name('credits.top-up');
        Route::post('generations/estimate', [ApiGenerationEstimateController::class, 'estimate'])
            ->middleware('throttle:60,1')
            ->name('generations.estimate');
    });
});

/*
|--------------------------------------------------------------------------
| Legacy / Fallback Unversioned API Routes (Forwarded to V1)
|--------------------------------------------------------------------------
*/
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);

Route::middleware('auth:api')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('generations/estimate', [ApiGenerationEstimateController::class, 'estimate'])
        ->middleware('throttle:60,1');
});
