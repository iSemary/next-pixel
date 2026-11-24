<?php

namespace Database\Factories;

use App\Enums\WebhookProcessingStatus;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $generationId = 'gen_'.(string) Str::ulid();

        return [
            'event_id' => 'evt_'.(string) Str::ulid(),
            'provider_generation_id' => $generationId,
            'event_type' => 'generation.succeeded',
            'payload' => [
                'id' => 'evt_'.(string) Str::ulid(),
                'object' => 'event',
                'type' => 'generation.succeeded',
                'data' => [
                    'object' => [
                        'id' => $generationId,
                        'object' => 'generation',
                        'status' => 'succeeded',
                    ],
                ],
            ],
            'status' => WebhookProcessingStatus::Pending,
            'delivery_attempt' => 1,
            'processing_error' => null,
            'received_at' => now(),
            'processed_at' => null,
        ];
    }

    /**
     * Indicate that the event has been processed.
     */
    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookProcessingStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that processing the event failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookProcessingStatus::Failed,
            'processed_at' => now(),
            'processing_error' => 'Simulated webhook processing exception',
        ]);
    }
}
