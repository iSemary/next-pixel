<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Generations\EstimateGenerationRequest;
use App\Services\Pricing\Exceptions\InvalidProviderPricingException;
use App\Services\Pricing\Exceptions\ProviderPricingUnavailableException;
use App\Services\Pricing\PricingEstimationServiceInterface;
use Illuminate\Http\JsonResponse;

class GenerationEstimateController extends Controller
{
    public function __construct(
        protected PricingEstimationServiceInterface $pricingService
    ) {}

    /**
     * Calculate estimated token usage and pricing for an image generation.
     */
    public function estimate(EstimateGenerationRequest $request): JsonResponse
    {
        try {
            $estimate = $this->pricingService->estimate($request->toEstimationInput());

            return response()->json($estimate->toArray(), 200);
        } catch (ProviderPricingUnavailableException $e) {
            return response()->json([
                'message' => 'Pricing estimation service is temporarily unavailable. Please try again shortly.',
                'error' => $e->getMessage(),
            ], 503);
        } catch (InvalidProviderPricingException $e) {
            return response()->json([
                'message' => 'Invalid pricing rates received from provider.',
                'error' => $e->getMessage(),
            ], 502);
        }
    }
}
