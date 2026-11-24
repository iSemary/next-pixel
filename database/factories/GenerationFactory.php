<?php

namespace Database\Factories;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    protected $model = Generation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_generation_id' => 'gen_'.(string) Str::ulid(),
            'idempotency_key' => (string) Str::uuid(),
            'prompt' => fake()->sentence(8),
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Medium,
            'reference_images' => null,
            'metadata' => ['order_id' => (string) fake()->numberBetween(1000, 9999)],
            'status' => GenerationStatus::Queued,
            'provider_status' => 'queued',
            'image_url' => null,
            'image_url_expires_at' => null,
            'error_code' => null,
            'error_message' => null,
            'submitted_at' => now(),
            'started_at' => null,
            'completed_at' => null,
            'estimated_input_text_tokens' => 12,
            'estimated_input_image_tokens' => 0,
            'estimated_output_tokens' => 1100,
            'estimated_reasoning_tokens_min' => 200,
            'estimated_reasoning_tokens_max' => 2000,
            'estimated_total_tokens_min' => 1312,
            'estimated_total_tokens_max' => 3112,
            'actual_input_text_tokens' => null,
            'actual_input_image_tokens' => null,
            'actual_output_tokens' => null,
            'actual_reasoning_tokens' => null,
            'actual_total_tokens' => null,
            'estimated_provider_cost' => '0.046125000',
            'estimated_user_cost' => '0.057656250',
            'actual_provider_cost' => null,
            'actual_user_cost' => null,
            'applied_markup_rate' => '0.2500',
            'pricing_rate_snapshot' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
            'settled_at' => null,
        ];
    }

    /**
     * Indicate that the generation is currently processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GenerationStatus::Processing,
            'provider_status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the generation succeeded.
     */
    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GenerationStatus::Succeeded,
            'provider_status' => 'succeeded',
            'started_at' => now()->subSeconds(20),
            'completed_at' => now(),
            'image_url' => 'https://pixelforge.promptedit.dev/images/gen_sample.png',
            'image_url_expires_at' => now()->addHours(24),
            'actual_input_text_tokens' => 12,
            'actual_input_image_tokens' => 0,
            'actual_output_tokens' => 1100,
            'actual_reasoning_tokens' => 450,
            'actual_total_tokens' => 1562,
            'actual_provider_cost' => '0.049685000',
            'actual_user_cost' => '0.062106250',
        ]);
    }

    /**
     * Indicate that the generation failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GenerationStatus::Failed,
            'provider_status' => 'failed',
            'started_at' => now()->subSeconds(5),
            'completed_at' => now(),
            'error_code' => 'internal_error',
            'error_message' => 'Something went wrong on our side while generating. You were not charged; please retry.',
            'actual_provider_cost' => '0.000000000',
            'actual_user_cost' => '0.000000000',
        ]);
    }

    /**
     * Indicate that the generation has been financially settled.
     */
    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'settled_at' => now(),
        ]);
    }
}
