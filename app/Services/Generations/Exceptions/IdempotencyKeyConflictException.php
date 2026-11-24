<?php

namespace App\Services\Generations\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class IdempotencyKeyConflictException extends Exception
{
    public function __construct(
        string $message = 'The provided idempotency key has already been used with different parameters.',
        int $code = 409,
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
                'message' => $this->getMessage(),
                'error_code' => 'idempotency_key_reused',
            ], 409);
        }

        return redirect()->back()->withErrors([
            'idempotency_key' => $this->getMessage(),
        ]);
    }
}
