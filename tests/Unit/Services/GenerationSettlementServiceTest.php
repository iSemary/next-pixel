<?php

namespace Tests\Unit\Services;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\Generation;
use App\Models\User;
use App\Services\Generations\GenerationSettlementServiceInterface;
use App\Services\Pixelforge\PixelforgeClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GenerationSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GenerationSettlementServiceInterface $settlementService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->user = User::factory()->create();
        $this->settlementService = $this->app->make(GenerationSettlementServiceInterface::class);
    }

    public function test_settles_succeeded_generation_with_exact_bcmath_precision_and_historical_rates(): void
    {
        // Even if live pricing changes later, historical snapshot rates must apply
        $changedClient = $this->createMock(PixelforgeClientInterface::class);
        $changedClient->method('getPricing')->willReturn([
            'rates' => [
                'input_text_tokens' => '99.00',
                'input_image_tokens' => '99.00',
                'output_tokens' => '99.00',
                'reasoning_tokens' => '99.00',
            ],
        ]);
        $this->app->instance(PixelforgeClientInterface::class, $changedClient);

        $generation = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'idemp-settle-exact-1',
            'prompt' => 'A lighthouse at dawn',
            'size' => ImageSize::Landscape,
            'quality' => ImageQuality::High,
            'status' => GenerationStatus::Succeeded,
            'completed_at' => now(),
            'actual_input_text_tokens' => 12,
            'actual_input_image_tokens' => 765,
            'actual_output_tokens' => 6450,
            'actual_reasoning_tokens' => 3184,
            'actual_total_tokens' => 10411,
            'applied_markup_rate' => '0.2500',
            'pricing_rate_snapshot' => [
                'rates' => [
                    'input_text_tokens' => '5.00',
                    'input_image_tokens' => '10.00',
                    'output_tokens' => '40.00',
                    'reasoning_tokens' => '12.50',
                ],
            ],
        ]);

        // Provider math at 9 decimals:
        // text:      (12 * 5.00) / 1e6     = 0.000060000
        // image:     (765 * 10.00) / 1e6   = 0.007650000
        // output:    (6450 * 40.00) / 1e6  = 0.258000000
        // reasoning: (3184 * 12.50) / 1e6  = 0.039800000
        // sum                              = 0.305510000
        // user (+25% = * 1.25)             = 0.381887500
        $this->settlementService->settle($generation);

        $this->assertSame('0.305510000', $generation->actual_provider_cost);
        $this->assertSame('0.381887500', $generation->actual_user_cost);
        $this->assertNotNull($generation->settled_at);
    }

    public function test_failed_generations_are_always_settled_as_free(): void
    {
        $generation = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'idemp-settle-free-1',
            'prompt' => '[fail] expensive high-res run',
            'size' => ImageSize::Landscape,
            'quality' => ImageQuality::High,
            'status' => GenerationStatus::Failed,
            'error_code' => 'internal_error',
            'error_message' => 'Failed mid-flight.',
            'estimated_provider_cost' => '0.350000000',
            'estimated_user_cost' => '0.437500000',
            'completed_at' => now(),
        ]);

        $this->settlementService->settle($generation);

        $this->assertSame('0.000000000', $generation->actual_provider_cost);
        $this->assertSame('0.000000000', $generation->actual_user_cost);
        $this->assertNotNull($generation->settled_at);
    }

    public function test_settle_is_idempotent_and_skips_non_terminal_generations(): void
    {
        $queued = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'idemp-non-terminal-1',
            'prompt' => 'Still processing',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Low,
            'status' => GenerationStatus::Processing,
        ]);

        $this->settlementService->settle($queued);
        $this->assertNull($queued->settled_at);
        $this->assertNull($queued->actual_user_cost);

        $settledAt = now()->subMinute()->startOfSecond();
        $alreadySettled = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'idemp-already-settled-1',
            'prompt' => 'Done already',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Low,
            'status' => GenerationStatus::Succeeded,
            'actual_provider_cost' => '0.010000000',
            'actual_user_cost' => '0.012500000',
            'settled_at' => $settledAt,
            'completed_at' => $settledAt,
        ]);

        $this->settlementService->settle($alreadySettled, [
            'output_tokens' => '999.00',
        ]);

        $this->assertSame('0.010000000', $alreadySettled->actual_provider_cost);
        $this->assertSame('0.012500000', $alreadySettled->actual_user_cost);
        $this->assertTrue($alreadySettled->settled_at->equalTo($settledAt));
    }
}
