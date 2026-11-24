<?php

namespace App\Services\Generations;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\Generation;
use App\Models\User;
use App\Services\Pixelforge\PixelforgeClientInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerationQueryService implements GenerationQueryServiceInterface
{
    public function __construct(
        protected PixelforgeClientInterface $pixelforgeClient,
        protected GenerationLifecycleServiceInterface $lifecycleService,
    ) {}

    /**
     * Get the user's most recent generations for the Image Studio workspace.
     *
     * @return Collection<int, Generation>
     */
    public function getRecentForStudio(User $user, int $limit = 12): Collection
    {
        return Generation::forUser($user)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Fetch specific user-owned generations by ID and reconcile stale upstream jobs.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Generation>
     */
    public function getByIdsForPolling(User $user, array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $generations = Generation::forUser($user)
            ->whereIn('id', $ids)
            ->latest('id')
            ->get();

        return $generations->map(function (Generation $generation): Generation {
            if ($this->shouldReconcile($generation)) {
                return $this->reconcileFromUpstream($generation);
            }

            return $generation;
        });
    }

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
    public function getPaginated(User $user, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Generation::forUser($user)->latest('id');

        $this->applyFilters($query, $filters);

        return $query->paginate(max(1, min(50, $perPage)))->withQueryString();
    }

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
    public function getSummaryStats(User $user): array
    {
        $rows = Generation::forUser($user)
            ->select(['status', 'estimated_user_cost', 'actual_user_cost'])
            ->get();

        $succeeded = 0;
        $failed = 0;
        $inFlight = 0;
        $estimatedTotal = '0.000000000';
        $actualTotal = '0.000000000';

        foreach ($rows as $row) {
            match ($row->status) {
                GenerationStatus::Succeeded => $succeeded++,
                GenerationStatus::Failed => $failed++,
                GenerationStatus::Queued, GenerationStatus::Processing => $inFlight++,
            };

            if ($row->estimated_user_cost !== null) {
                $estimatedTotal = bcadd($estimatedTotal, (string) $row->estimated_user_cost, 9);
            }

            if ($row->actual_user_cost !== null) {
                $actualTotal = bcadd($actualTotal, (string) $row->actual_user_cost, 9);
            }
        }

        return [
            'total_count' => $rows->count(),
            'succeeded_count' => $succeeded,
            'failed_count' => $failed,
            'in_flight_count' => $inFlight,
            'total_estimated_cost' => $estimatedTotal,
            'total_actual_cost' => $actualTotal,
        ];
    }

    /**
     * Reconcile a non-terminal generation directly against Pixelforge if webhook delivery was delayed.
     */
    public function reconcileFromUpstream(Generation $generation, bool $force = false): Generation
    {
        if ($generation->isCompleted() || empty($generation->provider_generation_id)) {
            return $generation;
        }

        $throttleKey = "pixelforge:poll_sync:{$generation->id}";
        if (! $force && ! Cache::add($throttleKey, true, 15)) {
            return $generation;
        }

        try {
            $remote = $this->pixelforgeClient->getGeneration($generation->provider_generation_id);

            return DB::transaction(function () use ($generation, $remote): Generation {
                $locked = Generation::where('id', $generation->id)
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    return $generation;
                }

                $this->lifecycleService->applyUpstreamSnapshot($locked, $remote);

                return $locked->fresh() ?? $locked;
            });
        } catch (Throwable $e) {
            Log::warning("Upstream poll reconcile failed for Generation #{$generation->id}: {$e->getMessage()}");

            return $generation;
        }
    }

    /**
     * Determine whether an active generation is old enough to warrant a throttled upstream check.
     */
    protected function shouldReconcile(Generation $generation): bool
    {
        if ($generation->isCompleted() || empty($generation->provider_generation_id)) {
            return false;
        }

        $referenceTime = $generation->submitted_at ?? $generation->created_at;

        return $referenceTime->lte(now()->subSeconds(20));
    }

    /**
     * Apply validated filter constraints cleanly across SQLite and PostgreSQL.
     *
     * @param  Builder<Generation>  $query
     * @param  array{
     *     status?: string|null,
     *     quality?: string|null,
     *     size?: string|null,
     *     search?: string|null
     * }  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        $status = $filters['status'] ?? null;
        if (is_string($status) && $status !== '' && $status !== 'all') {
            $enumStatus = GenerationStatus::tryFrom($status);
            if ($enumStatus !== null) {
                $query->where('status', $enumStatus);
            }
        }

        $quality = $filters['quality'] ?? null;
        if (is_string($quality) && $quality !== '' && $quality !== 'all') {
            $enumQuality = ImageQuality::tryFrom($quality);
            if ($enumQuality !== null) {
                $query->where('quality', $enumQuality);
            }
        }

        $size = $filters['size'] ?? null;
        if (is_string($size) && $size !== '' && $size !== 'all') {
            $enumSize = ImageSize::tryFrom($size);
            if ($enumSize !== null) {
                $query->where('size', $enumSize);
            }
        }

        $search = $filters['search'] ?? null;
        if (is_string($search) && trim($search) !== '') {
            $term = trim($search);
            $query->whereLike('prompt', "%{$term}%", caseSensitive: false);
        }
    }
}
