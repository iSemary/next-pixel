<?php

namespace Tests\Unit\Services\Pricing;

use App\Services\Pixelforge\PixelforgeClient;
use App\Services\Pricing\Exceptions\InvalidProviderPricingException;
use App\Services\Pricing\Exceptions\ProviderPricingUnavailableException;
use App\Services\Pricing\ProviderPricingService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderPricingServiceTest extends TestCase
{
    protected ProviderPricingService $service;

    protected array $validPricingPayload = [
        'object' => 'pricing',
        'currency' => 'usd',
        'unit' => 'per_1m_tokens',
        'rates' => [
            'input_text_tokens' => '5.00',
            'input_image_tokens' => '10.00',
            'output_tokens' => '40.00',
            'reasoning_tokens' => '12.50',
        ],
        'tokens' => [
            'characters_per_text_token' => 4,
            'text_token_variance' => 0.15,
            'per_reference_image' => 765,
            'output' => [
                'low' => ['1024x1024' => 280, '1536x1024' => 420, '1024x1536' => 420],
                'medium' => ['1024x1024' => 1100, '1536x1024' => 1650, '1024x1536' => 1650],
                'high' => ['1024x1024' => 4300, '1536x1024' => 6450, '1024x1536' => 6450],
            ],
            'reasoning' => [
                'low' => ['min' => 0, 'max' => 500],
                'medium' => ['min' => 200, 'max' => 2000],
                'high' => ['min' => 1000, 'max' => 8000],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $client = new PixelforgeClient(baseUrl: 'https://pixelforge.promptedit.dev/v1');
        $this->service = new ProviderPricingService($client);
    }

    public function test_get_pricing_retrieves_and_caches_provider_rates(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response($this->validPricingPayload, 200),
        ]);

        $pricing1 = $this->service->getPricing();
        $this->assertSame('5.00', $pricing1['rates']['input_text_tokens']);

        // Second call should hit the cache without firing another HTTP request
        $pricing2 = $this->service->getPricing();
        $this->assertSame('5.00', $pricing2['rates']['input_text_tokens']);

        Http::assertSentCount(1);
    }

    public function test_get_pricing_with_fresh_flag_bypasses_cache(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response($this->validPricingPayload, 200),
        ]);

        $this->service->getPricing();
        $this->service->getPricing(fresh: true);

        Http::assertSentCount(2);
    }

    public function test_clear_cache_removes_cached_pricing(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response($this->validPricingPayload, 200),
        ]);

        $this->service->getPricing();
        $this->service->clearCache();
        $this->service->getPricing();

        Http::assertSentCount(2);
    }

    public function test_provider_connection_failure_throws_pricing_unavailable_exception(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $this->expectException(ProviderPricingUnavailableException::class);
        $this->expectExceptionMessage('Failed to retrieve pricing from Pixelforge provider');

        $this->service->getPricing();
    }

    public function test_missing_rates_throws_invalid_provider_pricing_exception(): void
    {
        $invalidPayload = $this->validPricingPayload;
        unset($invalidPayload['rates']);

        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response($invalidPayload, 200),
        ]);

        $this->expectException(InvalidProviderPricingException::class);
        $this->expectExceptionMessage('Missing rates object');

        $this->service->getPricing();
    }

    public function test_negative_or_zero_rates_throw_invalid_provider_pricing_exception(): void
    {
        $invalidPayload = $this->validPricingPayload;
        $invalidPayload['rates']['output_tokens'] = '0.00';

        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response($invalidPayload, 200),
        ]);

        $this->expectException(InvalidProviderPricingException::class);
        $this->expectExceptionMessage('Provider rate [output_tokens] must be greater than zero.');

        $this->service->getPricing();
    }

    public function test_missing_output_tokens_table_throws_invalid_provider_pricing_exception(): void
    {
        $invalidPayload = $this->validPricingPayload;
        unset($invalidPayload['tokens']['output']);

        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response($invalidPayload, 200),
        ]);

        $this->expectException(InvalidProviderPricingException::class);
        $this->expectExceptionMessage('Missing tokens.output configuration table.');

        $this->service->getPricing();
    }
}
