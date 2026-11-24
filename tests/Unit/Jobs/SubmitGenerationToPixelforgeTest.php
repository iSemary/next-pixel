<?php

namespace Tests\Unit\Jobs;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Jobs\SubmitGenerationToPixelforge;
use App\Models\Generation;
use App\Models\User;
use App\Services\Pixelforge\Exceptions\PixelforgeApiException;
use App\Services\Pixelforge\PixelforgeClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SubmitGenerationToPixelforgeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_job_submits_to_pixelforge_and_updates_status_to_processing(): void
    {
        $generation = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'idemp-test-1234',
            'prompt' => 'A futuristic cyberpunk cityscape at night',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Medium,
            'status' => GenerationStatus::Queued,
        ]);

        $mockClient = Mockery::mock(PixelforgeClientInterface::class);
        $mockClient->shouldReceive('createGeneration')
            ->once()
            ->withArgs(function (array $payload, ?string $key) use ($generation) {
                return $payload['prompt'] === $generation->prompt
                    && $payload['size'] === '1024x1024'
                    && $payload['quality'] === 'medium'
                    && $payload['metadata']['nextpixel_generation_id'] === (string) $generation->id
                    && $key === 'idemp-test-1234';
            })
            ->andReturn([
                'id' => 'gen_01HXYZ999',
                'status' => 'queued',
            ]);

        $job = new SubmitGenerationToPixelforge($generation->id);
        $job->handle($mockClient);

        $generation->refresh();

        $this->assertSame(GenerationStatus::Processing, $generation->status);
        $this->assertSame('gen_01HXYZ999', $generation->provider_generation_id);
        $this->assertSame('queued', $generation->provider_status);
        $this->assertNotNull($generation->submitted_at);
    }

    public function test_job_releases_on_429_concurrency_limit(): void
    {
        $generation = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'idemp-rate-1234',
            'prompt' => 'A forest path in autumn',
            'size' => ImageSize::Landscape,
            'quality' => ImageQuality::High,
            'status' => GenerationStatus::Queued,
        ]);

        $mockClient = Mockery::mock(PixelforgeClientInterface::class);
        $mockClient->shouldReceive('createGeneration')
            ->once()
            ->andThrow(new PixelforgeApiException(
                message: 'Concurrency limit reached.',
                errorCode: 'concurrency_limit_exceeded',
                statusCode: 429,
                errorType: 'rate_limit_error',
                retryAfter: 5,
            ));

        $job = Mockery::mock(SubmitGenerationToPixelforge::class, [$generation->id])->makePartial();
        $job->shouldReceive('release')
            ->once()
            ->withArgs(fn (int $delay) => $delay >= 5 && $delay <= 8);

        $job->handle($mockClient);

        $generation->refresh();
        $this->assertSame(GenerationStatus::Queued, $generation->status);
        $this->assertNull($generation->provider_generation_id);
    }

    public function test_job_marks_generation_failed_on_content_policy_violation(): void
    {
        $generation = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'idemp-blocked-1234',
            'prompt' => '[blocked] forbidden content',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Low,
            'status' => GenerationStatus::Queued,
        ]);

        $mockClient = Mockery::mock(PixelforgeClientInterface::class);
        $mockClient->shouldReceive('createGeneration')
            ->once()
            ->andThrow(new PixelforgeApiException(
                message: 'Content policy violation.',
                errorCode: 'content_policy_violation',
                statusCode: 400,
                errorType: 'invalid_request_error',
            ));

        $job = new SubmitGenerationToPixelforge($generation->id);
        $job->handle($mockClient);

        $generation->refresh();

        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('content_policy_violation', $generation->error_code);
        $this->assertSame('Content policy violation.', $generation->error_message);
        $this->assertNotNull($generation->completed_at);
    }

    public function test_job_does_nothing_if_generation_already_has_provider_id(): void
    {
        $generation = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'idemp-already-1234',
            'provider_generation_id' => 'gen_already_submitted',
            'prompt' => 'An already submitted prompt',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Medium,
            'status' => GenerationStatus::Processing,
        ]);

        $mockClient = Mockery::mock(PixelforgeClientInterface::class);
        $mockClient->shouldNotReceive('createGeneration');

        $job = new SubmitGenerationToPixelforge($generation->id);
        $job->handle($mockClient);

        $generation->refresh();
        $this->assertSame('gen_already_submitted', $generation->provider_generation_id);
    }

    public function test_job_marks_generation_failed_immediately_on_429_daily_limit_exceeded(): void
    {
        $generation = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'idemp-daily-limit-1234',
            'prompt' => 'Quota test prompt',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Medium,
            'status' => GenerationStatus::Queued,
        ]);

        $mockClient = Mockery::mock(PixelforgeClientInterface::class);
        $mockClient->shouldReceive('createGeneration')
            ->once()
            ->andThrow(new PixelforgeApiException(
                message: 'Daily generation limit reached.',
                errorCode: 'daily_limit_exceeded',
                statusCode: 429,
                errorType: 'rate_limit_error',
                retryAfter: 3600,
            ));

        $job = Mockery::mock(SubmitGenerationToPixelforge::class, [$generation->id])->makePartial();
        $job->shouldNotReceive('release');

        $job->handle($mockClient);

        $generation->refresh();
        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('daily_limit_exceeded', $generation->error_code);
        $this->assertNotNull($generation->completed_at);
    }

    public function test_job_marks_generation_failed_immediately_on_409_idempotency_key_reused(): void
    {
        $generation = Generation::create([
            'user_id' => $this->user->id,
            'idempotency_key' => 'idemp-conflict-1234',
            'prompt' => 'Conflict prompt',
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Low,
            'status' => GenerationStatus::Queued,
        ]);

        $mockClient = Mockery::mock(PixelforgeClientInterface::class);
        $mockClient->shouldReceive('createGeneration')
            ->once()
            ->andThrow(new PixelforgeApiException(
                message: 'Idempotency key reused with different parameters.',
                errorCode: 'idempotency_key_reused',
                statusCode: 409,
                errorType: 'invalid_request_error',
            ));

        $job = Mockery::mock(SubmitGenerationToPixelforge::class, [$generation->id])->makePartial();
        $job->shouldNotReceive('release');

        $job->handle($mockClient);

        $generation->refresh();
        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('idempotency_key_reused', $generation->error_code);
    }

    public function test_retry_until_returns_date_time_interface_for_immutable_dates(): void
    {
        $job = new SubmitGenerationToPixelforge(1);

        $until = $job->retryUntil();

        $this->assertInstanceOf(\DateTimeInterface::class, $until);
        $this->assertTrue($until->getTimestamp() > time());
    }
}
