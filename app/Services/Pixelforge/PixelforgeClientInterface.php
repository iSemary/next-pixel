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
     * Submit a new image generation request to Pixelforge.
     *
     * @param  array{
     *     prompt: string,
     *     size: string,
     *     quality: string,
     *     reference_images?: array<int, string>,
     *     metadata?: array<string, string>
     * }  $payload
     * @return array<string, mixed>
     *
     * @throws PixelforgeException
     */
    public function createGeneration(array $payload, ?string $idempotencyKey = null): array;

    /**
     * Retrieve a single generation by its upstream provider ID.
     *
     * @return array<string, mixed>
     *
     * @throws PixelforgeException
     */
    public function getGeneration(string $providerGenerationId): array;

    /**
     * Retrieve daily provider usage and billed USD costs from GET /v1/usage.
     *
     * @return array<string, mixed>
     *
     * @throws PixelforgeException
     */
    public function getUsage(?string $startDate = null, ?string $endDate = null): array;

    /**
     * Get the configured base URL.
     */
    public function getBaseUrl(): string;

    /**
     * Check whether an API key is configured.
     */
    public function hasApiKey(): bool;
}
