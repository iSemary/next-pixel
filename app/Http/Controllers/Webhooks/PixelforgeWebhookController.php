<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Webhooks\WebhookIngestionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PixelforgeWebhookController extends Controller
{
    public function __construct(
        protected WebhookIngestionServiceInterface $ingestionService,
    ) {}

    /**
     * Receive and acknowledge verified Pixelforge webhook events quickly.
     */
    public function __invoke(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload) || empty($payload['id']) || empty($payload['type'])) {
            return response('Invalid webhook payload.', 400);
        }

        $attemptHeader = $request->header('Pixelforge-Delivery-Attempt');
        $deliveryAttempt = is_numeric($attemptHeader) ? (int) $attemptHeader : 1;

        $this->ingestionService->ingest($payload, $deliveryAttempt);

        return response()->noContent();
    }
}
