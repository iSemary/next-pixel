<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Generations\EstimateGenerationRequest;
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
        $estimate = $this->pricingService->estimate($request->toEstimationInput());

        return response()->json($estimate->toArray(), 200);
    }
}
