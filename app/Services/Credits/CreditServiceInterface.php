<?php

namespace App\Services\Credits;

use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Services\Credits\Exceptions\InsufficientCreditsException;

interface CreditServiceInterface
{
    /**
     * Add simulated USD credits to a user's balance inside an atomic transaction.
     */
    public function topUp(User|int $user, string $amountUsd, ?string $description = null): CreditTransaction;

    /**
     * Lock balance, verify affordability, and reserve estimated credits for a generation.
     *
     * @throws InsufficientCreditsException
     */
    public function reserveForGeneration(User $lockedUser, Generation $generation, string $amountUsd): CreditTransaction;

    /**
     * Reconcile reserved credits against final settled user cost (refunding unused or failed funds).
     */
    public function settleReservedGeneration(Generation $generation, string $actualUserCost): ?CreditTransaction;
}
