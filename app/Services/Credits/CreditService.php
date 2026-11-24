<?php

namespace App\Services\Credits;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Services\Credits\Exceptions\InsufficientCreditsException;
use Illuminate\Support\Facades\DB;

class CreditService implements CreditServiceInterface
{
    public const SCALE = 9;

    /**
     * Add simulated USD credits to a user's balance inside an atomic transaction.
     */
    public function topUp(User|int $user, string $amountUsd, ?string $description = null): CreditTransaction
    {
        $userId = $user instanceof User ? $user->id : $user;
        $normalized = bcadd($amountUsd, '0', self::SCALE);

        return DB::transaction(function () use ($userId, $normalized, $description): CreditTransaction {
            /** @var User $lockedUser */
            $lockedUser = User::where('id', $userId)->lockForUpdate()->firstOrFail();

            $newBalance = bcadd((string) $lockedUser->credit_balance, $normalized, self::SCALE);
            $lockedUser->credit_balance = $newBalance;
            $lockedUser->save();

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'generation_id' => null,
                'type' => CreditTransactionType::TopUp,
                'amount' => $normalized,
                'balance_after' => $newBalance,
                'description' => $description ?? 'Simulated credit top-up',
            ]);
        });
    }

    /**
     * Reserve estimated credits on an already-locked user row inside an active transaction.
     *
     * @throws InsufficientCreditsException
     */
    public function reserveForGeneration(User $lockedUser, Generation $generation, string $amountUsd): CreditTransaction
    {
        $existing = CreditTransaction::where('generation_id', $generation->id)
            ->where('type', CreditTransactionType::Reserve)
            ->first();

        if ($existing) {
            return $existing;
        }

        $reserveAmount = bcadd($amountUsd, '0', self::SCALE);
        $currentBalance = (string) $lockedUser->credit_balance;

        if (bccomp($currentBalance, $reserveAmount, self::SCALE) < 0) {
            throw new InsufficientCreditsException($reserveAmount, $currentBalance);
        }

        $newBalance = bcsub($currentBalance, $reserveAmount, self::SCALE);
        $lockedUser->credit_balance = $newBalance;
        $lockedUser->save();

        return CreditTransaction::create([
            'user_id' => $lockedUser->id,
            'generation_id' => $generation->id,
            'type' => CreditTransactionType::Reserve,
            'amount' => bcmul($reserveAmount, '-1', self::SCALE),
            'balance_after' => $newBalance,
            'description' => "Estimated reservation for generation #{$generation->id}",
        ]);
    }

    /**
     * Reconcile reserved credits against final settled user cost (refunding unused or failed funds).
     */
    public function settleReservedGeneration(Generation $generation, string $actualUserCost): ?CreditTransaction
    {
        if ($generation->user_id === null) {
            return null;
        }

        $reserveTx = CreditTransaction::where('generation_id', $generation->id)
            ->where('type', CreditTransactionType::Reserve)
            ->first();

        // No reservation exists (e.g. legacy seeded record without wallet hold)
        if (! $reserveTx) {
            return null;
        }

        $existingSettlementTx = CreditTransaction::where('generation_id', $generation->id)
            ->whereIn('type', [CreditTransactionType::Refund, CreditTransactionType::Adjustment])
            ->first();

        if ($existingSettlementTx) {
            return $existingSettlementTx;
        }

        $reservedPositive = bcmul((string) $reserveTx->amount, '-1', self::SCALE);
        $actualNormalized = bcadd($actualUserCost, '0', self::SCALE);
        $delta = bcsub($reservedPositive, $actualNormalized, self::SCALE);

        if (bccomp($delta, '0', self::SCALE) === 0) {
            return null;
        }

        return DB::transaction(function () use ($generation, $delta): CreditTransaction {
            /** @var User $lockedUser */
            $lockedUser = User::where('id', $generation->user_id)->lockForUpdate()->firstOrFail();

            $newBalance = bcadd((string) $lockedUser->credit_balance, $delta, self::SCALE);
            $lockedUser->credit_balance = $newBalance;
            $lockedUser->save();

            $isRefund = bccomp($delta, '0', self::SCALE) > 0;

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'generation_id' => $generation->id,
                'type' => $isRefund ? CreditTransactionType::Refund : CreditTransactionType::Adjustment,
                'amount' => $delta,
                'balance_after' => $newBalance,
                'description' => $generation->isFailed()
                    ? "Full refund for failed generation #{$generation->id}"
                    : ($isRefund
                        ? "Unused estimate refund for generation #{$generation->id}"
                        : "Usage settlement adjustment for generation #{$generation->id}"),
            ]);
        });
    }
}
