<?php

namespace App\Services\Webhooks;

use App\Enums\WebhookProcessingStatus;
use App\Jobs\ProcessPixelforgeWebhookEvent;
use App\Models\WebhookEvent;
use App\Support\DatabaseExceptionInspector;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookIngestionService implements WebhookIngestionServiceInterface
{
    /**
     * Supported lifecycle event types that trigger asynchronous generation updates.
     *
     * @var list<string>
     */
    protected const LIFECYCLE_EVENTS = [
        'generation.processing',
        'generation.succeeded',
        'generation.failed',
    ];

    /**
     * Ingest, deduplicate, persist, and dispatch processing for an authenticated webhook payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     event: WebhookEvent,
     *     is_duplicate: bool
     * }
     */
    public function ingest(array $payload, int $deliveryAttempt = 1): array
    {
        $eventId = (string) ($payload['id'] ?? '');
        $eventType = (string) ($payload['type'] ?? '');
        $providerGenerationId = $this->extractProviderGenerationId($payload);

        $existing = WebhookEvent::where('event_id', $eventId)->first();
        if ($existing) {
            Log::info("Duplicate Pixelforge webhook [{$eventId}] ({$eventType}) attempt #{$deliveryAttempt} ignored.");

            return [
                'event' => $existing,
                'is_duplicate' => true,
            ];
        }

        $isLifecycleEvent = in_array($eventType, self::LIFECYCLE_EVENTS, true);
        $initialStatus = match (true) {
            $eventType === 'ping' => WebhookProcessingStatus::Processed,
            $isLifecycleEvent => WebhookProcessingStatus::Pending,
            default => WebhookProcessingStatus::Ignored,
        };

        try {
            $webhookEvent = DB::transaction(fn (): WebhookEvent => WebhookEvent::create([
                'event_id' => $eventId,
                'provider_generation_id' => $providerGenerationId,
                'event_type' => $eventType,
                'payload' => $payload,
                'status' => $initialStatus,
                'delivery_attempt' => max(1, $deliveryAttempt),
                'received_at' => now(),
                'processed_at' => $isLifecycleEvent ? null : now(),
                'processing_error' => $initialStatus === WebhookProcessingStatus::Ignored
                    ? "Unsupported webhook event type [{$eventType}]."
                    : null,
            ]));
        } catch (QueryException $e) {
            if (! DatabaseExceptionInspector::isUniqueConstraintViolation($e)) {
                throw $e;
            }

            $duplicate = WebhookEvent::where('event_id', $eventId)->firstOrFail();

            return [
                'event' => $duplicate,
                'is_duplicate' => true,
            ];
        }

        if ($isLifecycleEvent) {
            ProcessPixelforgeWebhookEvent::dispatch($webhookEvent->id);
        }

        return [
            'event' => $webhookEvent,
            'is_duplicate' => false,
        ];
    }

    /**
     * Extract provider generation ID from the webhook data object when present.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function extractProviderGenerationId(array $payload): ?string
    {
        $object = $payload['data']['object'] ?? null;

        if (! is_array($object) || ($object['object'] ?? null) !== 'generation') {
            return null;
        }

        $id = $object['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }
}
