<?php

namespace Tests\Unit\Services\Pricing;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Services\Pricing\Exceptions\InvalidEstimationInputException;
use App\Services\Pricing\PricingEstimationService;
use App\Services\Pricing\PricingEstimationServiceInterface;
use App\Services\Pricing\ProviderPricingServiceInterface;
use Tests\TestCase;

class PricingEstimationServiceTest extends TestCase
{
    protected PricingEstimationService $service;

    protected array $standardPricingSnapshot = [
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

        $providerMock = $this->createMock(ProviderPricingServiceInterface::class);
        $providerMock->method('getPricing')->willReturn($this->standardPricingSnapshot);

        $this->service = new PricingEstimationService($providerMock);
    }

    public function test_official_pixelforge_worked_example_calculation(): void
    {
        // Exact input from official Pixelforge docs:
        // 400-char prompt, 2 reference images, High Quality, 1536x1024 image
        $prompt = str_repeat('A', 400);

        $result = $this->service->estimate([
            'prompt' => $prompt,
            'size' => '1536x1024',
            'quality' => 'high',
            'reference_images' => [
                'https://example.com/style1.png',
                'https://example.com/style2.png',
            ],
        ], $this->standardPricingSnapshot);

        // 1. Input text: 400/4 = 100 base, +15% variance = 115 tokens @ $5.00/1M = $0.000575000
        $this->assertSame(115, $result->categories['input_text']->estimatedTokens);
        $this->assertSame('0.000575000', $result->categories['input_text']->estimatedCostUsd);

        // 2. Input image: 2 * 765 = 1,530 tokens @ $10.00/1M = $0.015300000
        $this->assertSame(1530, $result->categories['input_image']->estimatedTokens);
        $this->assertSame('0.015300000', $result->categories['input_image']->estimatedCostUsd);

        // 3. Output: high quality, 1536x1024 = 6,450 tokens @ $40.00/1M = $0.258000000
        $this->assertSame(6450, $result->categories['output']->estimatedTokens);
        $this->assertSame('0.258000000', $result->categories['output']->estimatedCostUsd);

        // 4. Reasoning: high quality max = 8,000 tokens @ $12.50/1M = $0.100000000
        $this->assertSame(8000, $result->categories['reasoning']->estimatedTokens);
        $this->assertSame('0.100000000', $result->categories['reasoning']->estimatedCostUsd);

        // 5. Total tokens: 115 + 1530 + 6450 + 8000 = 16,095
        $this->assertSame(16095, $result->totalTokens);

        // 6. Total Provider Cost: $0.373875000 exactly
        $this->assertSame('0.373875000', $result->estimatedProviderCost);

        // 7. 25% Platform Markup: $0.373875000 * 0.25 = $0.093468750
        $this->assertSame('0.2500', $result->markupRate);
        $this->assertSame('25%', $result->markupPercentage);
        $this->assertSame('0.093468750', $result->markupAmount);

        // 8. Total User Cost: $0.373875000 + $0.093468750 = $0.467343750 exactly
        $this->assertSame('0.467343750', $result->estimatedUserCost);
    }

    public function test_estimation_across_all_supported_sizes(): void
    {
        $sizesAndExpectedOutputTokens = [
            '1024x1024' => 1100, // medium
            '1536x1024' => 1650,
            '1024x1536' => 1650,
        ];

        foreach ($sizesAndExpectedOutputTokens as $size => $expectedTokens) {
            $result = $this->service->estimate([
                'prompt' => 'Test prompt',
                'size' => $size,
                'quality' => 'medium',
            ], $this->standardPricingSnapshot);

            $this->assertSame($expectedTokens, $result->categories['output']->estimatedTokens);
        }
    }

