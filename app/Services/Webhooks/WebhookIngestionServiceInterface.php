<?php

namespace App\Services\Webhooks;

use App\Models\WebhookEvent;

interface WebhookIngestionServiceInterface
{
    /**
     * Ingest, deduplicate, persist, and dispatch processing for an authenticated webhook payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     event: WebhookEvent,
     *     is_duplicate: bool
     * }
     */
    public function ingest(array $payload, int $deliveryAttempt = 1): array;
}
