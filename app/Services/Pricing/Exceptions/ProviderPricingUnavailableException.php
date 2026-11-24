<?php

namespace App\Services\Pricing\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class ProviderPricingUnavailableException extends Exception
{
    public function __construct(
        string $message = 'Pixelforge provider pricing is currently unavailable.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pricing estimation service is temporarily unavailable. Please try again shortly.',
                'error' => $this->getMessage(),
            ], 503);
        }

        return redirect()->back()->withErrors([
            'prompt' => 'Pricing estimation is temporarily unavailable. Please try again shortly.',
        ]);
    }
}