    public function test_estimation_across_all_supported_qualities(): void
    {
        $qualities = [
            'low' => ['output' => 280, 'reasoning' => 500],
            'medium' => ['output' => 1100, 'reasoning' => 2000],
            'high' => ['output' => 4300, 'reasoning' => 8000],
        ];

        foreach ($qualities as $quality => $expected) {
            $result = $this->service->estimate([
                'prompt' => 'Test prompt',
                'size' => '1024x1024',
                'quality' => $quality,
            ], $this->standardPricingSnapshot);

            $this->assertSame($expected['output'], $result->categories['output']->estimatedTokens);
            $this->assertSame($expected['reasoning'], $result->categories['reasoning']->estimatedTokens);
        }
    }

    public function test_estimation_without_reference_images(): void
    {
        $result = $this->service->estimate([
            'prompt' => 'A photo without reference images',
            'reference_images' => [],
        ], $this->standardPricingSnapshot);

        $this->assertSame(0, $result->categories['input_image']->estimatedTokens);
        $this->assertSame('0.000000000', $result->categories['input_image']->estimatedCostUsd);
    }

    public function test_estimation_with_empty_and_short_prompts(): void
    {
        $emptyResult = $this->service->estimate([
            'prompt' => '',
        ], $this->standardPricingSnapshot);

        $this->assertSame(0, $emptyResult->categories['input_text']->estimatedTokens);
        $this->assertSame('0.000000000', $emptyResult->categories['input_text']->estimatedCostUsd);

        // Short prompt: 10 chars -> ceil(10/4) = 3 base -> ceil(3 * 1.15) = 4 tokens
        $shortResult = $this->service->estimate([
            'prompt' => '1234567890',
        ], $this->standardPricingSnapshot);

        $this->assertSame(4, $shortResult->categories['input_text']->estimatedTokens);
    }

    public function test_estimation_with_maximum_prompt_length_and_multibyte_unicode(): void
    {
        // 4000 single-byte chars
        $maxPrompt = str_repeat('A', 4000);
        $result = $this->service->estimate([
            'prompt' => $maxPrompt,
        ], $this->standardPricingSnapshot);

        // 4000 / 4 = 1000 base -> ceil(1000 * 1.15) = 1150 tokens
        $this->assertSame(1150, $result->categories['input_text']->estimatedTokens);

        // Multibyte Unicode prompt (e.g. 4 Unicode characters = 1 base token -> ceil(1*1.15) = 2 tokens)
        $unicodePrompt = '東京塔🗼'; // 4 characters
        $unicodeResult = $this->service->estimate([
            'prompt' => $unicodePrompt,
        ], $this->standardPricingSnapshot);

        $this->assertSame(2, $unicodeResult->categories['input_text']->estimatedTokens);
    }

    public function test_prompt_exceeding_4000_chars_throws_exception(): void
    {
        $tooLongPrompt = str_repeat('A', 4001);

        $this->expectException(InvalidEstimationInputException::class);
        $this->expectExceptionMessage('Prompt must not exceed 4,000 characters');

        $this->service->estimate(['prompt' => $tooLongPrompt]);
    }

    public function test_more_than_four_reference_images_throws_exception(): void
    {
        $this->expectException(InvalidEstimationInputException::class);
        $this->expectExceptionMessage('At most 4 reference images are supported');

        $this->service->estimate([
            'prompt' => 'Test',
            'reference_images' => [
                'https://example.com/1.png',
                'https://example.com/2.png',
                'https://example.com/3.png',
                'https://example.com/4.png',
                'https://example.com/5.png',
            ],
        ]);
    }

    public function test_invalid_size_and_quality_throw_exception(): void
    {
        $this->expectException(InvalidEstimationInputException::class);
        $this->expectExceptionMessage('Invalid image size');

        $this->service->estimate([
            'prompt' => 'Test',
            'size' => '9999x9999',
        ]);
    }

