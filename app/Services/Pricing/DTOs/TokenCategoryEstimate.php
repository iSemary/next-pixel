<?php

namespace App\Services\Pricing\DTOs;

readonly class TokenCategoryEstimate
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public string $category,
        public string $label,
        public int $estimatedTokens,
        public string $unitRatePer1M,
        public string $estimatedCostUsd,
        public array $details = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'label' => $this->label,
            'estimated_tokens' => $this->estimatedTokens,
            'unit_rate_per_1m' => $this->unitRatePer1M,
            'estimated_cost_usd' => $this->estimatedCostUsd,
            'details' => $this->details,
        ];
    }
}
