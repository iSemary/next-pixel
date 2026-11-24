<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Credits\TopUpCreditsRequest;
use App\Models\User;
use App\Services\Credits\CreditServiceInterface;
use Illuminate\Http\JsonResponse;

class CreditsController extends Controller
{
    public function __construct(
        protected CreditServiceInterface $creditService,
    ) {}

    /**
     * Add simulated USD credits to the authenticated API user's balance.
     */
    public function topUp(TopUpCreditsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $amount = number_format((float) $request->validated('amount'), 2, '.', '');

        $transaction = $this->creditService->topUp(
            $user,
            $amount,
            "Simulated API credit purchase (+\${$amount})",
        );

        return response()->json([
            'credit_balance' => $transaction->balance_after,
            'added_amount' => $transaction->amount,
            'message' => "Added \${$amount} credits to your account.",
        ]);
    }
}
