<?php

namespace App\Services\Generations;

use App\Models\Generation;

interface GenerationLifecycleServiceInterface
{
    /**
     * Apply an upstream Pixelforge generation object monotonically and settle terminal states.
     *
     * Returns true when a state transition occurred, or false when the update is stale/redundant.
     *
     * @param  array<string, mixed>  $upstreamObject
     */
    public function applyUpstreamSnapshot(Generation $generation, array $upstreamObject, ?string $expectedStatus = null): bool;
}
