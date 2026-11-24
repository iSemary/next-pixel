<?php

namespace App\Services\Pricing;

use App\Services\Pricing\Exceptions\InvalidProviderPricingException;
use App\Services\Pricing\Exceptions\ProviderPricingUnavailableException;

interface ProviderPricingServiceInterface
{
    /**
     * Retrieve validated provider pricing rates and token rules.
     *
     * @param  bool  $fresh  Whether to bypass cache and fetch directly from the provider.
     * @return array<string, mixed>
     *
     * @throws ProviderPricingUnavailableException
     * @throws InvalidProviderPricingException
     */
    public function getPricing(bool $fresh = false): array;

    /**
     * Clear cached provider pricing.
     */
    public function clearCache(): void;
}
