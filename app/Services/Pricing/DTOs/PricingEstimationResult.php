<?php

namespace App\Services\Pricing\DTOs;

readonly class PricingEstimationResult
{
    /**
     * @param  array<string, TokenCategoryEstimate>  $categories
     * @param  array<string, mixed>  $pricingSnapshot
     * @param  array<string, mixed>  $assumptions
     */
    public function __construct(
        public array $categories,
        public int $totalTokens,
        public string $estimatedProviderCost,
        public string $markupRate,
        public string $markupPercentage,
        public string $markupAmount,
        public string $estimatedUserCost,
        public array $pricingSnapshot,
        public array $assumptions = [],
        public string $currency = 'USD',
    ) {}

    /**
     * Map this result directly to attributes for the generations database table.
     *
     * @return array<string, mixed>
     */
    public function toDatabaseAttributes(): array
    {
        return [
            'estimated_input_text_tokens' => $this->categories['input_text']->estimatedTokens ?? null,
            'estimated_input_image_tokens' => $this->categories['input_image']->estimatedTokens ?? null,
            'estimated_output_tokens' => $this->categories['output']->estimatedTokens ?? null,
            'estimated_reasoning_tokens_min' => $this->assumptions['reasoning_tokens_min'] ?? null,
            'estimated_reasoning_tokens_max' => $this->assumptions['reasoning_tokens_max'] ?? null,
            'estimated_total_tokens_min' => $this->assumptions['total_tokens_min'] ?? null,
            'estimated_total_tokens_max' => $this->totalTokens,
            'estimated_provider_cost' => $this->estimatedProviderCost,
            'estimated_user_cost' => $this->estimatedUserCost,
            'applied_markup_rate' => $this->markupRate,
            'pricing_rate_snapshot' => $this->pricingSnapshot,
        ];
    }

    /**
     * Return user-facing presentation data without leaking raw provider internals.
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'currency' => $this->currency,
            'estimated_user_cost' => $this->estimatedUserCost,
            'total_tokens' => $this->totalTokens,
            'markup_percentage' => $this->markupPercentage,
            'breakdown' => array_map(
                fn (TokenCategoryEstimate $cat) => [
                    'label' => $cat->label,
                    'tokens' => $cat->estimatedTokens,
                    'estimated_cost' => $cat->estimatedCostUsd,
                ],
                $this->categories
            ),
        ];
    }

    /**
     * Return full detailed array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'total_tokens' => $this->totalTokens,
            'estimated_provider_cost' => $this->estimatedProviderCost,
            'markup_rate' => $this->markupRate,
            'markup_percentage' => $this->markupPercentage,
            'markup_amount' => $this->markupAmount,
            'estimated_user_cost' => $this->estimatedUserCost,
            'categories' => array_map(fn (TokenCategoryEstimate $cat) => $cat->toArray(), $this->categories),
            'pricing_snapshot' => $this->pricingSnapshot,
            'assumptions' => $this->assumptions,
        ];
    }
}
