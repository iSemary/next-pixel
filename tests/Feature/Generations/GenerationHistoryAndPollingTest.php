<?php

namespace Tests\Feature\Generations;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\Generation;
use App\Models\User;
use App\Services\Pixelforge\PixelforgeClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passport\Passport;
use Tests\TestCase;

class GenerationHistoryAndPollingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->user = User::factory()->create();
    }

    public function test_dashboard_hydrates_users_recent_generations(): void
    {
        $otherUser = User::factory()->create();

        Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'dash-mine-1',
            'prompt' => 'My studio mountain shot',
            'size' => ImageSize::Landscape,
            'quality' => ImageQuality::High,
            'status' => GenerationStatus::Succeeded,
        ]);

        Generation::create([
            'user_id' => $otherUser->id,
            'idempotency_key' => 'dash-other-1',
            'prompt' => 'Someone elses prompt',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Low,
            'status' => GenerationStatus::Succeeded,
        ]);

        $this->actingAs($this->user)
            ->get(route('image-studio'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('recentGenerations', 1)
                ->where('recentGenerations.0.prompt', 'My studio mountain shot')
            );
    }

    public function test_library_supports_filters_and_computes_estimated_vs_actual_summary(): void
    {
        Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'lib-1',
            'prompt' => 'Cyberpunk neon alley',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Medium,
            'status' => GenerationStatus::Succeeded,
            'estimated_user_cost' => '0.090000000',
            'actual_user_cost' => '0.075000000',
            'settled_at' => now(),
        ]);

        Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'lib-2',
            'prompt' => '[fail] Cyberpunk broken alley',
            'size' => ImageSize::Landscape,
            'quality' => ImageQuality::High,
            'status' => GenerationStatus::Failed,
            'estimated_user_cost' => '0.250000000',
            'actual_user_cost' => '0.000000000',
            'settled_at' => now(),
        ]);

        // Unfiltered summary + items on /dashboard
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Library')
                ->has('generations.data', 2)
                ->where('summary.total_count', 2)
                ->where('summary.succeeded_count', 1)
                ->where('summary.failed_count', 1)
                ->where('summary.total_estimated_cost', '0.340000000')
                ->where('summary.total_actual_cost', '0.075000000')
            );

        // Filtered by status + quality + search
        $this->actingAs($this->user)
            ->get(route('dashboard', [
                'status' => 'succeeded',
                'quality' => 'medium',
                'search' => 'neon',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Library')
                ->has('generations.data', 1)
                ->where('generations.data.0.idempotency_key', 'lib-1')
            );
    }

    public function test_polling_endpoint_reconciles_stale_processing_generation_from_upstream_and_settles(): void
    {
        $generation = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'poll-reconcile-1',
            'provider_generation_id' => 'gen_stale_001',
            'prompt' => 'Fallback sync test',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Low,
            'status' => GenerationStatus::Processing,
            'submitted_at' => now()->subSeconds(35),
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

        $mockClient = $this->createMock(PixelforgeClientInterface::class);
        $mockClient->expects($this->once())
            ->method('getGeneration')
            ->with('gen_stale_001')
            ->willReturn([
                'id' => 'gen_stale_001',
                'object' => 'generation',
                'status' => 'succeeded',
                'image_url' => 'https://pixelforge.promptedit.dev/images/gen_stale_001.png',
                'image_url_expires_at' => '2026-11-25T20:00:00Z',
                'completed_at' => '2026-11-24T22:10:00Z',
                'usage' => [
                    'input_text_tokens' => 4,
                    'input_image_tokens' => 0,
                    'output_tokens' => 280,
                    'reasoning_tokens' => 100,
                    'total_tokens' => 384,
                ],
            ]);
        $this->app->instance(PixelforgeClientInterface::class, $mockClient);

        $response = $this->actingAs($this->user)
            ->getJson(route('generations.index', ['ids' => $generation->id]));

        $response->assertOk()
            ->assertJsonCount(1, 'generations')
            ->assertJsonPath('generations.0.status', 'succeeded')
            ->assertJsonPath('generations.0.is_settled', true)
            ->assertJsonPath('generations.0.actual_total_tokens', 384);

        $generation->refresh();
        $this->assertSame(GenerationStatus::Succeeded, $generation->status);
        $this->assertNotNull($generation->settled_at);
        // (4*5 + 280*40 + 100*12.5) / 1e6 = (20 + 11200 + 1250)/1e6 = 0.012470000; *1.25 = 0.015587500
        $this->assertSame('0.012470000', $generation->actual_provider_cost);
        $this->assertSame('0.015587500', $generation->actual_user_cost);
    }

    public function test_api_v1_list_and_show_enforce_ownership_and_return_settlement_fields(): void
    {
        $otherUser = User::factory()->create();

        $mine = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'api-mine-1',
            'prompt' => 'API owned prompt',
            'size' => ImageSize::Portrait,
            'quality' => ImageQuality::High,
            'status' => GenerationStatus::Succeeded,
            'actual_provider_cost' => '0.200000000',
            'actual_user_cost' => '0.250000000',
            'settled_at' => now(),
        ]);

        $theirs = Generation::create([
            'user_id' => $otherUser->id,
            'idempotency_key' => 'api-theirs-1',
            'prompt' => 'Secret prompt',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Low,
            'status' => GenerationStatus::Queued,
        ]);

        Passport::actingAs($this->user);

        $this->getJson(route('api.v1.generations.index', ['status' => 'succeeded']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.actual_user_cost', '0.250000000')
            ->assertJsonPath('summary.total_count', 1);

        $this->getJson(route('api.v1.generations.show', $mine))
            ->assertOk()
            ->assertJsonPath('data.id', $mine->id);

        $this->getJson(route('api.v1.generations.show', $theirs))
            ->assertNotFound();
    }
}
