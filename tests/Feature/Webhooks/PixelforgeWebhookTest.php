<?php

namespace Tests\Feature\Webhooks;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Enums\WebhookProcessingStatus;
use App\Jobs\ProcessPixelforgeWebhookEvent;
use App\Models\Generation;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PixelforgeWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $secret = 'whsec_test_webhook_secret_xyz';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.pixelforge.webhook_secret' => $this->secret]);
    }

    /**
     * Build raw JSON and signed Pixelforge headers.
     *
     * @param  array<string, mixed>  $payload
     * @return array{raw: string, headers: array<string, string>}
     */
    protected function signPayload(array $payload, int $attempt = 1, ?int $timestamp = null): array
    {
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $ts = $timestamp ?? time();
        $sig = hash_hmac('sha256', "{$ts}.{$raw}", $this->secret);

        return [
            'raw' => $raw,
            'headers' => [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PIXELFORGE_SIGNATURE' => "t={$ts},v1={$sig}",
                'HTTP_PIXELFORGE_EVENT_ID' => (string) $payload['id'],
                'HTTP_PIXELFORGE_EVENT_TYPE' => (string) $payload['type'],
                'HTTP_PIXELFORGE_DELIVERY_ATTEMPT' => (string) $attempt,
            ],
        ];
    }

    public function test_rejects_requests_with_invalid_or_missing_signature(): void
    {
        $raw = json_encode(['id' => 'evt_bad_sig', 'type' => 'ping'], JSON_THROW_ON_ERROR);

        $response = $this->call('POST', route('webhooks.pixelforge'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PIXELFORGE_SIGNATURE' => 't='.time().',v1=forgedsignature',
        ], $raw);

        $response->assertStatus(400);
        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_accepts_signed_ping_event_without_csrf_and_marks_processed(): void
    {
        Queue::fake();

        $signed = $this->signPayload([
            'id' => 'evt_ping_001',
            'object' => 'event',
            'type' => 'ping',
            'created_at' => '2026-11-24T20:00:46Z',
            'data' => ['object' => ['object' => 'ping', 'message' => 'Hello']],
        ]);

        $response = $this->call('POST', route('webhooks.pixelforge'), [], [], [], $signed['headers'], $signed['raw']);

        $response->assertNoContent();

        $this->assertDatabaseHas('webhook_events', [
            'event_id' => 'evt_ping_001',
            'event_type' => 'ping',
            'status' => WebhookProcessingStatus::Processed->value,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_deduplicates_duplicate_webhook_events_by_event_id(): void
    {
        Queue::fake();

        $payload = [
            'id' => 'evt_dup_001',
            'object' => 'event',
            'type' => 'generation.succeeded',
            'created_at' => '2026-11-24T20:01:00Z',
            'data' => [
                'object' => [
                    'id' => 'gen_provider_dup',
                    'object' => 'generation',
                    'status' => 'succeeded',
                ],
            ],
        ];

        $first = $this->signPayload($payload, attempt: 1);
        $this->call('POST', route('webhooks.pixelforge'), [], [], [], $first['headers'], $first['raw'])
            ->assertNoContent();

        $second = $this->signPayload($payload, attempt: 2);
        $this->call('POST', route('webhooks.pixelforge'), [], [], [], $second['headers'], $second['raw'])
            ->assertNoContent();

        $this->assertSame(1, WebhookEvent::where('event_id', 'evt_dup_001')->count());
        Queue::assertPushed(ProcessPixelforgeWebhookEvent::class, 1);
    }

    public function test_processes_generation_succeeded_and_stores_actual_tokens(): void
    {
        $user = User::factory()->create();
        $generation = Generation::create([
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-webhook-success-1',
            'provider_generation_id' => 'gen_success_123',
            'prompt' => 'A peaceful mountain lake',
            'size' => ImageSize::Landscape,
            'quality' => ImageQuality::High,
            'status' => GenerationStatus::Processing,
        ]);

        $signed = $this->signPayload([
            'id' => 'evt_succeeded_123',
            'object' => 'event',
            'type' => 'generation.succeeded',
            'created_at' => '2026-11-24T20:05:00Z',
            'data' => [
                'object' => [
                    'id' => 'gen_success_123',
                    'object' => 'generation',
                    'status' => 'succeeded',
                    'image_url' => 'https://pixelforge.promptedit.dev/images/gen_success_123.png?sig=abc',
                    'image_url_expires_at' => '2026-11-25T20:05:00Z',
                    'started_at' => '2026-11-24T20:04:40Z',
                    'completed_at' => '2026-11-24T20:05:00Z',
                    'usage' => [
                        'input_text_tokens' => 12,
                        'input_image_tokens' => 0,
                        'output_tokens' => 6450,
                        'reasoning_tokens' => 3184,
                        'total_tokens' => 9646,
                    ],
                    'rates' => [
                        'input_text_tokens' => '5.00',
                        'input_image_tokens' => '10.00',
                        'output_tokens' => '40.00',
                        'reasoning_tokens' => '12.50',
                    ],
                ],
            ],
        ]);

        // Run with sync queue default for end-to-end processing verification
        config(['queue.default' => 'sync']);

        $this->call('POST', route('webhooks.pixelforge'), [], [], [], $signed['headers'], $signed['raw'])
            ->assertNoContent();

        $generation->refresh();

        $this->assertSame(GenerationStatus::Succeeded, $generation->status);
        $this->assertSame('https://pixelforge.promptedit.dev/images/gen_success_123.png?sig=abc', $generation->image_url);
        $this->assertSame(12, $generation->actual_input_text_tokens);
        $this->assertSame(6450, $generation->actual_output_tokens);
        $this->assertSame(3184, $generation->actual_reasoning_tokens);
        $this->assertSame(9646, $generation->actual_total_tokens);
        $this->assertNotNull($generation->completed_at);
        $this->assertSame('0.297860000', $generation->actual_provider_cost);
        $this->assertSame('0.372325000', $generation->actual_user_cost);
        $this->assertNotNull($generation->settled_at);

        $this->assertDatabaseHas('webhook_events', [
            'event_id' => 'evt_succeeded_123',
            'status' => WebhookProcessingStatus::Processed->value,
        ]);
    }

    public function test_out_of_order_processing_webhook_does_not_revert_succeeded_generation(): void
    {
        $user = User::factory()->create();
        $generation = Generation::create([
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-out-of-order-1',
            'provider_generation_id' => 'gen_ooo_456',
            'prompt' => 'Out of order test',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Medium,
            'status' => GenerationStatus::Succeeded,
            'image_url' => 'https://pixelforge.promptedit.dev/images/gen_ooo_456.png',
            'completed_at' => now(),
        ]);

        config(['queue.default' => 'sync']);

        $signed = $this->signPayload([
            'id' => 'evt_late_processing_456',
            'object' => 'event',
            'type' => 'generation.processing',
            'created_at' => '2026-11-24T20:03:00Z',
            'data' => [
                'object' => [
                    'id' => 'gen_ooo_456',
                    'object' => 'generation',
                    'status' => 'processing',
                    'started_at' => '2026-11-24T20:03:00Z',
                ],
            ],
        ]);

        $this->call('POST', route('webhooks.pixelforge'), [], [], [], $signed['headers'], $signed['raw'])
            ->assertNoContent();

        $generation->refresh();
        $this->assertSame(GenerationStatus::Succeeded, $generation->status);

        $this->assertDatabaseHas('webhook_events', [
            'event_id' => 'evt_late_processing_456',
            'status' => WebhookProcessingStatus::Ignored->value,
        ]);
    }

    public function test_correlates_early_webhook_via_metadata_before_provider_id_is_saved(): void
    {
        $user = User::factory()->create();
        $generation = Generation::create([
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-early-meta-1',
            'provider_generation_id' => null,
            'prompt' => 'Early arrival correlation test',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Low,
            'status' => GenerationStatus::Queued,
        ]);

        config(['queue.default' => 'sync']);

        $signed = $this->signPayload([
            'id' => 'evt_early_789',
            'object' => 'event',
            'type' => 'generation.failed',
            'created_at' => '2026-11-24T20:04:00Z',
            'data' => [
                'object' => [
                    'id' => 'gen_early_provider_789',
                    'object' => 'generation',
                    'status' => 'failed',
                    'metadata' => [
                        'nextpixel_generation_id' => (string) $generation->id,
                    ],
                    'error' => [
                        'code' => 'generation_failed',
                        'message' => 'Model failed intentionally.',
                    ],
                ],
            ],
        ]);

        $this->call('POST', route('webhooks.pixelforge'), [], [], [], $signed['headers'], $signed['raw'])
            ->assertNoContent();

        $generation->refresh();
        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('gen_early_provider_789', $generation->provider_generation_id);
        $this->assertSame('generation_failed', $generation->error_code);
        $this->assertSame('Model failed intentionally.', $generation->error_message);
    }
}