    public function test_custom_configured_markup_rate(): void
    {
        config(['services.pixelforge.markup_rate' => '0.3000']);

        $result = $this->service->estimate([
            'prompt' => str_repeat('A', 400),
            'size' => '1536x1024',
            'quality' => 'high',
            'reference_images' => ['https://example.com/1.png', 'https://example.com/2.png'],
        ], $this->standardPricingSnapshot);

        // Provider: 0.373875000
        // Markup 30%: 0.373875000 * 0.30 = 0.112162500
        // User cost: 0.373875000 + 0.112162500 = 0.486037500
        $this->assertSame('0.3000', $result->markupRate);
        $this->assertSame('30%', $result->markupPercentage);
        $this->assertSame('0.112162500', $result->markupAmount);
        $this->assertSame('0.486037500', $result->estimatedUserCost);
    }

    public function test_to_database_attributes_maps_to_generations_schema(): void
    {
        $result = $this->service->estimate([
            'prompt' => 'Test mapped attributes',
            'size' => ImageSize::Landscape,
            'quality' => ImageQuality::Medium,
        ], $this->standardPricingSnapshot);

        $attributes = $result->toDatabaseAttributes();

        $this->assertArrayHasKey('estimated_input_text_tokens', $attributes);
        $this->assertArrayHasKey('estimated_input_image_tokens', $attributes);
        $this->assertArrayHasKey('estimated_output_tokens', $attributes);
        $this->assertArrayHasKey('estimated_reasoning_tokens_min', $attributes);
        $this->assertArrayHasKey('estimated_reasoning_tokens_max', $attributes);
        $this->assertArrayHasKey('estimated_total_tokens_min', $attributes);
        $this->assertArrayHasKey('estimated_total_tokens_max', $attributes);
        $this->assertArrayHasKey('estimated_provider_cost', $attributes);
        $this->assertArrayHasKey('estimated_user_cost', $attributes);
        $this->assertArrayHasKey('applied_markup_rate', $attributes);
        $this->assertArrayHasKey('pricing_rate_snapshot', $attributes);

        $this->assertSame('0.2500', $attributes['applied_markup_rate']);
        $this->assertSame($this->standardPricingSnapshot, $attributes['pricing_rate_snapshot']);
    }

    public function test_to_public_array_formats_frontend_presentation(): void
    {
        $result = $this->service->estimate([
            'prompt' => 'A lighthouse on a cliff',
        ], $this->standardPricingSnapshot);

        $public = $result->toPublicArray();

        $this->assertSame('USD', $public['currency']);
        $this->assertArrayHasKey('estimated_user_cost', $public);
        $this->assertArrayHasKey('total_tokens', $public);
        $this->assertArrayHasKey('markup_percentage', $public);
        $this->assertArrayHasKey('breakdown', $public);
        $this->assertCount(4, $public['breakdown']);
    }

    public function test_calculation_is_deterministic_and_reproducible(): void
    {
        $input = [
            'prompt' => 'Deterministic test prompt with exact values',
            'size' => '1024x1536',
            'quality' => 'high',
            'reference_images' => ['https://example.com/style.png'],
        ];

        $firstResult = $this->service->estimate($input, $this->standardPricingSnapshot);

        for ($i = 0; $i < 50; $i++) {
            $subsequentResult = $this->service->estimate($input, $this->standardPricingSnapshot);

            $this->assertSame($firstResult->estimatedProviderCost, $subsequentResult->estimatedProviderCost);
            $this->assertSame($firstResult->markupAmount, $subsequentResult->markupAmount);
            $this->assertSame($firstResult->estimatedUserCost, $subsequentResult->estimatedUserCost);
            $this->assertSame($firstResult->totalTokens, $subsequentResult->totalTokens);
        }
    }

    public function test_service_resolves_from_container(): void
    {
        $resolved = app(PricingEstimationServiceInterface::class);

        $this->assertInstanceOf(PricingEstimationService::class, $resolved);
        $this->assertSame('0.2500', $resolved->getMarkupRate());
    }
}
