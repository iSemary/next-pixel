<?php

namespace Tests\Feature\Generations;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Jobs\SubmitGenerationToPixelforge;
use App\Models\Generation;
use App\Models\User;
use App\Services\Pixelforge\PixelforgeClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SubmitGenerationTest extends TestCase
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

    public function test_guest_cannot_submit_generation(): void
    {
        $response = $this->postJson(route('generations.store'), [
            'prompt' => 'A beautiful mountain landscape',
            'size' => '1024x1024',
            'quality' => 'medium',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_web_user_can_submit_generation_and_dispatches_job(): void
    {
        Queue::fake();
        $user = User::factory()->withCredits()->create();

        $response = $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => 'An astronaut walking on Mars, digital painting',
            'size' => '1536x1024',
            'quality' => 'high',
            'idempotency_key' => 'idemp-web-uuid-001',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('is_idempotent_replay', false)
            ->assertJsonCount(1, 'generations')
            ->assertJsonPath('generations.0.prompt', 'An astronaut walking on Mars, digital painting')
            ->assertJsonPath('generations.0.size', '1536x1024')
            ->assertJsonPath('generations.0.quality', 'high')
            ->assertJsonPath('generations.0.status', 'queued');

        $this->assertDatabaseHas('generations', [
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-web-uuid-001',
            'prompt' => 'An astronaut walking on Mars, digital painting',
            'size' => ImageSize::Landscape->value,
            'quality' => ImageQuality::High->value,
            'status' => GenerationStatus::Queued->value,
        ]);

        $generation = Generation::where('idempotency_key', 'idemp-web-uuid-001')->first();
        $this->assertNotNull($generation);
        $this->assertNotNull($generation->estimated_total_tokens_max);
        $this->assertNotNull($generation->estimated_user_cost);
        $this->assertSame('0.2500', (string) $generation->applied_markup_rate);

        Queue::assertPushed(SubmitGenerationToPixelforge::class, function ($job) use ($generation) {
            return $job->generationId === $generation->id;
        });
    }

    public function test_authenticated_api_user_can_submit_generation_via_v1_api(): void
    {
        Queue::fake();
        $user = User::factory()->withCredits()->create();
        Passport::actingAs($user);

        $response = $this->postJson(route('api.v1.generations.store'), [
            'prompt' => 'Cyberpunk street with neon signs in rain',
            'size' => '1024x1024',
            'quality' => 'medium',
        ], ['Idempotency-Key' => 'idemp-header-v1-999']);

        $response->assertStatus(202)
            ->assertJsonPath('is_idempotent_replay', false)
            ->assertJsonPath('data.prompt', 'Cyberpunk street with neon signs in rain')
            ->assertJsonPath('data.size', '1024x1024')
            ->assertJsonPath('data.quality', 'medium')
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.idempotency_key', 'idemp-header-v1-999');

        $this->assertDatabaseHas('generations', [
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-header-v1-999',
            'status' => 'queued',
        ]);

        Queue::assertPushed(SubmitGenerationToPixelforge::class, 1);
    }

    public function test_submitting_with_same_idempotency_key_is_deduplicated_and_replayed(): void
    {
        Queue::fake();
        $user = User::factory()->withCredits()->create();

        // 1. Initial submission
        $firstResponse = $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => 'Ancient library with magical floating books',
            'size' => '1024x1024',
            'quality' => 'low',
            'idempotency_key' => 'idemp-repeat-key-777',
        ]);

        $firstResponse->assertStatus(202)
            ->assertJsonPath('is_idempotent_replay', false);

        Queue::assertPushed(SubmitGenerationToPixelforge::class, 1);

        // 2. Duplicate submission with exact same key
        $secondResponse = $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => 'Ancient library with magical floating books',
            'size' => '1024x1024',
            'quality' => 'low',
            'idempotency_key' => 'idemp-repeat-key-777',
        ]);

        $secondResponse->assertStatus(200)
            ->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('is_idempotent_replay', true);

        // Assert exactly 1 record exists in DB and no second queue job was pushed
        $this->assertSame(1, Generation::where('idempotency_key', 'idemp-repeat-key-777')->count());
        Queue::assertPushed(SubmitGenerationToPixelforge::class, 1);
    }

    public function test_batch_generation_creates_multiple_records_and_dispatches_jobs(): void
    {
        Queue::fake();
        $user = User::factory()->withCredits()->create();

        $response = $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => 'A futuristic robotic cat',
            'size' => '1024x1024',
            'quality' => 'medium',
            'num_generations' => 3,
            'idempotency_key' => 'idemp-batch-uuid',
        ]);

        $response->assertStatus(202)
            ->assertJsonCount(3, 'generations')
            ->assertJsonPath('is_idempotent_replay', false);

        $this->assertDatabaseHas('generations', ['idempotency_key' => 'idemp-batch-uuid-1']);
        $this->assertDatabaseHas('generations', ['idempotency_key' => 'idemp-batch-uuid-2']);
        $this->assertDatabaseHas('generations', ['idempotency_key' => 'idemp-batch-uuid-3']);

        Queue::assertPushed(SubmitGenerationToPixelforge::class, 3);
    }

    public function test_validation_errors_for_invalid_generation_requests(): void
    {
        $user = User::factory()->withCredits()->create();

        $response = $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => '', // empty prompt
            'size' => '9999x9999', // unsupported size
            'quality' => 'ultra', // unsupported quality
            'num_generations' => 10, // max 4
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['prompt', 'size', 'quality', 'num_generations']);
    }

    public function test_reusing_another_users_idempotency_key_returns_409_without_leaking_data(): void
    {
        Queue::fake();
        $owner = User::factory()->withCredits()->create();
        $otherUser = User::factory()->withCredits()->create();

        $this->actingAs($owner)->postJson(route('generations.store'), [
            'prompt' => 'Owner secret prompt',
            'size' => '1024x1024',
            'quality' => 'medium',
            'idempotency_key' => 'shared-key-attempt-1',
        ])->assertStatus(202);

        $response = $this->actingAs($otherUser)->postJson(route('generations.store'), [
            'prompt' => 'Owner secret prompt',
            'size' => '1024x1024',
            'quality' => 'medium',
            'idempotency_key' => 'shared-key-attempt-1',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error_code', 'idempotency_key_reused');
    }

    public function test_reusing_own_idempotency_key_with_different_parameters_returns_409(): void
    {
        Queue::fake();
        $user = User::factory()->withCredits()->create();

        $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => 'Original prompt',
            'size' => '1024x1024',
            'quality' => 'low',
            'idempotency_key' => 'reused-mismatch-key',
        ])->assertStatus(202);

        $response = $this->actingAs($user)->postJson(route('generations.store'), [
            'prompt' => 'Modified prompt',
            'size' => '1024x1024',
            'quality' => 'low',
            'idempotency_key' => 'reused-mismatch-key',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error_code', 'idempotency_key_reused');
    }

    public function test_authenticated_user_can_upload_reference_image_and_non_images_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('generations.reference-images.store'), [
            'image' => UploadedFile::fake()->image('style-guide.png', 256, 256),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['url', 'path']);

        $storedPath = $response->json('path');
        Storage::disk('public')->assertExists($storedPath);

        // Non-image upload rejected
        $this->actingAs($user)->postJson(route('generations.reference-images.store'), [
            'image' => UploadedFile::fake()->create('script.txt', 10, 'text/plain'),
        ])->assertStatus(422)->assertJsonValidationErrors(['image']);

        // API v1 upload route also works via Passport
        Passport::actingAs($user);
        $this->postJson(route('api.v1.generations.reference-images.store'), [
            'image' => UploadedFile::fake()->image('api-style.jpg', 128, 128),
        ])->assertStatus(201);
    }

    public function test_generation_submission_with_reference_images_snapshots_tokens_and_forwards_to_pixelforge(): void
    {
        $user = User::factory()->withCredits()->create();

        $refs = [
            'https://example.com/ref-a.png',
            'https://example.com/ref-b.jpg',
        ];

        $capturedPayload = null;
        $clientMock = $this->createMock(PixelforgeClientInterface::class);
        $clientMock->method('getPricing')->willReturn($this->standardPricingSnapshot);
        $clientMock->expects($this->once())
            ->method('createGeneration')
            ->willReturnCallback(function (array $payload) use (&$capturedPayload) {
                $capturedPayload = $payload;

                return [
                    'id' => 'gen_with_refs_123',
                    'status' => 'queued',
                ];
            });
        $this->app->instance(PixelforgeClientInterface::class, $clientMock);

        Passport::actingAs($user);

        $response = $this->postJson(route('api.v1.generations.store'), [
            'prompt' => 'Blend these two architectural styles',
            'size' => '1024x1024',
            'quality' => 'medium',
            'reference_images' => $refs,
            'idempotency_key' => 'idemp-refs-e2e-1',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.reference_images', $refs)
            ->assertJsonPath('data.estimated_input_image_tokens', 1530);

        $this->assertSame($refs, $capturedPayload['reference_images'] ?? null);
    }
}
