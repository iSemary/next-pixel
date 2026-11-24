<?php

namespace App\Services\Webhooks;

use App\Models\WebhookEvent;

interface WebhookProcessorServiceInterface
{
    /**
     * Process a stored webhook event and update its associated generation monotonically.
     *
     * @return 'processed'|'ignored'|'generation_not_found'
     */
    public function process(WebhookEvent $webhookEvent): string;
}
