<?php

namespace Tests\Unit\Services;

use App\Services\Pixelforge\Exceptions\PixelforgeApiException;
use App\Services\Pixelforge\Exceptions\PixelforgeConnectionException;
use App\Services\Pixelforge\Exceptions\PixelforgeTimeoutException;
use App\Services\Pixelforge\PixelforgeClient;
use App\Services\Pixelforge\PixelforgeClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PixelforgeClientTest extends TestCase
{
    protected PixelforgeClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new PixelforgeClient(
            baseUrl: 'https://pixelforge.promptedit.dev/v1',
            apiKey: 'test_api_key_123',
            timeout: 10,
        );
    }

    public function test_get_pricing_returns_rates_preserving_decimal_strings(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response([
                'object' => 'pricing',
                'currency' => 'usd',
                'unit' => 'per_1m_tokens',
                'rates' => [
                    'input_text_tokens' => '5.00',
                    'input_image_tokens' => '10.00',
                    'output_tokens' => '40.00',
                    'reasoning_tokens' => '12.50',
                ],
                'tokens' => [
                    'characters_per_text_token' => 4,
                    'text_token_variance' => 0.15,
                    'per_reference_image' => 765,
                ],
            ], 200),
        ]);

        $pricing = $this->client->getPricing();

        $this->assertSame('pricing', $pricing['object']);
        $this->assertSame('usd', $pricing['currency']);
        $this->assertSame('per_1m_tokens', $pricing['unit']);

        // Verify monetary rates are strictly preserved as exact decimal strings (not floats)
        $this->assertSame('5.00', $pricing['rates']['input_text_tokens']);
        $this->assertIsString($pricing['rates']['input_text_tokens']);

        $this->assertSame('10.00', $pricing['rates']['input_image_tokens']);
        $this->assertIsString($pricing['rates']['input_image_tokens']);

        $this->assertSame('40.00', $pricing['rates']['output_tokens']);
        $this->assertIsString($pricing['rates']['output_tokens']);

        $this->assertSame('12.50', $pricing['rates']['reasoning_tokens']);
        $this->assertIsString($pricing['rates']['reasoning_tokens']);
    }

    public function test_list_generations_sends_bearer_token_and_query_parameters(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/generations*' => Http::response([
                'object' => 'list',
                'data' => [
                    [
                        'id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K',
                        'object' => 'generation',
                        'status' => 'succeeded',
                        'prompt' => 'A test prompt',
                        'rates' => [
                            'input_text_tokens' => '5.00',
                            'input_image_tokens' => '10.00',
                            'output_tokens' => '40.00',
                            'reasoning_tokens' => '12.50',
                        ],
                    ],
                ],
                'has_more' => false,
                'next_cursor' => null,
            ], 200),
        ]);

        $result = $this->client->listGenerations([
            'limit' => 5,
            'status' => 'succeeded',
            'cursor' => 'cursor_abc123',
        ]);

        $this->assertSame('list', $result['object']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K', $result['data'][0]['id']);

        // Verify Bearer token and query parameters sent
        Http::assertSent(function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer test_api_key_123')
                && $request->url() === 'https://pixelforge.promptedit.dev/v1/generations?limit=5&status=succeeded&cursor=cursor_abc123';
        });
    }

    public function test_invalid_api_key_throws_pixelforge_api_exception(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/generations*' => Http::response([
                'error' => [
                    'type' => 'authentication_error',
                    'code' => 'invalid_api_key',
                    'message' => 'The API key is missing, malformed or revoked.',
                ],
            ], 401),
        ]);

        try {
            $this->client->listGenerations();
            $this->fail('Expected PixelforgeApiException was not thrown.');
        } catch (PixelforgeApiException $e) {
            $this->assertSame(401, $e->getStatusCode());
            $this->assertSame('invalid_api_key', $e->getErrorCode());
            $this->assertSame('authentication_error', $e->getErrorType());
            $this->assertTrue($e->isAuthenticationError());
            $this->assertFalse($e->isRateLimited());
            $this->assertSame('The API key is missing, malformed or revoked.', $e->getMessage());
        }
    }

    public function test_validation_failed_throws_api_exception_with_param_and_errors(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/generations*' => Http::response([
                'error' => [
                    'type' => 'invalid_request_error',
                    'code' => 'validation_failed',
                    'message' => 'The selected size is invalid.',
                    'param' => 'size',
                    'errors' => [
                        'size' => ['The size must be one of: 1024x1024, 1536x1024, 1024x1536.'],
                    ],
                ],
            ], 422),
        ]);

        try {
            $this->client->listGenerations();
            $this->fail('Expected PixelforgeApiException was not thrown.');
        } catch (PixelforgeApiException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame('validation_failed', $e->getErrorCode());
            $this->assertSame('invalid_request_error', $e->getErrorType());
            $this->assertSame('size', $e->getParam());
            $this->assertArrayHasKey('size', $e->getErrors());
            $this->assertTrue($e->isValidationFailed());
        }
    }

    public function test_rate_limit_exceeded_throws_with_retry_after(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/generations*' => Http::response([
                'error' => [
                    'type' => 'rate_limit_error',
                    'code' => 'rate_limit_exceeded',
                    'message' => 'Too many requests this minute.',
                ],
            ], 429, ['Retry-After' => '15']),
        ]);

        try {
            $this->client->listGenerations();
            $this->fail('Expected PixelforgeApiException was not thrown.');
        } catch (PixelforgeApiException $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertSame('rate_limit_exceeded', $e->getErrorCode());
            $this->assertSame('rate_limit_error', $e->getErrorType());
            $this->assertSame(15, $e->getRetryAfter());
            $this->assertTrue($e->isRateLimited());
        }
    }

    public function test_overloaded_service_throws_with_retry_after(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response([
                'error' => [
                    'type' => 'api_error',
                    'code' => 'overloaded',
                    'message' => 'Pixelforge is temporarily overloaded.',
                ],
            ], 503, ['Retry-After' => '2']),
        ]);

        try {
            $this->client->getPricing();
            $this->fail('Expected PixelforgeApiException was not thrown.');
        } catch (PixelforgeApiException $e) {
            $this->assertSame(503, $e->getStatusCode());
            $this->assertSame('overloaded', $e->getErrorCode());
            $this->assertSame('api_error', $e->getErrorType());
            $this->assertSame(2, $e->getRetryAfter());
            $this->assertTrue($e->isOverloaded());
        }
    }

    public function test_connection_failure_throws_pixelforge_connection_exception(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Could not resolve host: pixelforge.promptedit.dev');
        });

        $this->expectException(PixelforgeConnectionException::class);
        $this->expectExceptionMessage('Failed to connect to Pixelforge API: Could not resolve host');

        $this->client->getPricing();
    }

    public function test_request_timeout_throws_pixelforge_timeout_exception(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Operation timed out after 10000 milliseconds');
        });

        $this->expectException(PixelforgeTimeoutException::class);
        $this->expectExceptionMessage('Pixelforge API request timed out after 10s');

        $this->client->getPricing();
    }

    public function test_client_resolves_from_container_via_interface(): void
    {
        config([
            'services.pixelforge.base_url' => 'https://custom.api.dev/v1',
            'services.pixelforge.api_key' => 'custom_key_456',
            'services.pixelforge.timeout' => 25,
        ]);

        $resolved = app(PixelforgeClientInterface::class);

        $this->assertInstanceOf(PixelforgeClient::class, $resolved);
        $this->assertSame('https://custom.api.dev/v1', $resolved->getBaseUrl());
        $this->assertTrue($resolved->hasApiKey());
    }
}
