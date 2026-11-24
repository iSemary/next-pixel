<?php

namespace App\Services\Generations;

use App\Models\Generation;
use App\Services\Credits\CreditServiceInterface;
use App\Services\Pricing\ProviderPricingServiceInterface;
use App\Services\Pricing\TokenCostCalculator;

class GenerationSettlementService implements GenerationSettlementServiceInterface
{
    public function __construct(
        protected ProviderPricingServiceInterface $providerPricing,
        protected CreditServiceInterface $creditService,
    ) {}

    /**
     * Calculate final provider and user costs, reconcile reserved credits, and settle idempotently.
     *
     * Failed generations are always free ($0.000000000) and receive a full reservation refund.
     *
     * @param  array<string, mixed>|null  $providerRates  Optional explicit rates snapshot from upstream payload.
     */
    public function settle(Generation $generation, ?array $providerRates = null): Generation
    {
        // Idempotent guard: never recalculate an already-settled generation
        if ($generation->isSettled() || ! $generation->isCompleted()) {
            return $generation;
        }

        // Failed generations are never billed
        if ($generation->isFailed()) {
            $generation->actual_provider_cost = '0.000000000';
            $generation->actual_user_cost = '0.000000000';
            $generation->settled_at = now();

            $this->creditService->settleReservedGeneration($generation, '0.000000000');

            return $generation;
        }

        $rates = $this->resolveRates($generation, $providerRates);

        $textCost = TokenCostCalculator::categoryCost(
            (int) ($generation->actual_input_text_tokens ?? 0),
            (string) ($rates['input_text_tokens'] ?? '0'),
        );
        $imageCost = TokenCostCalculator::categoryCost(
            (int) ($generation->actual_input_image_tokens ?? 0),
            (string) ($rates['input_image_tokens'] ?? '0'),
        );
        $outputCost = TokenCostCalculator::categoryCost(
            (int) ($generation->actual_output_tokens ?? 0),
            (string) ($rates['output_tokens'] ?? '0'),
        );
        $reasoningCost = TokenCostCalculator::categoryCost(
            (int) ($generation->actual_reasoning_tokens ?? 0),
            (string) ($rates['reasoning_tokens'] ?? '0'),
        );

        $actualProviderCost = TokenCostCalculator::sum(
            $textCost,
            $imageCost,
            $outputCost,
            $reasoningCost,
        );

        $markupRate = bcadd((string) ($generation->applied_markup_rate ?: '0.2500'), '0', 4);
        $markedUp = TokenCostCalculator::applyMarkup($actualProviderCost, $markupRate);

        $generation->actual_provider_cost = $actualProviderCost;
        $generation->actual_user_cost = $markedUp['user_cost'];
        $generation->settled_at = now();

        $this->creditService->settleReservedGeneration($generation, $markedUp['user_cost']);

        return $generation;
    }

    /**
     * Resolve exact billing rates prioritizing upstream payload rates, then submission snapshot, then live provider pricing.
     *
     * @param  array<string, mixed>|null  $providerRates
     * @return array<string, mixed>
     */
    protected function resolveRates(Generation $generation, ?array $providerRates): array
    {
        if (! empty($providerRates)) {
            return $providerRates;
        }

        $snapshotRates = $generation->pricing_rate_snapshot['rates'] ?? null;
        if (is_array($snapshotRates) && ! empty($snapshotRates)) {
            return $snapshotRates;
        }

        $pricing = $this->providerPricing->getPricing();

        return is_array($pricing['rates'] ?? null) ? $pricing['rates'] : [];
    }
}
