<?php

namespace App\Services\Generations;

use App\Models\Generation;

interface GenerationSettlementServiceInterface
{
    /**
     * Calculate final provider and user costs and settle a completed generation idempotently.
     *
     * @param  array<string, mixed>|null  $providerRates  Optional explicit rates snapshot from upstream payload.
     */
    public function settle(Generation $generation, ?array $providerRates = null): Generation;
}
