<?php

namespace App\Services\Pricing;

use App\Services\Pixelforge\Exceptions\PixelforgeException;
use App\Services\Pixelforge\PixelforgeClientInterface;
use App\Services\Pricing\Exceptions\InvalidProviderPricingException;
use App\Services\Pricing\Exceptions\ProviderPricingUnavailableException;
use Illuminate\Support\Facades\Cache;

class ProviderPricingService implements ProviderPricingServiceInterface
{
    public const CACHE_KEY = 'pixelforge.pricing';

    public function __construct(
        protected PixelforgeClientInterface $client,
    ) {}

    /**
     * Retrieve validated provider pricing rates and token rules.
     *
     * @param  bool  $fresh  Whether to bypass cache and fetch directly from the provider.
     * @return array<string, mixed>
     *
     * @throws ProviderPricingUnavailableException
     * @throws InvalidProviderPricingException
     */
    public function getPricing(bool $fresh = false): array
    {
        $ttl = (int) config('services.pixelforge.pricing_cache_ttl', 3600);

        if ($fresh) {
            $this->clearCache();
        }

        try {
            return Cache::remember(self::CACHE_KEY, $ttl, function () {
                $pricing = $this->client->getPricing();
                $this->validatePricing($pricing);

                return $pricing;
            });
        } catch (PixelforgeException $e) {
            throw new ProviderPricingUnavailableException(
                message: "Failed to retrieve pricing from Pixelforge provider: {$e->getMessage()}",
                code: $e->getCode(),
                previous: $e,
            );
        }
    }

    /**
     * Clear cached provider pricing.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Validate the completeness and correctness of the pricing response.
     *
     * @param  array<string, mixed>  $pricing
     *
     * @throws InvalidProviderPricingException
     */
    protected function validatePricing(array $pricing): void
    {
        if (! isset($pricing['rates']) || ! is_array($pricing['rates'])) {
            throw new InvalidProviderPricingException('Missing rates object in provider pricing response.');
        }

        $requiredRates = [
            'input_text_tokens',
            'input_image_tokens',
            'output_tokens',
            'reasoning_tokens',
        ];

        foreach ($requiredRates as $rateKey) {
            if (! isset($pricing['rates'][$rateKey]) || ! is_numeric($pricing['rates'][$rateKey])) {
                throw new InvalidProviderPricingException("Missing or non-numeric rate [{$rateKey}] in provider pricing response.");
            }

            if (bccomp((string) $pricing['rates'][$rateKey], '0', 9) <= 0) {
                throw new InvalidProviderPricingException("Provider rate [{$rateKey}] must be greater than zero.");
            }
        }

        if (! isset($pricing['tokens']) || ! is_array($pricing['tokens'])) {
            throw new InvalidProviderPricingException('Missing tokens configuration object in provider pricing response.');
        }

        $tokens = $pricing['tokens'];

        if (! isset($tokens['output']) || ! is_array($tokens['output'])) {
            throw new InvalidProviderPricingException('Missing tokens.output configuration table.');
        }

        foreach (['low', 'medium', 'high'] as $quality) {
            if (! isset($tokens['output'][$quality]) || ! is_array($tokens['output'][$quality])) {
                throw new InvalidProviderPricingException("Missing tokens.output.{$quality} table in provider pricing response.");
            }

            foreach (['1024x1024', '1536x1024', '1024x1536'] as $size) {
                if (! isset($tokens['output'][$quality][$size]) || ! is_numeric($tokens['output'][$quality][$size])) {
                    throw new InvalidProviderPricingException("Missing or invalid tokens.output.{$quality}.{$size} token count.");
                }
            }
        }

        if (! isset($tokens['reasoning']) || ! is_array($tokens['reasoning'])) {
            throw new InvalidProviderPricingException('Missing tokens.reasoning configuration table.');
        }

        foreach (['low', 'medium', 'high'] as $quality) {
            if (! isset($tokens['reasoning'][$quality]['min']) || ! isset($tokens['reasoning'][$quality]['max'])) {
                throw new InvalidProviderPricingException("Missing tokens.reasoning.{$quality} min/max limits.");
            }
        }
    }
}
