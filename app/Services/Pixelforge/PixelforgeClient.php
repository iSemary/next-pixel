<?php

namespace App\Services\Pixelforge;

use App\Services\Pixelforge\Exceptions\PixelforgeApiException;
use App\Services\Pixelforge\Exceptions\PixelforgeConnectionException;
use App\Services\Pixelforge\Exceptions\PixelforgeException;
use App\Services\Pixelforge\Exceptions\PixelforgeTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PixelforgeClient implements PixelforgeClientInterface
{
    protected string $baseUrl;

    protected string $apiKey;

    protected int $timeout;

    public function __construct(
        ?string $baseUrl = null,
        ?string $apiKey = null,
        ?int $timeout = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.pixelforge.base_url', 'https://pixelforge.promptedit.dev/v1'), '/');
        $this->apiKey = $apiKey ?? (string) config('services.pixelforge.api_key', '');
        $this->timeout = $timeout ?? (int) config('services.pixelforge.timeout', 15);
    }

    /**
     * Retrieve the current token rates and tokenization rules.
     *
     * @return array<string, mixed>
     *
     * @throws PixelforgeException
     */
    public function getPricing(): array
    {
        $response = $this->send('GET', 'pricing');

        return $response->json();
    }

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
    public function listGenerations(array $params = []): array
    {
        $queryParams = array_filter([
            'limit' => $params['limit'] ?? null,
            'status' => $params['status'] ?? null,
            'cursor' => $params['cursor'] ?? null,
        ], fn ($val) => $val !== null && $val !== '');

        $response = $this->send('GET', 'generations', $queryParams);

        return $response->json();
    }

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
    public function createGeneration(array $payload, ?string $idempotencyKey = null): array
    {
        $headers = [];
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $response = $this->send('POST', 'generations', $payload, $headers);
        $data = $response->json();

        if (! is_array($data) || empty($data['id']) || ! is_string($data['id'])) {
            throw new PixelforgeApiException(
                message: 'Invalid response received from Pixelforge createGeneration endpoint.',
                errorCode: 'invalid_response',
                statusCode: $response->status(),
                errorType: 'api_error',
            );
        }

        return $data;
    }

    /**
     * Retrieve a single generation by its upstream provider ID.
     *
     * @return array<string, mixed>
     *
     * @throws PixelforgeException
     */
    public function getGeneration(string $providerGenerationId): array
    {
        $response = $this->send('GET', 'generations/'.ltrim($providerGenerationId, '/'));
        $data = $response->json();

        if (! is_array($data) || empty($data['id'])) {
            throw new PixelforgeApiException(
                message: 'Invalid response received from Pixelforge getGeneration endpoint.',
                errorCode: 'invalid_response',
                statusCode: $response->status(),
                errorType: 'api_error',
            );
        }

        return $data;
    }

    /**
     * Retrieve daily provider usage and billed USD costs from GET /v1/usage.
     *
     * @return array<string, mixed>
     *
     * @throws PixelforgeException
     */
    public function getUsage(?string $startDate = null, ?string $endDate = null): array
    {
        $queryParams = array_filter([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], fn (?string $val): bool => $val !== null && $val !== '');

        $response = $this->send('GET', 'usage', $queryParams);
        $data = $response->json();

        if (! is_array($data)) {
            throw new PixelforgeApiException(
                message: 'Invalid response received from Pixelforge usage endpoint.',
                errorCode: 'invalid_response',
                statusCode: $response->status(),
                errorType: 'api_error',
            );
        }

        return $data;
    }

    /**
     * Get the configured base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Check whether an API key is configured.
     */
    public function hasApiKey(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Get the underlying HTTP client builder.
     */
    protected function http(): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson();

        if ($this->hasApiKey()) {
            $client = $client->withToken($this->apiKey);
        }

        return $client;
    }

    /**
     * Send an HTTP request and handle connection / API errors.
     *
     * @param  array<string, mixed>  $queryOrData
     * @param  array<string, string>  $headers
     *
     * @throws PixelforgeException
     */
    protected function send(string $method, string $endpoint, array $queryOrData = [], array $headers = []): Response
    {
        $cleanEndpoint = ltrim($endpoint, '/');

        try {
            $client = $this->http();

            if (! empty($headers)) {
                $client = $client->withHeaders($headers);
            }

            $response = match (strtoupper($method)) {
                'GET' => $client->get($cleanEndpoint, $queryOrData),
                'POST' => $client->post($cleanEndpoint, $queryOrData),
                default => $client->send($method, $cleanEndpoint, $queryOrData),
            };
        } catch (ConnectionException $e) {
            $message = strtolower($e->getMessage());

            if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                throw new PixelforgeTimeoutException(
                    message: "Pixelforge API request timed out after {$this->timeout}s: {$e->getMessage()}",
                    code: $e->getCode(),
                    previous: $e,
                );
            }

            throw new PixelforgeConnectionException(
                message: "Failed to connect to Pixelforge API: {$e->getMessage()}",
                code: $e->getCode(),
                previous: $e,
            );
        }

        if ($response->failed()) {
            $this->handleApiError($response);
        }

        return $response;
    }

    /**
     * Parse and throw a PixelforgeApiException for failed responses.
     *
     * @throws PixelforgeApiException
     */
    protected function handleApiError(Response $response): void
    {
        $payload = $response->json();
        $error = is_array($payload) ? ($payload['error'] ?? []) : [];

        $message = $error['message'] ?? $response->reason() ?? 'Pixelforge API error';
        $code = $error['code'] ?? 'api_error';
        $type = $error['type'] ?? 'api_error';
        $param = $error['param'] ?? null;
        $errors = is_array($error['errors'] ?? null) ? $error['errors'] : [];

        $retryAfterHeader = $response->header('Retry-After');
        $retryAfter = is_numeric($retryAfterHeader) ? (int) $retryAfterHeader : null;

        throw new PixelforgeApiException(
            message: $message,
            errorCode: $code,
            statusCode: $response->status(),
            errorType: $type,
            param: $param,
            errors: $errors,
            retryAfter: $retryAfter,
        );
    }
}
