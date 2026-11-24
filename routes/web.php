<?php

use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\CreditsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\GenerationEstimateController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\ReferenceImageController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [WebAuthController::class, 'login']);
    Route::get('register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('register', [WebAuthController::class, 'register']);

    Route::get('forgot-password', [PasswordResetController::class, 'showForgotPassword'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetPassword'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [LibraryController::class, 'index'])->name('dashboard');
    Route::get('image-studio', [DashboardController::class, 'index'])->name('image-studio');
    Route::get('admin/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
    Route::redirect('reports', '/admin/reports');
    Route::post('logout', [WebAuthController::class, 'logout'])->name('logout');
    Route::post('credits/top-up', [CreditsController::class, 'topUp'])
        ->middleware('throttle:30,1')
        ->name('credits.top-up');
    Route::get('generations', [GenerationController::class, 'index'])
        ->middleware('throttle:120,1')
        ->name('generations.index');
    Route::post('generations', [GenerationController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('generations.store');
    Route::get('generations/{generation}', [GenerationController::class, 'show'])
        ->middleware('throttle:120,1')
        ->name('generations.show');
    Route::post('generations/estimate', [GenerationEstimateController::class, 'estimate'])
        ->middleware('throttle:60,1')
        ->name('generations.estimate');
    Route::post('generations/reference-images', [ReferenceImageController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('generations.reference-images.store');
});

require __DIR__.'/settings.php';
