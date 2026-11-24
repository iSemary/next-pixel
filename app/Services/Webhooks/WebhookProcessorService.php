<?php

namespace App\Services\Webhooks;

use App\Enums\WebhookProcessingStatus;
use App\Models\Generation;
use App\Models\WebhookEvent;
use App\Services\Generations\GenerationLifecycleServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookProcessorService implements WebhookProcessorServiceInterface
{
    public function __construct(
        protected GenerationLifecycleServiceInterface $lifecycleService,
    ) {}

    /**
     * Process a stored webhook event and update its associated generation monotonically.
     *
     * @return 'processed'|'ignored'|'generation_not_found'
     */
    public function process(WebhookEvent $webhookEvent): string
    {
        if ($webhookEvent->status === WebhookProcessingStatus::Processed) {
            return 'processed';
        }

        $payload = $webhookEvent->payload;
        $object = is_array($payload['data']['object'] ?? null) ? $payload['data']['object'] : [];
        $eventType = $webhookEvent->event_type;

        if ($eventType === 'ping') {
            $webhookEvent->update([
                'status' => WebhookProcessingStatus::Processed,
                'processed_at' => now(),
            ]);

            return 'processed';
        }

        return DB::transaction(function () use ($webhookEvent, $eventType, $object): string {
            $generation = $this->findLockedGeneration($webhookEvent, $object);

            if (! $generation) {
                return 'generation_not_found';
            }

            $providerId = is_string($object['id'] ?? null) ? $object['id'] : null;
            if ($webhookEvent->provider_generation_id === null && $providerId !== null && $providerId !== '') {
                $webhookEvent->provider_generation_id = $providerId;
            }

            $applied = $this->lifecycleService->applyUpstreamSnapshot($generation, $object, $eventType);

            if (! $applied) {
                $webhookEvent->update([
                    'status' => WebhookProcessingStatus::Ignored,
                    'processing_error' => "Ignored stale or out-of-order [{$eventType}] event; local generation #{$generation->id} is in status [{$generation->status->value}].",
                    'processed_at' => now(),
                ]);

                Log::info("Ignored stale webhook [{$webhookEvent->event_id}] ({$eventType}) for Generation #{$generation->id} in status [{$generation->status->value}].");

                return 'ignored';
            }

            $webhookEvent->update([
                'status' => WebhookProcessingStatus::Processed,
                'processing_error' => null,
                'processed_at' => now(),
            ]);

            Log::info("Processed webhook [{$webhookEvent->event_id}] ({$eventType}) for Generation #{$generation->id} -> status [{$generation->status->value}].");

            return 'processed';
        });
    }

    /**
     * Find and lock the target generation by provider_generation_id or metadata correlation fallback.
     *
     * @param  array<string, mixed>  $object
     */
    protected function findLockedGeneration(WebhookEvent $webhookEvent, array $object): ?Generation
    {
        $providerId = $webhookEvent->provider_generation_id
            ?? (is_string($object['id'] ?? null) ? $object['id'] : null);

        if ($providerId !== null && $providerId !== '') {
            $byProvider = Generation::where('provider_generation_id', $providerId)
                ->lockForUpdate()
                ->first();

            if ($byProvider) {
                return $byProvider;
            }
        }

        $localId = $object['metadata']['nextpixel_generation_id'] ?? null;

        if ($localId !== null && is_numeric((string) $localId)) {
            return Generation::where('id', (int) $localId)
                ->lockForUpdate()
                ->first();
        }

        return null;
    }
}
