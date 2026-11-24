<?php

namespace App\Services\Pixelforge;

use App\Services\Pixelforge\Exceptions\PixelforgeException;

interface PixelforgeClientInterface
{
    /**
     * Retrieve the current token rates and tokenization rules.
     *
     * @return array<string, mixed>
     *
     * @throws PixelforgeException
     */
    public function getPricing(): array;

    /**
     * List generations with cursor pagination.
     *
     * @param  array{
     *     limit?: int,
     *     status?: string,
     *     cursor?: string
     * }  $params
     * @return array<string, mixed>
     *
     * @throws PixelforgeException
     */
    public function listGenerations(array $params = []): array;

    /**
     * Get the configured base URL.
     */
    public function getBaseUrl(): string;

    /**
     * Check whether an API key is configured.
     */
    public function hasApiKey(): bool;
}
