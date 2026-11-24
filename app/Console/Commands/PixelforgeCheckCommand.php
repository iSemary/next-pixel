<?php

namespace App\Console\Commands;

use App\Services\Pixelforge\Exceptions\PixelforgeApiException;
use App\Services\Pixelforge\Exceptions\PixelforgeConnectionException;
use App\Services\Pixelforge\Exceptions\PixelforgeTimeoutException;
use App\Services\Pixelforge\PixelforgeClientInterface;
use Illuminate\Console\Command;

class PixelforgeCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pixelforge:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Pixelforge API connectivity and authentication without creating a generation';

    /**
     * Execute the console command.
     */
    public function handle(PixelforgeClientInterface $client): int
    {
        $this->info('Verifying Pixelforge API connectivity...');
        $this->line("Base URL: <comment>{$client->getBaseUrl()}</comment>");

        // 1. Verify Connectivity via public Pricing endpoint
        try {
            $pricing = $client->getPricing();
            $this->info('✓ Connectivity: OK (Pricing endpoint reached successfully)');

            if (isset($pricing['rates']) && is_array($pricing['rates'])) {
                $this->table(
                    ['Token Type', 'Rate (USD per 1M tokens)'],
                    collect($pricing['rates'])->map(fn ($rate, $type) => [
                        str_replace('_', ' ', (string) $type),
                        "$ {$rate}",
                    ])->all()
                );
            }
        } catch (PixelforgeTimeoutException $e) {
            $this->error("✗ Connectivity FAILED (Timeout): {$e->getMessage()}");

            return self::FAILURE;
        } catch (PixelforgeConnectionException $e) {
            $this->error("✗ Connectivity FAILED (Connection): {$e->getMessage()}");

            return self::FAILURE;
        } catch (PixelforgeApiException $e) {
            $this->error("✗ Connectivity FAILED (API Error [{$e->getErrorCode()}]): {$e->getMessage()}");

            return self::FAILURE;
        }

        // 2. Verify Authentication via List Generations (read-only, limit 1)
        if (! $client->hasApiKey()) {
            $this->warn('! Authentication: SKIPPED (PIXELFORGE_API_KEY is not configured)');

            return self::SUCCESS;
        }

        $this->line('Verifying API Key authentication...');

        try {
            $client->listGenerations(['limit' => 1]);
            $this->info('✓ Authentication: OK (API key is valid)');
        } catch (PixelforgeApiException $e) {
            if ($e->isAuthenticationError()) {
                $this->error("✗ Authentication FAILED (401 invalid_api_key): {$e->getMessage()}");

                return self::FAILURE;
            }

            $this->error("✗ Authentication check FAILED ([{$e->getErrorCode()}]): {$e->getMessage()}");

            return self::FAILURE;
        } catch (PixelforgeConnectionException|PixelforgeTimeoutException $e) {
            $this->error("✗ Authentication check FAILED (Network): {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('All Pixelforge API checks completed successfully.');

        return self::SUCCESS;
    }
}
