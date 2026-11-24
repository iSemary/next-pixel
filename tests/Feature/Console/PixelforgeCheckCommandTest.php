<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PixelforgeCheckCommandTest extends TestCase
{
    public function test_check_command_succeeds_when_api_and_key_are_valid(): void
    {
        config([
            'services.pixelforge.base_url' => 'https://pixelforge.promptedit.dev/v1',
            'services.pixelforge.api_key' => 'valid_test_key',
        ]);

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
            ], 200),
            'https://pixelforge.promptedit.dev/v1/generations*' => Http::response([
                'object' => 'list',
                'data' => [],
                'has_more' => false,
                'next_cursor' => null,
            ], 200),
        ]);

        $this->artisan('pixelforge:check')
            ->expectsOutputToContain('Connectivity: OK')
            ->expectsOutputToContain('Authentication: OK')
            ->expectsOutputToContain('All Pixelforge API checks completed successfully.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_check_command_skips_auth_when_no_api_key_configured(): void
    {
        config([
            'services.pixelforge.base_url' => 'https://pixelforge.promptedit.dev/v1',
            'services.pixelforge.api_key' => '',
        ]);

        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response([
                'object' => 'pricing',
                'rates' => [
                    'input_text_tokens' => '5.00',
                ],
            ], 200),
        ]);

        $this->artisan('pixelforge:check')
            ->expectsOutputToContain('Connectivity: OK')
            ->expectsOutputToContain('Authentication: SKIPPED (PIXELFORGE_API_KEY is not configured)')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_check_command_fails_when_api_key_is_invalid(): void
    {
        config([
            'services.pixelforge.base_url' => 'https://pixelforge.promptedit.dev/v1',
            'services.pixelforge.api_key' => 'invalid_key',
        ]);

        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response([
                'object' => 'pricing',
                'rates' => [
                    'input_text_tokens' => '5.00',
                ],
            ], 200),
            'https://pixelforge.promptedit.dev/v1/generations*' => Http::response([
                'error' => [
                    'type' => 'authentication_error',
                    'code' => 'invalid_api_key',
                    'message' => 'The API key is missing, malformed or revoked.',
                ],
            ], 401),
        ]);

        $this->artisan('pixelforge:check')
            ->expectsOutputToContain('Connectivity: OK')
            ->expectsOutputToContain('Authentication FAILED (401 invalid_api_key)')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_check_command_fails_when_pricing_endpoint_is_unreachable(): void
    {
        config([
            'services.pixelforge.base_url' => 'https://pixelforge.promptedit.dev/v1',
        ]);

        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => function () {
                throw new ConnectionException('Could not resolve host');
            },
        ]);

        $this->artisan('pixelforge:check')
            ->expectsOutputToContain('Connectivity FAILED (Connection)')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_check_command_fails_when_pricing_endpoint_times_out(): void
    {
        config([
            'services.pixelforge.base_url' => 'https://pixelforge.promptedit.dev/v1',
        ]);

        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $this->artisan('pixelforge:check')
            ->expectsOutputToContain('Connectivity FAILED (Timeout)')
            ->assertExitCode(Command::FAILURE);
    }
}
