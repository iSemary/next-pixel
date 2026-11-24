<?php

namespace Tests\Feature\Models;

use App\Enums\WebhookProcessingStatus;
use App\Models\Generation;
use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_event_can_be_created_with_factory(): void
    {
        $event = WebhookEvent::factory()->create([
            'event_id' => 'evt_01JC2Z9B7K2M4P6R8T0V3X5Z7A',
            'event_type' => 'generation.succeeded',
        ]);

        $this->assertDatabaseHas('webhook_events', [
            'id' => $event->id,
            'event_id' => 'evt_01JC2Z9B7K2M4P6R8T0V3X5Z7A',
            'event_type' => 'generation.succeeded',
        ]);
        $this->assertNotNull($event->id);
    }

    public function test_webhook_event_casts_payload_and_status_enum(): void
    {
        $event = WebhookEvent::factory()->create([
            'status' => WebhookProcessingStatus::Pending,
            'payload' => [
                'id' => 'evt_test_1',
                'type' => 'generation.succeeded',
                'data' => ['image_url' => 'https://example.com/test.png'],
            ],
        ]);

        $fresh = $event->fresh();

        $this->assertSame(WebhookProcessingStatus::Pending, $fresh->status);
        $this->assertIsArray($fresh->payload);
        $this->assertSame('generation.succeeded', $fresh->payload['type']);
    }

    public function test_unique_event_id_prevents_duplicate_webhook_events(): void
    {
        WebhookEvent::factory()->create([
            'event_id' => 'evt_duplicate_test_123',
        ]);

        $this->expectException(QueryException::class);

        WebhookEvent::factory()->create([
            'event_id' => 'evt_duplicate_test_123',
        ]);
    }

    public function test_webhook_event_associates_with_generation_via_provider_id(): void
    {
        $providerGenId = 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K';

        $generation = Generation::factory()->create([
            'provider_generation_id' => $providerGenId,
        ]);

        $event = WebhookEvent::factory()->create([
            'provider_generation_id' => $providerGenId,
        ]);

        $this->assertInstanceOf(Generation::class, $event->generation);
        $this->assertSame($generation->id, $event->generation->id);

        $this->assertTrue($generation->webhookEvents->contains($event));
    }

    public function test_webhook_event_can_be_stored_before_generation_exists(): void
    {
        $unlinkedGenId = 'gen_future_arrival_999';

        $event = WebhookEvent::factory()->create([
            'provider_generation_id' => $unlinkedGenId,
        ]);

        $fresh = $event->fresh();

        $this->assertNotNull($fresh);
        $this->assertNull($fresh->generation);
        $this->assertSame($unlinkedGenId, $fresh->provider_generation_id);
    }

    public function test_query_scopes_filter_by_status(): void
    {
        $pending = WebhookEvent::factory()->create();
        $processed = WebhookEvent::factory()->processed()->create();
        $failed = WebhookEvent::factory()->failed()->create();

        $this->assertCount(1, WebhookEvent::pending()->get());
        $this->assertTrue(WebhookEvent::pending()->first()->is($pending));

        $this->assertCount(1, WebhookEvent::processed()->get());
        $this->assertTrue(WebhookEvent::processed()->first()->is($processed));

        $this->assertCount(1, WebhookEvent::failed()->get());
        $this->assertTrue(WebhookEvent::failed()->first()->is($failed));
    }
}
