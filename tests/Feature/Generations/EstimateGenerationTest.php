<?php

namespace Tests\Feature\Generations;

use App\Models\User;
use App\Services\Pixelforge\Exceptions\PixelforgeConnectionException;
use App\Services\Pixelforge\PixelforgeClientInterface;
use App\Services\Pricing\ProviderPricingServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EstimateGenerationTest extends TestCase
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

        // Bind mock client returning standard pricing
        $clientMock = $this->createMock(PixelforgeClientInterface::class);
        $clientMock->method('getPricing')->willReturn($this->standardPricingSnapshot);
        $this->app->instance(PixelforgeClientInterface::class, $clientMock);
    }

    public function test_guest_cannot_estimate_via_web_route(): void
    {
        $response = $this->postJson(route('generations.estimate'), [
            'prompt' => 'A beautiful sunset',
            'size' => '1024x1024',
            'quality' => 'medium',
        ]);

        $response->assertUnauthorized();
    }

    public function test_guest_cannot_estimate_via_api_v1_route(): void
    {
        $response = $this->postJson('/api/v1/generations/estimate', [
            'prompt' => 'A beautiful sunset',
            'size' => '1024x1024',
            'quality' => 'medium',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_calculate_estimate(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('generations.estimate'), [
            'prompt' => str_repeat('A', 400),
            'size' => '1536x1024',
            'quality' => 'high',
            'reference_images' => [
                'https://example.com/ref1.jpg',
                'https://example.com/ref2.jpg',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'currency',
            'total_tokens',
            'estimated_provider_cost',
            'markup_rate',
            'markup_percentage',
            'markup_amount',
            'estimated_user_cost',
            'categories' => [
                'input_text' => ['category', 'label', 'estimated_tokens', 'unit_rate_per_1m', 'estimated_cost_usd'],
                'input_image' => ['category', 'label', 'estimated_tokens', 'unit_rate_per_1m', 'estimated_cost_usd'],
                'output' => ['category', 'label', 'estimated_tokens', 'unit_rate_per_1m', 'estimated_cost_usd'],
                'reasoning' => ['category', 'label', 'estimated_tokens', 'unit_rate_per_1m', 'estimated_cost_usd'],
            ],
            'pricing_snapshot',
            'assumptions',
        ]);

        $data = $response->json();
        $this->assertSame('USD', $data['currency']);
        $this->assertSame('0.2500', $data['markup_rate']);
        $this->assertSame('25%', $data['markup_percentage']);
        // Verify worked example values
        $this->assertSame(16095, $data['total_tokens']);
        $this->assertSame('0.373875000', $data['estimated_provider_cost']);
        $this->assertSame('0.093468750', $data['markup_amount']);
        $this->assertSame('0.467343750', $data['estimated_user_cost']);

        // Crucial requirement: No generations should be written to the database during estimation
        $this->assertDatabaseCount('generations', 0);
    }

    public function test_api_v1_authenticated_endpoint_works(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/v1/generations/estimate', [
            'prompt' => 'Cyberpunk neon city street at night',
            'size' => '1024x1024',
            'quality' => 'low',
        ]);

        $response->assertOk();
        $this->assertSame('USD', $response->json('currency'));
        $this->assertNotEmpty($response->json('estimated_user_cost'));
        $this->assertDatabaseCount('generations', 0);
    }

    public function test_validation_fails_for_empty_prompt(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('generations.estimate'), [
            'prompt' => '',
            'size' => '1024x1024',
            'quality' => 'medium',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    public function test_validation_fails_for_prompt_exceeding_4000_characters(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('generations.estimate'), [
            'prompt' => str_repeat('x', 4001),
            'size' => '1024x1024',
            'quality' => 'medium',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    public function test_validation_fails_for_unsupported_size(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('generations.estimate'), [
            'prompt' => 'Valid prompt',
            'size' => '800x600',
            'quality' => 'medium',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['size']);
    }

    public function test_validation_fails_for_unsupported_quality(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('generations.estimate'), [
            'prompt' => 'Valid prompt',
            'size' => '1024x1024',
            'quality' => 'ultra',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quality']);
    }

    public function test_validation_fails_when_exceeding_four_reference_images(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('generations.estimate'), [
            'prompt' => 'Valid prompt',
            'size' => '1024x1024',
            'quality' => 'medium',
            'reference_images' => [
                'https://example.com/1.png',
                'https://example.com/2.png',
                'https://example.com/3.png',
                'https://example.com/4.png',
                'https://example.com/5.png',
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reference_images']);
    }

    public function test_validation_fails_for_invalid_reference_image_url(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('generations.estimate'), [
            'prompt' => 'Valid prompt',
            'size' => '1024x1024',
            'quality' => 'medium',
            'reference_images' => [
                'not-a-valid-url',
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reference_images.0']);
    }

    public function test_returns_503_when_provider_pricing_is_unavailable(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Clear instance mock and bind failing client
        $failingClient = $this->createMock(PixelforgeClientInterface::class);
        $failingClient->method('getPricing')->willThrowException(new PixelforgeConnectionException('Pixelforge API down'));
        $this->app->instance(PixelforgeClientInterface::class, $failingClient);

        // Clear provider service cache as well
        /** @var ProviderPricingServiceInterface $pricingService */
        $pricingService = $this->app->make(ProviderPricingServiceInterface::class);
        $pricingService->clearCache();

        $response = $this->actingAs($user)->postJson(route('generations.estimate'), [
            'prompt' => 'Valid prompt',
            'size' => '1024x1024',
            'quality' => 'medium',
        ]);

        $response->assertStatus(503);
        $response->assertJsonStructure(['message', 'error']);
    }
}
