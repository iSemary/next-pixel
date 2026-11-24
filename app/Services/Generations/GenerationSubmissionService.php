<?php

namespace App\Services\Generations;

use App\Enums\GenerationStatus;
use App\Jobs\SubmitGenerationToPixelforge;
use App\Models\Generation;
use App\Models\User;
use App\Services\Credits\CreditServiceInterface;
use App\Services\Credits\Exceptions\InsufficientCreditsException;
use App\Services\Generations\DTOs\GenerationSubmissionData;
use App\Services\Generations\Exceptions\IdempotencyKeyConflictException;
use App\Services\Pricing\PricingEstimationServiceInterface;
use App\Support\DatabaseExceptionInspector;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerationSubmissionService implements GenerationSubmissionServiceInterface
{
    public function __construct(
        protected PricingEstimationServiceInterface $pricingService,
        protected CreditServiceInterface $creditService,
    ) {}

    /**
     * Submit one or more generations for background processing.
     *
     * @return array{
     *     generations: Collection<int, Generation>,
     *     is_idempotent_replay: bool,
     *     credit_balance?: string|null
     * }
     *
     * @throws IdempotencyKeyConflictException|InsufficientCreditsException
     */
    public function submit(User|int|null $user, GenerationSubmissionData $data): array
    {
        $userId = $user instanceof User ? $user->id : $user;
        $numGenerations = $data->numGenerations;
        $baseKey = $data->idempotencyKey ?: (string) Str::uuid();
        $expectedKeys = $this->buildIdempotencyKeys($baseKey, $numGenerations);

        // 1. Check for existing submission(s) under this idempotency key set
        $existing = $this->findExistingSubmission($expectedKeys, $userId, $data);
        if ($existing !== null) {
            Log::info("Idempotent replay detected for base key [{$baseKey}]. Returning {$existing->count()} existing generation(s).");

            return [
                'generations' => $existing,
                'is_idempotent_replay' => true,
                'credit_balance' => $userId !== null ? (string) User::where('id', $userId)->value('credit_balance') : null,
            ];
        }

        // 2. Snapshot current token rates and pricing estimation before DB persistence
        $estimate = $this->pricingService->estimate($data->toEstimationInput());
        $pricingAttributes = $estimate->toDatabaseAttributes();
        $perItemEstimatedCost = (string) $estimate->estimatedUserCost;

        // 3. Persist generations and reserve estimated credits atomically; dispatch jobs ONLY after commit
        $createdIdsToDispatch = [];
        $generations = collect();
        $isReplay = false;
        $remainingBalance = null;

        try {
            $generations = DB::transaction(function () use (
                $userId,
                $expectedKeys,
                $data,
                $pricingAttributes,
                $perItemEstimatedCost,
                &$createdIdsToDispatch,
                &$isReplay,
                &$remainingBalance,
            ) {
                /** @var User|null $lockedUser */
                $lockedUser = $userId !== null
                    ? User::where('id', $userId)->lockForUpdate()->firstOrFail()
                    : null;

                $existingMap = Generation::whereIn('idempotency_key', $expectedKeys)
                    ->get()
                    ->keyBy('idempotency_key');

                $newCount = count($expectedKeys) - $existingMap->count();

                if ($lockedUser !== null && $newCount > 0) {
                    $totalRequired = bcmul($perItemEstimatedCost, (string) $newCount, 9);
                    $available = (string) $lockedUser->credit_balance;

                    if (bccomp($available, $totalRequired, 9) < 0) {
                        throw new InsufficientCreditsException($totalRequired, $available);
                    }
                }

                $batch = collect();

                foreach ($expectedKeys as $key) {
                    $existingRecord = $existingMap->get($key);

                    if ($existingRecord) {
                        $this->assertIdempotencyMatch($existingRecord, $userId, $data);
                        $batch->push($existingRecord);
                        $isReplay = true;

                        continue;
                    }

                    $created = Generation::create(array_merge([
                        'user_id' => $userId,
                        'idempotency_key' => $key,
                        'prompt' => $data->input->prompt,
                        'size' => $data->input->size,
                        'quality' => $data->input->quality,
                        'reference_images' => ! empty($data->input->referenceImages) ? $data->input->referenceImages : null,
                        'metadata' => ! empty($data->metadata) ? $data->metadata : null,
                        'status' => GenerationStatus::Queued,
                    ], $pricingAttributes));

                    if ($lockedUser !== null) {
                        $this->creditService->reserveForGeneration($lockedUser, $created, $perItemEstimatedCost);
                    }

                    $createdIdsToDispatch[] = $created->id;
                    $batch->push($created);
                }

                $remainingBalance = $lockedUser !== null ? (string) $lockedUser->credit_balance : null;

                return $batch;
            });
        } catch (QueryException $e) {
            if (! DatabaseExceptionInspector::isUniqueConstraintViolation($e)) {
                throw $e;
            }

            // Concurrent race won the insert: verify ownership & payload parity before replaying
            $replayed = $this->findExistingSubmission($expectedKeys, $userId, $data);
            if ($replayed !== null) {
                foreach ($replayed as $gen) {
                    if ($gen->provider_generation_id === null && $gen->status === GenerationStatus::Queued) {
                        SubmitGenerationToPixelforge::dispatch($gen->id);
                    }
                }

                return [
                    'generations' => $replayed,
                    'is_idempotent_replay' => true,
                    'credit_balance' => $userId !== null ? (string) User::where('id', $userId)->value('credit_balance') : null,
                ];
            }

            throw new IdempotencyKeyConflictException(previous: $e);
        }

        // Dispatch after DB commit so background workers always observe committed rows
        foreach ($createdIdsToDispatch as $generationId) {
            SubmitGenerationToPixelforge::dispatch($generationId);
        }

        return [
            'generations' => $generations,
            'is_idempotent_replay' => $isReplay,
            'credit_balance' => $remainingBalance,
        ];
    }

    /**
     * Build deterministic idempotency keys for single or batch requests.
     *
     * @return list<string>
     */
    protected function buildIdempotencyKeys(string $baseKey, int $count): array
    {
        if ($count === 1) {
            return [$baseKey];
        }

        return array_map(
            static fn (int $i): string => "{$baseKey}-{$i}",
            range(1, $count),
        );
    }

    /**
     * Locate existing generation records for the given keys and verify ownership and payload match.
     *
     * @param  list<string>  $expectedKeys
     * @return Collection<int, Generation>|null
     *
     * @throws IdempotencyKeyConflictException
     */
    protected function findExistingSubmission(array $expectedKeys, ?int $userId, GenerationSubmissionData $data): ?Collection
    {
        $existing = Generation::whereIn('idempotency_key', $expectedKeys)
            ->orderBy('id')
            ->get();

        if ($existing->isEmpty()) {
            return null;
        }

        if ($existing->count() !== count($expectedKeys)) {
            throw new IdempotencyKeyConflictException('Idempotency key batch size does not match original request.');
        }

        foreach ($existing as $record) {
            $this->assertIdempotencyMatch($record, $userId, $data);
        }

        return $existing;
    }

    /**
     * Ensure an existing idempotent generation belongs to the requesting user and matches parameters.
     *
     * @throws IdempotencyKeyConflictException
     */
    protected function assertIdempotencyMatch(Generation $record, ?int $userId, GenerationSubmissionData $data): void
    {
        if ($record->user_id !== $userId) {
            throw new IdempotencyKeyConflictException('The provided idempotency key is unavailable.');
        }

        $samePrompt = $record->prompt === $data->input->prompt;
        $sameSize = $record->size === $data->input->size;
        $sameQuality = $record->quality === $data->input->quality;
        $sameRefs = array_values($record->reference_images ?? []) === array_values($data->input->referenceImages);

        if (! ($samePrompt && $sameSize && $sameQuality && $sameRefs)) {
            throw new IdempotencyKeyConflictException;
        }
    }
}
