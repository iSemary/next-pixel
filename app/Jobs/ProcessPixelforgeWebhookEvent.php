<?php

namespace App\Jobs;

use App\Enums\WebhookProcessingStatus;
use App\Models\WebhookEvent;
use App\Services\Webhooks\WebhookProcessorServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPixelforgeWebhookEvent implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $uniqueFor = 120;

    public function __construct(
        public int $webhookEventId,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->webhookEventId;
    }

    /**
     * Execute the job to process a verified Pixelforge webhook event.
     */
    public function handle(WebhookProcessorServiceInterface $processor): void
    {
        $webhookEvent = WebhookEvent::find($this->webhookEventId);

        if (! $webhookEvent) {
            Log::warning("ProcessPixelforgeWebhookEvent: WebhookEvent #{$this->webhookEventId} not found.");

            return;
        }

        $outcome = $processor->process($webhookEvent);

        if ($outcome === 'generation_not_found') {
            if ($this->attempts() < 3) {
                Log::warning("Generation not yet visible for webhook [{$webhookEvent->event_id}]; releasing briefly.");
                $this->release(3);

                return;
            }

            $webhookEvent->update([
                'status' => WebhookProcessingStatus::Ignored,
                'processing_error' => 'No matching local generation record found for provider generation ID.',
                'processed_at' => now(),
            ]);
        }
    }

    /**
     * Record processing failures if all retry attempts are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $webhookEvent = WebhookEvent::find($this->webhookEventId);

        if ($webhookEvent && $webhookEvent->status === WebhookProcessingStatus::Pending) {
            $webhookEvent->update([
                'status' => WebhookProcessingStatus::Failed,
                'processing_error' => $exception?->getMessage() ?? 'Webhook processing failed.',
                'processed_at' => now(),
            ]);
        }
    }
}
