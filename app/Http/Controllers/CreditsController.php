<?php

namespace App\Http\Controllers;

use App\Http\Requests\Credits\TopUpCreditsRequest;
use App\Models\User;
use App\Services\Credits\CreditServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CreditsController extends Controller
{
    public function __construct(
        protected CreditServiceInterface $creditService,
    ) {}

    /**
     * Add simulated USD credits to the authenticated user's balance.
     */
    public function topUp(TopUpCreditsRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $amount = number_format((float) $request->validated('amount'), 2, '.', '');

        $transaction = $this->creditService->topUp(
            $user,
            $amount,
            "Simulated credit purchase (+\${$amount})",
        );

        if ($request->wantsJson()) {
            return response()->json([
                'credit_balance' => $transaction->balance_after,
                'added_amount' => $transaction->amount,
                'message' => "Added \${$amount} credits to your account.",
            ]);
        }

        return redirect()->back()->with('success', "Added \${$amount} credits to your account.");
    }
}
