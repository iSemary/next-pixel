<?php

namespace App\Services\Pricing;

use App\Services\Pricing\DTOs\EstimationInput;
use App\Services\Pricing\DTOs\PricingEstimationResult;
use App\Services\Pricing\Exceptions\InvalidEstimationInputException;
use App\Services\Pricing\Exceptions\InvalidProviderPricingException;
use App\Services\Pricing\Exceptions\ProviderPricingUnavailableException;

interface PricingEstimationServiceInterface
{
    /**
     * Estimate token usage and generation costs.
     *
     * @param  EstimationInput|array<string, mixed>  $input
     * @param  array<string, mixed>|null  $pricingSnapshot  Optional static rates snapshot.
     *
     * @throws InvalidEstimationInputException
     * @throws ProviderPricingUnavailableException
     * @throws InvalidProviderPricingException
     */
    public function estimate(EstimationInput|array $input, ?array $pricingSnapshot = null): PricingEstimationResult;

    /**
     * Get the configured platform markup rate (e.g. '0.2500').
     */
    public function getMarkupRate(): string;
}
