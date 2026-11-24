<?php

namespace Tests\Feature\Credits;

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Jobs\SubmitGenerationToPixelforge;
use App\Models\Generation;
use App\Models\User;
use App\Services\Generations\GenerationSettlementServiceInterface;
use App\Services\Pixelforge\Exceptions\PixelforgeApiException;
use App\Services\Pixelforge\PixelforgeClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CreditBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected array $standardPricingSnapshot = [
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
            'output' => [
                'low' => ['1024x1024' => 280, '1536x1024' => 420, '1024x1536' => 420],
                'medium' => ['1024x1024' => 1100, '1536x1024' => 1650, '1024x1536' => 1650],
                'high' => ['1024x1024' => 4300, '1536x1024' => 6450, '1024x1536' => 6450],
            ],
            'reasoning' => [
                'low' => ['min' => 0, 'max' => 500],
                'medium' => ['min' => 200, 'max' => 2000],
                'high' => ['min' => 1000, 'max' => 8000],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $clientMock = $this->createMock(PixelforgeClientInterface::class);
        $clientMock->method('getPricing')->willReturn($this->standardPricingSnapshot);
        $this->app->instance(PixelforgeClientInterface::class, $clientMock);
    }

    public function test_user_can_buy_simulated_credits_via_web_and_api(): void
    {
        $user = User::factory()->create();
        $this->assertSame('0.000000000', $user->credit_balance);

        // Web top-up
        $this->actingAs($user)
            ->postJson(route('credits.top-up'), ['amount' => 25])
            ->assertOk()
            ->assertJsonPath('credit_balance', '25.000000000')
            ->assertJsonPath('added_amount', '25.000000000');

        // API top-up
        Passport::actingAs($user);
        $this->postJson(route('api.v1.credits.top-up'), ['amount' => 10.5])
            ->assertOk()
            ->assertJsonPath('credit_balance', '35.500000000');

        $user->refresh();
        $this->assertSame('35.500000000', $user->credit_balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => CreditTransactionType::TopUp->value,
            'amount' => '25.000000000',
            'balance_after' => '25.000000000',
        ]);
    }

    public function test_generation_is_blocked_with_402_when_user_cannot_afford_it(): void
    {
        Queue::fake();
        $user = User::factory()->withCredits('0.050000000')->create();

        // High quality costs > $0.30 estimated, and batch of 1 exceeds $0.05
        $response = $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => 'High resolution cathedral',
            'size' => '1536x1024',
            'quality' => 'high',
            'num_generations' => 1,
        ]);

        $response->assertStatus(402)
            ->assertJsonPath('error_code', 'insufficient_credits');

        $this->assertDatabaseCount('generations', 0);
        $this->assertSame('0.050000000', $user->fresh()->credit_balance);
        Queue::assertNothingPushed();
    }

    public function test_batch_generation_blocks_atomically_if_user_can_only_afford_subset(): void
    {
        Queue::fake();
        // Enough for 1 medium 1024x1024 (~$0.09), NOT enough for 3 (~$0.27)
        $user = User::factory()->withCredits('0.120000000')->create();

        $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => 'Forest waterfall',
            'size' => '1024x1024',
            'quality' => 'medium',
            'num_generations' => 3,
        ])->assertStatus(402);

        $this->assertDatabaseCount('generations', 0);
        $this->assertSame('0.120000000', $user->fresh()->credit_balance);
        Queue::assertNothingPushed();
    }

    public function test_reserves_estimated_cost_and_refunds_unused_excess_on_success_settlement(): void
    {
        Queue::fake();
        $user = User::factory()->withCredits('10.000000000')->create();

        $submitResponse = $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => 'A tranquil alpine meadow',
            'size' => '1536x1024',
            'quality' => 'high',
            'idempotency_key' => 'credit-lifecycle-success-1',
        ])->assertStatus(202);

        $generation = Generation::where('idempotency_key', 'credit-lifecycle-success-1')->firstOrFail();
        $estimatedUserCost = (string) $generation->estimated_user_cost;

        $expectedAfterReserve = bcsub('10.000000000', $estimatedUserCost, 9);
        $this->assertSame($expectedAfterReserve, $user->fresh()->credit_balance);
        $submitResponse->assertJsonPath('credit_balance', $expectedAfterReserve);

        // Simulate lower actual reasoning tokens upon completion
        $generation->update([
            'status' => GenerationStatus::Succeeded,
            'completed_at' => now(),
            'actual_input_text_tokens' => 6,
            'actual_input_image_tokens' => 0,
            'actual_output_tokens' => 6450,
            'actual_reasoning_tokens' => 2000,
            'actual_total_tokens' => 8456,
        ]);

        /** @var GenerationSettlementServiceInterface $settlement */
        $settlement = $this->app->make(GenerationSettlementServiceInterface::class);
        $settlement->settle($generation);
        $generation->save();

        $actualUserCost = (string) $generation->actual_user_cost;
        $expectedFinalBalance = bcsub('10.000000000', $actualUserCost, 9);

        $this->assertSame($expectedFinalBalance, $user->fresh()->credit_balance);
        $this->assertDatabaseHas('credit_transactions', [
            'generation_id' => $generation->id,
            'type' => CreditTransactionType::Reserve->value,
        ]);
        $this->assertDatabaseHas('credit_transactions', [
            'generation_id' => $generation->id,
            'type' => CreditTransactionType::Refund->value,
        ]);
    }

    public function test_failed_generation_refunds_100_percent_of_reserved_credits(): void
    {
        Queue::fake();
        $user = User::factory()->withCredits('5.000000000')->create();

        $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => '[fail] Trigger failure refund',
            'size' => '1024x1024',
            'quality' => 'medium',
            'idempotency_key' => 'credit-lifecycle-fail-1',
        ])->assertStatus(202);

        $generation = Generation::where('idempotency_key', 'credit-lifecycle-fail-1')->firstOrFail();

        // Balance temporarily reduced while queued/processing
        $this->assertTrue(bccomp($user->fresh()->credit_balance, '5.000000000', 9) < 0);

        // Synchronous terminal rejection or webhook failure settles as free & refunds 100%
        $mockClient = $this->createMock(PixelforgeClientInterface::class);
        $mockClient->method('createGeneration')->willThrowException(
            new PixelforgeApiException(
                message: 'Blocked by safety filter.',
                errorCode: 'content_policy_violation',
                statusCode: 400,
            )
        );

        $job = new SubmitGenerationToPixelforge($generation->id);
        $job->handle($mockClient);

        $generation->refresh();
        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('0.000000000', $generation->actual_user_cost);
        $this->assertNotNull($generation->settled_at);
        $this->assertSame('5.000000000', $user->fresh()->credit_balance);
    }
}
