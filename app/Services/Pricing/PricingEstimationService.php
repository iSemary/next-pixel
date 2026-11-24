<?php

namespace App\Services\Pricing;

use App\Services\Pricing\DTOs\EstimationInput;
use App\Services\Pricing\DTOs\PricingEstimationResult;
use App\Services\Pricing\DTOs\TokenCategoryEstimate;

class PricingEstimationService implements PricingEstimationServiceInterface
{
    public function __construct(
        protected ProviderPricingServiceInterface $providerPricing,
    ) {}

    /**
     * Estimate token usage and generation costs.
     *
     * @param  EstimationInput|array<string, mixed>  $input
     * @param  array<string, mixed>|null  $pricingSnapshot  Optional static rates snapshot.
     */
    public function estimate(EstimationInput|array $input, ?array $pricingSnapshot = null): PricingEstimationResult
    {
        $dto = $input instanceof EstimationInput ? $input : EstimationInput::fromArray($input);

        $snapshot = $pricingSnapshot ?? $this->providerPricing->getPricing();
        $rates = $snapshot['rates'];
        $tokensConfig = $snapshot['tokens'];

        // 1. Input text tokens
        $characters = mb_strlen($dto->prompt, 'UTF-8');
        $charsPerToken = (int) ($tokensConfig['characters_per_text_token'] ?? 4);
        $baseTextTokens = $characters > 0 ? (int) ceil($characters / max(1, $charsPerToken)) : 0;
        $variance = (float) ($tokensConfig['text_token_variance'] ?? 0.15);
        $maxTextTokens = (int) ceil($baseTextTokens * (1.0 + $variance));
        $minTextTokens = (int) floor($baseTextTokens * (1.0 - $variance));

        // 2. Input image tokens
        $imageCount = count($dto->referenceImages);
        $perImageTokens = (int) ($tokensConfig['per_reference_image'] ?? 765);
        $imageTokens = $imageCount * $perImageTokens;

        // 3. Output tokens
        $sizeValue = $dto->size->value;
        $qualityValue = $dto->quality->value;
        $outputTokens = (int) ($tokensConfig['output'][$qualityValue][$sizeValue]);

        // 4. Reasoning tokens
        $minReasoning = (int) ($tokensConfig['reasoning'][$qualityValue]['min'] ?? 0);
        $maxReasoning = (int) ($tokensConfig['reasoning'][$qualityValue]['max'] ?? 0);
        $reasoningTokens = $maxReasoning;

        // 5. Financial calculations per category (Scale: 9 decimals)
        $textRate = (string) $rates['input_text_tokens'];
        $imageRate = (string) $rates['input_image_tokens'];
        $outputRate = (string) $rates['output_tokens'];
        $reasoningRate = (string) $rates['reasoning_tokens'];

        $textCost = $this->calculateCategoryCost($maxTextTokens, $textRate);
        $imageCost = $this->calculateCategoryCost($imageTokens, $imageRate);
        $outputCost = $this->calculateCategoryCost($outputTokens, $outputRate);
        $reasoningCost = $this->calculateCategoryCost($reasoningTokens, $reasoningRate);

        // Total Provider Cost
        $totalProviderCost = bcadd(
            bcadd(bcadd($textCost, $imageCost, 9), $outputCost, 9),
            $reasoningCost,
            9
        );

        // Platform Markup (default 25%)
        $markupRate = $this->getMarkupRate();
        $markupAmount = bcmul($totalProviderCost, $markupRate, 9);
        $totalUserCost = bcadd($totalProviderCost, $markupAmount, 9);

        $totalTokens = $maxTextTokens + $imageTokens + $outputTokens + $reasoningTokens;
        $minTotalTokens = $minTextTokens + $imageTokens + $outputTokens + $minReasoning;

        $categories = [
            'input_text' => new TokenCategoryEstimate(
                category: 'input_text',
                label: 'Input text tokens',
                estimatedTokens: $maxTextTokens,
                unitRatePer1M: $textRate,
                estimatedCostUsd: $textCost,
                details: [
                    'characters' => $characters,
                    'base_tokens' => $baseTextTokens,
                    'min_tokens' => $minTextTokens,
                    'max_tokens' => $maxTextTokens,
                    'variance_percent' => (int) round($variance * 100),
                ],
            ),
            'input_image' => new TokenCategoryEstimate(
                category: 'input_image',
                label: 'Input image tokens',
                estimatedTokens: $imageTokens,
                unitRatePer1M: $imageRate,
                estimatedCostUsd: $imageCost,
                details: [
                    'reference_images_count' => $imageCount,
                    'tokens_per_image' => $perImageTokens,
                ],
            ),
            'output' => new TokenCategoryEstimate(
                category: 'output',
                label: 'Output tokens',
                estimatedTokens: $outputTokens,
                unitRatePer1M: $outputRate,
                estimatedCostUsd: $outputCost,
                details: [
                    'size' => $sizeValue,
                    'quality' => $qualityValue,
                ],
            ),
            'reasoning' => new TokenCategoryEstimate(
                category: 'reasoning',
                label: 'Reasoning tokens',
                estimatedTokens: $reasoningTokens,
                unitRatePer1M: $reasoningRate,
                estimatedCostUsd: $reasoningCost,
                details: [
                    'min_tokens' => $minReasoning,
                    'max_tokens' => $maxReasoning,
                    'quality' => $qualityValue,
                ],
            ),
        ];

        $markupPercentage = rtrim(rtrim(bcmul($markupRate, '100', 4), '0'), '.').'%';

        return new PricingEstimationResult(
            categories: $categories,
            totalTokens: $totalTokens,
            estimatedProviderCost: $totalProviderCost,
            markupRate: $markupRate,
            markupPercentage: $markupPercentage,
            markupAmount: $markupAmount,
            estimatedUserCost: $totalUserCost,
            pricingSnapshot: $snapshot,
            assumptions: [
                'reasoning_tokens_min' => $minReasoning,
                'reasoning_tokens_max' => $maxReasoning,
                'total_tokens_min' => $minTotalTokens,
                'total_tokens_max' => $totalTokens,
                'characters' => $characters,
                'text_token_variance' => $variance,
                'estimation_mode' => 'conservative_maximum',
            ],
            currency: 'USD',
        );
    }

    /**
     * Get the configured platform markup rate formatted as a 4-decimal string.
     */
    public function getMarkupRate(): string
    {
        $raw = (string) config('services.pixelforge.markup_rate', '0.2500');

        return bcadd($raw, '0', 4);
    }

    /**
     * Calculate cost for a token category in USD using exact 9-decimal precision.
     * Formula: (tokens * ratePer1M) / 1,000,000
     */
    protected function calculateCategoryCost(int $tokens, string $ratePer1M): string
    {
        if ($tokens <= 0 || bccomp($ratePer1M, '0', 9) <= 0) {
            return '0.000000000';
        }

        $numerator = bcmul((string) $tokens, $ratePer1M, 9);

        return bcdiv($numerator, '1000000', 9);
    }
}
