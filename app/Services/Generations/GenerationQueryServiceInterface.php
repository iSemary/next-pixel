<?php

namespace App\Services\Generations;

use App\Models\Generation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface GenerationQueryServiceInterface
{
    /**
     * Get the user's most recent generations for the Image Studio workspace.
     *
     * @return Collection<int, Generation>
     */
    public function getRecentForStudio(User $user, int $limit = 12): Collection;

    /**
     * Fetch specific user-owned generations by ID (used for live polling) and reconcile stale upstream jobs.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Generation>
     */
    public function getByIdsForPolling(User $user, array $ids): Collection;

    /**
     * Get paginated generation history for the library or API list endpoint.
     *
     * @param  array{
     *     status?: string|null,
     *     quality?: string|null,
     *     size?: string|null,
     *     search?: string|null
     * }  $filters
     * @return LengthAwarePaginator<int, Generation>
     */
    public function getPaginated(User $user, array $filters = [], int $perPage = 12): LengthAwarePaginator;

    /**
     * Compute aggregate library counts and estimated vs actual spend totals.
     *
     * @return array{
     *     total_count: int,
     *     succeeded_count: int,
     *     failed_count: int,
     *     in_flight_count: int,
     *     total_estimated_cost: string,
     *     total_actual_cost: string
     * }
     */
    public function getSummaryStats(User $user): array;

    /**
     * Reconcile a non-terminal generation directly against Pixelforge if webhook delivery was delayed.
     */
    public function reconcileFromUpstream(Generation $generation, bool $force = false): Generation;
}
