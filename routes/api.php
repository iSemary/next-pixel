<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CreditsController as ApiCreditsController;
use App\Http\Controllers\Api\V1\GenerationController as ApiGenerationController;
use App\Http\Controllers\Api\V1\GenerationEstimateController as ApiGenerationEstimateController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\ReferenceImageController;
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
        Route::get('generations', [ApiGenerationController::class, 'index'])
            ->middleware('throttle:120,1')
            ->name('generations.index');
        Route::post('generations', [ApiGenerationController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('generations.store');
        Route::get('generations/{generation}', [ApiGenerationController::class, 'show'])
            ->middleware('throttle:120,1')
            ->name('generations.show');
        Route::post('generations/estimate', [ApiGenerationEstimateController::class, 'estimate'])
            ->middleware('throttle:60,1')
            ->name('generations.estimate');
        Route::post('generations/reference-images', [ReferenceImageController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('generations.reference-images.store');
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
    Route::get('generations', [ApiGenerationController::class, 'index'])
        ->middleware('throttle:120,1');
    Route::post('generations', [ApiGenerationController::class, 'store'])
        ->middleware('throttle:30,1');
    Route::get('generations/{generation}', [ApiGenerationController::class, 'show'])
        ->middleware('throttle:120,1');
    Route::post('generations/estimate', [ApiGenerationEstimateController::class, 'estimate'])
        ->middleware('throttle:60,1');
});
