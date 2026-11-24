<?php

namespace App\Services\Pricing\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class InvalidProviderPricingException extends Exception
{
    public function __construct(
        string $message = 'Invalid or malformed provider pricing structure received.',
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
                'message' => 'Invalid pricing rates received from provider.',
                'error' => $this->getMessage(),
            ], 502);
        }

        return redirect()->back()->withErrors([
            'prompt' => 'Invalid pricing rates received from provider.',
        ]);
    }
}
