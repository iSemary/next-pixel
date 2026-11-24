<?php

namespace App\Services\Generations;

use App\Models\Generation;
use App\Models\User;
use App\Services\Generations\DTOs\GenerationSubmissionData;
use App\Services\Generations\Exceptions\IdempotencyKeyConflictException;
use Illuminate\Support\Collection;

interface GenerationSubmissionServiceInterface
{
    /**
     * Submit one or more generations for background processing.
     *
     * @return array{
     *     generations: Collection<int, Generation>,
     *     is_idempotent_replay: bool
     * }
     *
     * @throws IdempotencyKeyConflictException
     */
    public function submit(User|int|null $user, GenerationSubmissionData $data): array;
}
