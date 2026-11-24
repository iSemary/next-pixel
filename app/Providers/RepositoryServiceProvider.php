<?php

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\Auth\AuthServiceInterface;
use App\Services\Auth\PasswordResetService;
use App\Services\Auth\PasswordResetServiceInterface;
use App\Services\Credits\CreditService;
use App\Services\Credits\CreditServiceInterface;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\DashboardServiceInterface;
use App\Services\Pixelforge\PixelforgeClient;
use App\Services\Pixelforge\PixelforgeClientInterface;
use App\Services\Pricing\PricingEstimationService;
use App\Services\Pricing\PricingEstimationServiceInterface;
use App\Services\Pricing\ProviderPricingService;
use App\Services\Pricing\ProviderPricingServiceInterface;
use App\Services\Settings\ProfileService;
use App\Services\Settings\ProfileServiceInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<string, string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        AuthServiceInterface::class => AuthService::class,
        PasswordResetServiceInterface::class => PasswordResetService::class,
        CreditServiceInterface::class => CreditService::class,
        DashboardServiceInterface::class => DashboardService::class,
        ProfileServiceInterface::class => ProfileService::class,
        PixelforgeClientInterface::class => PixelforgeClient::class,
        ProviderPricingServiceInterface::class => ProviderPricingService::class,
        PricingEstimationServiceInterface::class => PricingEstimationService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
