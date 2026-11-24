<?php

namespace App\Services\Reports;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use App\Services\Pixelforge\Exceptions\PixelforgeException;
use App\Services\Pixelforge\PixelforgeClientInterface;
use App\Services\Pricing\TokenCostCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AdminReportService implements AdminReportServiceInterface
{
    public const MAX_RANGE_DAYS = 92;

    public const DEFAULT_RANGE_DAYS = 30;

    public function __construct(
        protected PixelforgeClientInterface $pixelforgeClient,
    ) {}

    /**
     * Build the operator financial report across UTC days and users, reconciled against Pixelforge GET /v1/usage.
     *
     * @return array<string, mixed>
     */
    public function getReport(?string $startDate = null, ?string $endDate = null): array
    {
        [$start, $end] = $this->resolveUtcRange($startDate, $endDate);

        $startFormatted = $start->format('Y-m-d');
        $endFormatted = $end->format('Y-m-d');

        $generations = Generation::query()
            ->with('user:id,name,email,credit_balance')
            ->whereIn('status', [GenerationStatus::Succeeded, GenerationStatus::Failed])
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start->startOfDay(), $end->endOfDay()])
            ->get();

        $pixelforgeUsage = $this->fetchPixelforgeUsage($startFormatted, $endFormatted);

        $dailyRows = $this->buildDailyRows($start, $end, $generations, $pixelforgeUsage['by_date']);
        $userRows = $this->buildUserRows($generations);
        $summary = $this->buildSummary($generations, $pixelforgeUsage);

        return [
            'filters' => [
                'start_date' => $startFormatted,
                'end_date' => $endFormatted,
            ],
            'summary' => $summary,
            'daily' => $dailyRows,
            'by_user' => $userRows,
            'synced_at' => CarbonImmutable::now('UTC')->toIso8601String(),
        ];
    }

    /**
     * Normalize and clamp the requested UTC date window to Pixelforge's 92-day limit.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolveUtcRange(?string $startDate, ?string $endDate): array
    {
        $today = CarbonImmutable::now('UTC')->startOfDay();

        $end = $endDate
            ? CarbonImmutable::createFromFormat('Y-m-d', $endDate, 'UTC')->startOfDay()
            : $today;

        $start = $startDate
            ? CarbonImmutable::createFromFormat('Y-m-d', $startDate, 'UTC')->startOfDay()
            : $end->subDays(self::DEFAULT_RANGE_DAYS - 1);

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        if ($start->diffInDays($end) >= self::MAX_RANGE_DAYS) {
            $start = $end->subDays(self::MAX_RANGE_DAYS - 1);
        }

        return [$start, $end];
    }

    /**
     * Fetch usage totals and daily breakdowns from Pixelforge GET /v1/usage safely.
     *
     * @return array{
     *     available: bool,
     *     error: string|null,
     *     by_date: array<string, array<string, mixed>>,
     *     total: array<string, mixed>|null
     * }
     */
    protected function fetchPixelforgeUsage(string $startDate, string $endDate): array
    {
        if (! $this->pixelforgeClient->hasApiKey()) {
            return [
                'available' => false,
                'error' => 'Pixelforge API key is not configured for live reconciliation.',
                'by_date' => [],
                'total' => null,
            ];
        }

        try {
            $payload = $this->pixelforgeClient->getUsage($startDate, $endDate);
        } catch (PixelforgeException $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
                'by_date' => [],
                'total' => null,
            ];
        }

        $byDate = [];
        foreach ((array) ($payload['data'] ?? []) as $entry) {
            if (is_array($entry) && is_string($entry['date'] ?? null)) {
                $byDate[$entry['date']] = $entry;
            }
        }

        return [
            'available' => true,
            'error' => null,
            'by_date' => $byDate,
            'total' => is_array($payload['total'] ?? null) ? $payload['total'] : null,
        ];
    }

    /**
     * Build per-UTC-day rows combining local records with Pixelforge /v1/usage rows.
     *
     * @param  Collection<int, Generation>  $generations
     * @param  array<string, array<string, mixed>>  $pixelforgeByDate
     * @return list<array<string, mixed>>
     */
    protected function buildDailyRows(
        CarbonImmutable $start,
        CarbonImmutable $end,
        Collection $generations,
        array $pixelforgeByDate,
    ): array {
        $localByDate = [];

        foreach ($generations as $generation) {
            if (! $generation->completed_at) {
                continue;
            }

            $dateKey = $generation->completed_at->utc()->format('Y-m-d');
            if (! isset($localByDate[$dateKey])) {
                $localByDate[$dateKey] = [
                    'succeeded_generations' => 0,
                    'failed_generations' => 0,
                    'credit_spend' => '0.000000000',
                    'local_provider_cost' => '0.000000000',
                    'local_total_tokens' => 0,
                ];
            }

            if ($generation->status === GenerationStatus::Failed) {
                $localByDate[$dateKey]['failed_generations']++;

                continue;
            }

            $localByDate[$dateKey]['succeeded_generations']++;
            $localByDate[$dateKey]['credit_spend'] = bcadd(
                $localByDate[$dateKey]['credit_spend'],
                (string) ($generation->actual_user_cost ?? '0'),
                TokenCostCalculator::SCALE,
            );
            $localByDate[$dateKey]['local_provider_cost'] = bcadd(
                $localByDate[$dateKey]['local_provider_cost'],
                (string) ($generation->actual_provider_cost ?? '0'),
                TokenCostCalculator::SCALE,
            );
            $localByDate[$dateKey]['local_total_tokens'] += (int) ($generation->actual_total_tokens ?? 0);
        }

        $rows = [];
        $cursor = $end;

        while ($cursor->greaterThanOrEqualTo($start)) {
            $dateKey = $cursor->format('Y-m-d');
            $local = $localByDate[$dateKey] ?? [
                'succeeded_generations' => 0,
                'failed_generations' => 0,
                'credit_spend' => '0.000000000',
                'local_provider_cost' => '0.000000000',
                'local_total_tokens' => 0,
            ];

            $pf = $pixelforgeByDate[$dateKey] ?? null;
            $pixelforgeCost = is_array($pf) && isset($pf['cost_usd'])
                ? bcadd((string) $pf['cost_usd'], '0', TokenCostCalculator::SCALE)
                : null;
            $pixelforgeGenerations = is_array($pf) && isset($pf['generations'])
                ? (int) $pf['generations']
                : null;
            $pixelforgeTotalTokens = is_array($pf)
                ? (int) ($pf['input_text_tokens'] ?? 0)
                    + (int) ($pf['input_image_tokens'] ?? 0)
                    + (int) ($pf['output_tokens'] ?? 0)
                    + (int) ($pf['reasoning_tokens'] ?? 0)
                : null;

            $providerCost = $pixelforgeCost ?? $local['local_provider_cost'];
            $profit = bcsub($local['credit_spend'], $providerCost, TokenCostCalculator::SCALE);
            $discrepancy = $pixelforgeCost !== null
                ? bcsub($local['local_provider_cost'], $pixelforgeCost, TokenCostCalculator::SCALE)
                : null;
            $matchesPixelforge = $discrepancy !== null
                ? bccomp($discrepancy, '0', TokenCostCalculator::SCALE) === 0
                : null;

            $hasActivity = $local['succeeded_generations'] > 0
                || $local['failed_generations'] > 0
                || ($pixelforgeGenerations ?? 0) > 0
                || bccomp($local['credit_spend'], '0', TokenCostCalculator::SCALE) > 0
                || bccomp($local['local_provider_cost'], '0', TokenCostCalculator::SCALE) > 0
                || ($pixelforgeCost !== null && bccomp($pixelforgeCost, '0', TokenCostCalculator::SCALE) > 0);

            $rows[] = [
                'date' => $dateKey,
                'succeeded_generations' => $local['succeeded_generations'],
                'failed_generations' => $local['failed_generations'],
                'credit_spend' => $local['credit_spend'],
                'local_provider_cost' => $local['local_provider_cost'],
                'pixelforge_cost' => $pixelforgeCost,
                'pixelforge_generations' => $pixelforgeGenerations,
                'provider_cost' => $providerCost,
                'profit' => $profit,
                'discrepancy' => $discrepancy,
                'matches_pixelforge' => $matchesPixelforge,
                'local_total_tokens' => $local['local_total_tokens'],
                'pixelforge_total_tokens' => $pixelforgeTotalTokens,
                'has_activity' => $hasActivity,
            ];

            $cursor = $cursor->subDay();
        }

        return $rows;
    }

    /**
     * Aggregate credit spend, provider costs, and profit per user across the selected window.
     *
     * @param  Collection<int, Generation>  $generations
     * @return list<array<string, mixed>>
     */
    protected function buildUserRows(Collection $generations): array
    {
        $aggregates = [];

        foreach ($generations as $generation) {
            $key = $generation->user_id !== null ? (string) $generation->user_id : 'deleted';

            if (! isset($aggregates[$key])) {
                $aggregates[$key] = [
                    'user_id' => $generation->user_id,
                    'name' => $generation->user?->name ?? 'Deleted User',
                    'email' => $generation->user?->email ?? '—',
                    'credit_balance' => bcadd((string) ($generation->user?->credit_balance ?? '0'), '0', TokenCostCalculator::SCALE),
                    'succeeded_generations' => 0,
                    'failed_generations' => 0,
                    'credit_spend' => '0.000000000',
                    'provider_cost' => '0.000000000',
                    'total_tokens' => 0,
                ];
            }

            if ($generation->status === GenerationStatus::Failed) {
                $aggregates[$key]['failed_generations']++;

                continue;
            }

            $aggregates[$key]['succeeded_generations']++;
            $aggregates[$key]['credit_spend'] = bcadd(
                $aggregates[$key]['credit_spend'],
                (string) ($generation->actual_user_cost ?? '0'),
                TokenCostCalculator::SCALE,
            );
            $aggregates[$key]['provider_cost'] = bcadd(
                $aggregates[$key]['provider_cost'],
                (string) ($generation->actual_provider_cost ?? '0'),
                TokenCostCalculator::SCALE,
            );
            $aggregates[$key]['total_tokens'] += (int) ($generation->actual_total_tokens ?? 0);
        }

        // Include registered users who had zero completed generations in the selected window
        User::query()
            ->select(['id', 'name', 'email', 'credit_balance'])
            ->orderBy('name')
            ->get()
            ->each(function (User $user) use (&$aggregates): void {
                $key = (string) $user->id;
                if (! isset($aggregates[$key])) {
                    $aggregates[$key] = [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'credit_balance' => bcadd((string) $user->credit_balance, '0', TokenCostCalculator::SCALE),
                        'succeeded_generations' => 0,
                        'failed_generations' => 0,
                        'credit_spend' => '0.000000000',
                        'provider_cost' => '0.000000000',
                        'total_tokens' => 0,
                    ];
                }
            });

        $rows = array_map(function (array $row): array {
            $row['profit'] = bcsub($row['credit_spend'], $row['provider_cost'], TokenCostCalculator::SCALE);

            return $row;
        }, array_values($aggregates));

        usort($rows, function (array $a, array $b): int {
            $spendCmp = bccomp($b['credit_spend'], $a['credit_spend'], TokenCostCalculator::SCALE);
            if ($spendCmp !== 0) {
                return $spendCmp;
            }

            $countCmp = $b['succeeded_generations'] <=> $a['succeeded_generations'];
            if ($countCmp !== 0) {
                return $countCmp;
            }

            return strcmp((string) $a['name'], (string) $b['name']);
        });

        return $rows;
    }

    /**
     * Compute overall summary and Pixelforge GET /v1/usage reconciliation totals.
     *
     * @param  Collection<int, Generation>  $generations
     * @param  array{
     *     available: bool,
     *     error: string|null,
     *     by_date: array<string, array<string, mixed>>,
     *     total: array<string, mixed>|null
     * }  $pixelforgeUsage
     * @return array<string, mixed>
     */
    protected function buildSummary(Collection $generations, array $pixelforgeUsage): array
    {
        $creditSpend = '0.000000000';
        $localProviderCost = '0.000000000';
        $succeeded = 0;
        $failed = 0;
        $localInputText = 0;
        $localInputImage = 0;
        $localOutput = 0;
        $localReasoning = 0;

        foreach ($generations as $generation) {
            if ($generation->status === GenerationStatus::Failed) {
                $failed++;

                continue;
            }

            $succeeded++;
            $creditSpend = bcadd(
                $creditSpend,
                (string) ($generation->actual_user_cost ?? '0'),
                TokenCostCalculator::SCALE,
            );
            $localProviderCost = bcadd(
                $localProviderCost,
                (string) ($generation->actual_provider_cost ?? '0'),
                TokenCostCalculator::SCALE,
            );
            $localInputText += (int) ($generation->actual_input_text_tokens ?? 0);
            $localInputImage += (int) ($generation->actual_input_image_tokens ?? 0);
            $localOutput += (int) ($generation->actual_output_tokens ?? 0);
            $localReasoning += (int) ($generation->actual_reasoning_tokens ?? 0);
        }

        $pfTotal = $pixelforgeUsage['total'];
        $pixelforgeProviderCost = is_array($pfTotal) && isset($pfTotal['cost_usd'])
            ? bcadd((string) $pfTotal['cost_usd'], '0', TokenCostCalculator::SCALE)
            : null;
        $pixelforgeGenerations = is_array($pfTotal) && isset($pfTotal['generations'])
            ? (int) $pfTotal['generations']
            : null;

        $providerCost = $pixelforgeProviderCost ?? $localProviderCost;
        $profit = bcsub($creditSpend, $providerCost, TokenCostCalculator::SCALE);

        $discrepancy = $pixelforgeProviderCost !== null
            ? bcsub($localProviderCost, $pixelforgeProviderCost, TokenCostCalculator::SCALE)
            : null;
        $matchesPixelforge = $discrepancy !== null
            ? bccomp($discrepancy, '0', TokenCostCalculator::SCALE) === 0
            : null;

        $marginPercent = bccomp($creditSpend, '0', TokenCostCalculator::SCALE) > 0
            ? bcmul(bcdiv($profit, $creditSpend, 6), '100', 2)
            : '0.00';

        $pixelforgeTokens = is_array($pfTotal) ? [
            'input_text' => (int) ($pfTotal['input_text_tokens'] ?? 0),
            'input_image' => (int) ($pfTotal['input_image_tokens'] ?? 0),
            'output' => (int) ($pfTotal['output_tokens'] ?? 0),
            'reasoning' => (int) ($pfTotal['reasoning_tokens'] ?? 0),
            'total' => (int) ($pfTotal['input_text_tokens'] ?? 0)
                + (int) ($pfTotal['input_image_tokens'] ?? 0)
                + (int) ($pfTotal['output_tokens'] ?? 0)
                + (int) ($pfTotal['reasoning_tokens'] ?? 0),
        ] : null;

        return [
            'credit_spend' => $creditSpend,
            'local_provider_cost' => $localProviderCost,
            'pixelforge_provider_cost' => $pixelforgeProviderCost,
            'provider_cost' => $providerCost,
            'profit' => $profit,
            'margin_percent' => $marginPercent,
            'discrepancy' => $discrepancy,
            'matches_pixelforge' => $matchesPixelforge,
            'succeeded_generations' => $succeeded,
            'failed_generations' => $failed,
            'pixelforge_generations' => $pixelforgeGenerations,
            'local_tokens' => [
                'input_text' => $localInputText,
                'input_image' => $localInputImage,
                'output' => $localOutput,
                'reasoning' => $localReasoning,
                'total' => $localInputText + $localInputImage + $localOutput + $localReasoning,
            ],
            'pixelforge_tokens' => $pixelforgeTokens,
            'pixelforge_available' => $pixelforgeUsage['available'],
            'pixelforge_error' => $pixelforgeUsage['error'],
        ];
    }
}
