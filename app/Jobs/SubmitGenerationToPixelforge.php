<?php

namespace App\Jobs;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Services\Generations\GenerationSettlementServiceInterface;
use App\Services\Pixelforge\Exceptions\PixelforgeApiException;
use App\Services\Pixelforge\Exceptions\PixelforgeConnectionException;
use App\Services\Pixelforge\Exceptions\PixelforgeTimeoutException;
use App\Services\Pixelforge\PixelforgeClientInterface;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubmitGenerationToPixelforge implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Allow unlimited queue releases until retryUntil() expires so concurrency backoffs don't prematurely fail.
     */
    public int $tries = 0;

    /**
     * Maximum unhandled exceptions allowed before failing.
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $generationId,
    ) {}

    /**
     * Determine the time at which the job should timeout from retrying.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    /**
     * Get the unique ID for the job to prevent duplicate execution across workers.
     */
    public function uniqueId(): string
    {
        return (string) $this->generationId;
    }

    /**
     * Execute the job.
     */
    public function handle(PixelforgeClientInterface $client): void
    {
        $lock = Cache::lock("pixelforge:submit:{$this->generationId}", 30);

        if (! $lock->get()) {
            $this->release(3);

            return;
        }

        try {
            $generation = Generation::find($this->generationId);

            if (! $generation) {
                Log::warning("SubmitGenerationToPixelforge: Generation #{$this->generationId} not found.");

                return;
            }

            // If already submitted to provider or completed (e.g. by webhook or prior attempt), do nothing
            if ($generation->provider_generation_id !== null || $generation->isCompleted()) {
                return;
            }

            $payload = $this->buildPayload($generation);

            $response = $client->createGeneration($payload, $generation->idempotency_key);

            $this->markAsSubmitted($generation, $response);
        } catch (PixelforgeApiException $e) {
            if (isset($generation)) {
                $this->handleApiException($e, $generation);
            }
        } catch (PixelforgeConnectionException|PixelforgeTimeoutException $e) {
            Log::warning("Pixelforge network issue for Generation #{$this->generationId}: {$e->getMessage()}. Releasing with backoff.");
            $this->release(10);
        } finally {
            $lock->release();
        }
    }

    /**
     * Build the upstream Pixelforge submission payload.
     *
     * @return array{
     *     prompt: string,
     *     size: string,
     *     quality: string,
     *     reference_images?: array<int, string>,
     *     metadata: array<string, string>
     * }
     */
    protected function buildPayload(Generation $generation): array
    {
        $metadata = array_map(
            static fn (mixed $value): string => (string) $value,
            $generation->metadata ?? []
        );
        $metadata['nextpixel_generation_id'] = (string) $generation->id;

        $payload = [
            'prompt' => $generation->prompt,
            'size' => $generation->size->value,
            'quality' => $generation->quality->value,
            'metadata' => $metadata,
        ];

        if (! empty($generation->reference_images)) {
            $payload['reference_images'] = array_values($generation->reference_images);
        }

        return $payload;
    }

    /**
     * Persist upstream provider identifiers without reverting any terminal webhook update.
     *
     * @param  array<string, mixed>  $response
     */
    protected function markAsSubmitted(Generation $generation, array $response): void
    {
        $fresh = $generation->fresh();
        if (! $fresh) {
            return;
        }

        $updateData = [
            'provider_generation_id' => $response['id'],
            'provider_status' => $response['status'] ?? 'queued',
            'submitted_at' => now(),
        ];

        if (! $fresh->isCompleted()) {
            $updateData['status'] = GenerationStatus::Processing;
        }

        $fresh->update($updateData);

        Log::info("Generation #{$generation->id} successfully submitted to Pixelforge (Provider ID: {$response['id']}).");
    }

    /**
     * Handle Pixelforge API exceptions with appropriate retry or failure semantics.
     */
    protected function handleApiException(PixelforgeApiException $e, Generation $generation): void
    {
        // 1. Terminal failures MUST be checked before status-based rate limits (daily_limit_exceeded is HTTP 429)
        if (
            $e->isDailyLimit()
            || $e->isContentPolicyViolation()
            || $e->isValidationFailed()
            || $e->isAuthenticationError()
            || $e->isIdempotencyReused()
        ) {
            Log::error("Pixelforge terminal error for Generation #{$generation->id}: code=[{$e->getErrorCode()}], message=[{$e->getMessage()}]. Marking generation as failed.");

            $this->markAsFailed($generation, $e->getErrorCode(), $e->getMessage());

            return;
        }

        // 2. Retriable rate limits (429 concurrency/rate) & Capacity / Overload (503)
        if ($e->isRateLimited() || $e->isOverloaded()) {
            $retryAfter = $e->getRetryAfter() ?? 5;
            $delay = max(1, $retryAfter) + random_int(1, 2);

            Log::warning("Pixelforge rate limit or overload encountered for Generation #{$generation->id}: code=[{$e->getErrorCode()}], retryAfter={$retryAfter}s. Releasing back to queue with delay of {$delay}s.");

            $this->release($delay);

            return;
        }

        // 3. Other API errors: release with backoff while within retryUntil window
        $retryAfter = $e->getRetryAfter() ?? 10;
        Log::warning("Pixelforge unhandled API exception ({$e->getStatusCode()}) for Generation #{$generation->id}: {$e->getMessage()}. Releasing in {$retryAfter}s.");
        $this->release($retryAfter);
    }

    /**
     * Mark the generation as terminally failed if not already completed.
     */
    protected function markAsFailed(Generation $generation, string $errorCode, string $errorMessage): void
    {
        DB::transaction(function () use ($generation, $errorCode, $errorMessage): void {
            $fresh = Generation::where('id', $generation->id)->lockForUpdate()->first();

            if ($fresh && ! $fresh->isCompleted()) {
                $fresh->fill([
                    'status' => GenerationStatus::Failed,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'completed_at' => now(),
                ]);

                app(GenerationSettlementServiceInterface::class)->settle($fresh);
                $fresh->save();
            }
        });
    }

    /**
     * Handle a job failure after exhausting the retry window.
     */
    public function failed(?Throwable $exception): void
    {
        $generation = Generation::find($this->generationId);

        if ($generation) {
            $this->markAsFailed(
                $generation,
                'submission_failed',
                $exception ? $exception->getMessage() : 'Generation submission failed after maximum retry attempts.',
            );
        }
    }
}
