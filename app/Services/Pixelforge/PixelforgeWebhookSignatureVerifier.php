<?php

namespace App\Services\Pixelforge;

class PixelforgeWebhookSignatureVerifier
{
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    /**
     * Verify a Pixelforge webhook signature header against the raw request payload.
     */
    public function isValid(
        string $rawPayload,
        ?string $signatureHeader,
        ?string $secret = null,
        ?int $currentTime = null,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
    ): bool {
        $signingSecret = $secret ?? (string) config('services.pixelforge.webhook_secret', '');

        if ($signingSecret === '' || $signatureHeader === null || trim($signatureHeader) === '') {
            return false;
        }

        $parts = $this->parseHeader($signatureHeader);
        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;

        if ($timestamp === null || ! ctype_digit($timestamp) || $signature === null || $signature === '') {
            return false;
        }

        $numericTimestamp = (int) $timestamp;
        $now = $currentTime ?? time();

        if ($numericTimestamp <= 0 || abs($now - $numericTimestamp) > $toleranceSeconds) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$numericTimestamp}.{$rawPayload}", $signingSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * Parse key=value pairs from the Pixelforge-Signature header.
     *
     * @return array<string, string>
     */
    protected function parseHeader(string $header): array
    {
        $parsed = [];

        foreach (explode(',', $header) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, '');
            if ($key !== '') {
                $parsed[trim($key)] = trim($value);
            }
        }

        return $parsed;
    }
}
