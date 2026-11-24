<?php

namespace App\Http\Middleware;

use App\Services\Pixelforge\PixelforgeWebhookSignatureVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPixelforgeWebhookSignature
{
    public function __construct(
        protected PixelforgeWebhookSignatureVerifier $verifier,
    ) {}

    /**
     * Handle an incoming Pixelforge webhook request and reject invalid or expired signatures.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawPayload = $request->getContent();
        $signatureHeader = $request->header('Pixelforge-Signature');

        if (! $this->verifier->isValid($rawPayload, is_string($signatureHeader) ? $signatureHeader : null)) {
            return response()->json([
                'message' => 'Invalid or expired Pixelforge webhook signature.',
            ], 400);
        }

        return $next($request);
    }
}
