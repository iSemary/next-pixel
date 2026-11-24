<?php

namespace App\Services\Credits\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class InsufficientCreditsException extends Exception
{
    public function __construct(
        public readonly string $requiredAmount,
        public readonly string $availableBalance,
        string $message = '',
        int $code = 402,
        ?Throwable $previous = null,
    ) {
        $resolvedMessage = $message !== ''
            ? $message
            : sprintf(
                'Insufficient credit balance ($%s required, $%s available). Please buy credits to continue.',
                number_format((float) $requiredAmount, 4),
                number_format((float) $availableBalance, 4),
            );

        parent::__construct($resolvedMessage, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error_code' => 'insufficient_credits',
                'required_amount' => $this->requiredAmount,
                'available_balance' => $this->availableBalance,
            ], 402);
        }

        return redirect()->back()->withErrors([
            'prompt' => $this->getMessage(),
        ]);
    }
}
