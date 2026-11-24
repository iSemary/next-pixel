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
use App\Services\Generations\GenerationLifecycleService;
use App\Services\Generations\GenerationLifecycleServiceInterface;
use App\Services\Generations\GenerationQueryService;
use App\Services\Generations\GenerationQueryServiceInterface;
use App\Services\Generations\GenerationSettlementService;
use App\Services\Generations\GenerationSettlementServiceInterface;
use App\Services\Generations\GenerationSubmissionService;
use App\Services\Generations\GenerationSubmissionServiceInterface;
use App\Services\Pixelforge\PixelforgeClient;
use App\Services\Pixelforge\PixelforgeClientInterface;
use App\Services\Pricing\PricingEstimationService;
use App\Services\Pricing\PricingEstimationServiceInterface;
use App\Services\Pricing\ProviderPricingService;
use App\Services\Pricing\ProviderPricingServiceInterface;
use App\Services\Reports\AdminReportService;
use App\Services\Reports\AdminReportServiceInterface;
use App\Services\Settings\ProfileService;
use App\Services\Settings\ProfileServiceInterface;
use App\Services\Webhooks\WebhookIngestionService;
use App\Services\Webhooks\WebhookIngestionServiceInterface;
use App\Services\Webhooks\WebhookProcessorService;
use App\Services\Webhooks\WebhookProcessorServiceInterface;
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
        GenerationSubmissionServiceInterface::class => GenerationSubmissionService::class,
        GenerationSettlementServiceInterface::class => GenerationSettlementService::class,
        GenerationLifecycleServiceInterface::class => GenerationLifecycleService::class,
        GenerationQueryServiceInterface::class => GenerationQueryService::class,
        WebhookIngestionServiceInterface::class => WebhookIngestionService::class,
        WebhookProcessorServiceInterface::class => WebhookProcessorService::class,
        AdminReportServiceInterface::class => AdminReportService::class,
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
