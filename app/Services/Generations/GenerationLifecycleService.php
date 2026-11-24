<?php

namespace App\Services\Generations;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use Illuminate\Support\Carbon;

class GenerationLifecycleService implements GenerationLifecycleServiceInterface
{
    public function __construct(
        protected GenerationSettlementServiceInterface $settlementService,
    ) {}

    /**
     * Apply an upstream Pixelforge generation object monotonically and settle terminal states.
     *
     * @param  array<string, mixed>  $upstreamObject
     */
    public function applyUpstreamSnapshot(Generation $generation, array $upstreamObject, ?string $expectedStatus = null): bool
    {
        $providerId = is_string($upstreamObject['id'] ?? null) ? $upstreamObject['id'] : null;
        if ($generation->provider_generation_id === null && $providerId !== null && $providerId !== '') {
            $generation->provider_generation_id = $providerId;
        }

        $targetStatus = $expectedStatus ?? (is_string($upstreamObject['status'] ?? null) ? $upstreamObject['status'] : '');

        $applied = match ($targetStatus) {
            'processing', 'generation.processing' => $this->applyProcessing($generation, $upstreamObject),
            'succeeded', 'generation.succeeded' => $this->applySucceeded($generation, $upstreamObject),
            'failed', 'generation.failed' => $this->applyFailed($generation, $upstreamObject),
            default => false,
        };

        if (! $applied) {
            if ($generation->isDirty()) {
                $generation->save();
            }

            return false;
        }

        if ($generation->isCompleted()) {
            $providerRates = is_array($upstreamObject['rates'] ?? null) ? $upstreamObject['rates'] : null;
            $this->settlementService->settle($generation, $providerRates);
        }

        $generation->save();

        return true;
    }

    /**
     * Transition a queued generation to processing without moving backwards from later states.
     *
     * @param  array<string, mixed>  $object
     */
    protected function applyProcessing(Generation $generation, array $object): bool
    {
        if ($generation->isCompleted()) {
            return false;
        }

        if ($generation->status === GenerationStatus::Processing && $generation->started_at !== null) {
            return false;
        }

        $generation->status = GenerationStatus::Processing;
        $generation->provider_status = 'processing';
        $generation->started_at ??= $this->parseTimestamp($object['started_at'] ?? null) ?? now();

        return true;
    }

    /**
     * Transition a non-terminal generation to succeeded and capture usage/image artifacts.
     *
     * @param  array<string, mixed>  $object
     */
    protected function applySucceeded(Generation $generation, array $object): bool
    {
        if ($generation->isCompleted()) {
            return false;
        }

        $usage = is_array($object['usage'] ?? null) ? $object['usage'] : [];

        $generation->status = GenerationStatus::Succeeded;
        $generation->provider_status = 'succeeded';
        $generation->image_url = is_string($object['image_url'] ?? null) ? $object['image_url'] : null;
        $generation->image_url_expires_at = $this->parseTimestamp($object['image_url_expires_at'] ?? null);
        $generation->started_at ??= $this->parseTimestamp($object['started_at'] ?? null) ?? now();
        $generation->completed_at = $this->parseTimestamp($object['completed_at'] ?? null) ?? now();

        if (! empty($usage)) {
            $generation->actual_input_text_tokens = isset($usage['input_text_tokens']) ? (int) $usage['input_text_tokens'] : null;
            $generation->actual_input_image_tokens = isset($usage['input_image_tokens']) ? (int) $usage['input_image_tokens'] : null;
            $generation->actual_output_tokens = isset($usage['output_tokens']) ? (int) $usage['output_tokens'] : null;
            $generation->actual_reasoning_tokens = isset($usage['reasoning_tokens']) ? (int) $usage['reasoning_tokens'] : null;
            $generation->actual_total_tokens = isset($usage['total_tokens']) ? (int) $usage['total_tokens'] : null;
        }

        return true;
    }

    /**
     * Transition a non-terminal generation to failed and record provider error details.
     *
     * @param  array<string, mixed>  $object
     */
    protected function applyFailed(Generation $generation, array $object): bool
    {
        if ($generation->isCompleted()) {
            return false;
        }

        $error = is_array($object['error'] ?? null) ? $object['error'] : [];

        $generation->status = GenerationStatus::Failed;
        $generation->provider_status = 'failed';
        $generation->error_code = is_string($error['code'] ?? null) ? $error['code'] : 'generation_failed';
        $generation->error_message = is_string($error['message'] ?? null)
            ? $error['message']
            : 'Generation failed on upstream provider.';
        $generation->started_at ??= $this->parseTimestamp($object['started_at'] ?? null);
        $generation->completed_at = $this->parseTimestamp($object['completed_at'] ?? null) ?? now();

        return true;
    }

    protected function parseTimestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
