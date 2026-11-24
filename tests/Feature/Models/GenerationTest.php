<?php

namespace Tests\Feature\Models;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\Generation;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_can_be_created_with_factory(): void
    {
        $generation = Generation::factory()->create([
            'prompt' => 'A futuristic cityscape at sunset, vaporwave style',
        ]);

        $this->assertDatabaseHas('generations', [
            'id' => $generation->id,
            'prompt' => 'A futuristic cityscape at sunset, vaporwave style',
        ]);
        $this->assertNotNull($generation->id);
        $this->assertNotNull($generation->idempotency_key);
    }

    public function test_generation_belongs_to_user_and_user_has_many_generations(): void
    {
        $user = User::factory()->create();
        $generation = Generation::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $generation->user);
        $this->assertSame($user->id, $generation->user->id);

        $this->assertTrue($user->generations->contains($generation));
        $this->assertCount(1, $user->generations);
    }

    public function test_generation_casts_enums_and_json_attributes_correctly(): void
    {
        $generation = Generation::factory()->create([
            'status' => GenerationStatus::Processing,
            'size' => ImageSize::Landscape,
            'quality' => ImageQuality::High,
            'reference_images' => ['https://example.com/ref1.png', 'https://example.com/ref2.png'],
            'metadata' => ['batch_id' => 'batch_999'],
            'submitted_at' => now(),
        ]);

        $fresh = $generation->fresh();

        $this->assertSame(GenerationStatus::Processing, $fresh->status);
        $this->assertSame(ImageSize::Landscape, $fresh->size);
        $this->assertSame(ImageQuality::High, $fresh->quality);
        $this->assertIsArray($fresh->reference_images);
        $this->assertCount(2, $fresh->reference_images);
        $this->assertSame(['batch_id' => 'batch_999'], $fresh->metadata);
        $this->assertInstanceOf(CarbonInterface::class, $fresh->submitted_at);
    }

    public function test_monetary_precision_preserves_nine_decimal_places_without_float_drift(): void
    {
        // 9-decimal nanodollar values matching Pixelforge rates and 25% user markup
        $providerCost = '0.046125000';
        $userCost = '0.057656250'; // 0.046125000 * 1.25

        $generation = Generation::factory()->succeeded()->create([
            'actual_provider_cost' => $providerCost,
            'actual_user_cost' => $userCost,
            'applied_markup_rate' => '0.2500',
        ]);

        $fresh = $generation->fresh();

        // Must strictly be identical strings with all 9 decimal places intact
        $this->assertSame($providerCost, $fresh->actual_provider_cost);
        $this->assertIsString($fresh->actual_provider_cost);

        $this->assertSame($userCost, $fresh->actual_user_cost);
        $this->assertIsString($fresh->actual_user_cost);

        $this->assertSame('0.2500', $fresh->applied_markup_rate);
    }

    public function test_costs_are_distinguishably_null_before_completion(): void
    {
        $queued = Generation::factory()->create();

        $fresh = $queued->fresh();

        $this->assertNull($fresh->actual_provider_cost);
        $this->assertNull($fresh->actual_user_cost);
        $this->assertNull($fresh->completed_at);
        $this->assertFalse($fresh->isCompleted());
    }

    public function test_failed_generations_record_explicit_zero_charges(): void
    {
        $failed = Generation::factory()->failed()->create();

        $fresh = $failed->fresh();

        $this->assertSame(GenerationStatus::Failed, $fresh->status);
        $this->assertSame('0.000000000', $fresh->actual_provider_cost);
        $this->assertSame('0.000000000', $fresh->actual_user_cost);
        $this->assertSame('internal_error', $fresh->error_code);
        $this->assertTrue($fresh->isCompleted());
        $this->assertTrue($fresh->isFailed());
    }

    public function test_unique_provider_generation_id_is_enforced(): void
    {
        Generation::factory()->create([
            'provider_generation_id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K',
        ]);

        $this->expectException(QueryException::class);

        Generation::factory()->create([
            'provider_generation_id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K',
        ]);
    }

    public function test_unique_idempotency_key_is_enforced(): void
    {
        Generation::factory()->create([
            'idempotency_key' => 'idem_unique_key_123',
        ]);

        $this->expectException(QueryException::class);

        Generation::factory()->create([
            'idempotency_key' => 'idem_unique_key_123',
        ]);
    }

    public function test_deleting_user_preserves_generation_record_with_null_user_id(): void
    {
        $user = User::factory()->create();
        $generation = Generation::factory()->succeeded()->create([
            'user_id' => $user->id,
            'actual_provider_cost' => '0.046125000',
            'actual_user_cost' => '0.057656250',
            'actual_total_tokens' => 10411,
        ]);

        // User deletes account
        $user->delete();

        // Verify generation remains in database for provider cost reconciliation
        $fresh = $generation->fresh();
        $this->assertNotNull($fresh);
        $this->assertNull($fresh->user_id);
        $this->assertSame('0.046125000', $fresh->actual_provider_cost);
        $this->assertSame('0.057656250', $fresh->actual_user_cost);
        $this->assertSame(10411, $fresh->actual_total_tokens);
    }

    public function test_query_scopes_filter_by_status_and_settlement(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $queued = Generation::factory()->create(['user_id' => $user->id]);
        $processing = Generation::factory()->processing()->create(['user_id' => $user->id]);
        $succeeded = Generation::factory()->succeeded()->settled()->create(['user_id' => $user->id]);
        $failed = Generation::factory()->failed()->create(['user_id' => $user->id]);
        $otherGen = Generation::factory()->failed()->create(['user_id' => $otherUser->id]);

        $this->assertCount(1, Generation::forUser($user)->queued()->get());
        $this->assertTrue(Generation::forUser($user)->queued()->first()->is($queued));

        $this->assertCount(1, Generation::processing()->get());
        $this->assertTrue(Generation::processing()->first()->is($processing));

        $this->assertCount(1, Generation::succeeded()->get());
        $this->assertTrue(Generation::succeeded()->first()->is($succeeded));

        $this->assertCount(2, Generation::failed()->get());
        $this->assertCount(1, Generation::forUser($user)->failed()->get());
        $this->assertTrue(Generation::failed()->first()->is($failed));

        $this->assertCount(1, Generation::settled()->get());
        $this->assertTrue(Generation::settled()->first()->is($succeeded));

        $this->assertCount(4, Generation::unsettled()->get());

        $this->assertCount(4, Generation::forUser($user)->get());
        $this->assertCount(1, Generation::forUser($otherUser)->get());
    }
}
