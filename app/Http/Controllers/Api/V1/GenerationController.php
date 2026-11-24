<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Generations\CreateGenerationRequest;
use App\Http\Resources\GenerationResource;
use App\Models\Generation;
use App\Models\User;
use App\Services\Generations\GenerationQueryServiceInterface;
use App\Services\Generations\GenerationSubmissionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenerationController extends Controller
{
    public function __construct(
        protected GenerationSubmissionServiceInterface $submissionService,
        protected GenerationQueryServiceInterface $generationQuery,
    ) {}

    /**
     * List the authenticated user's generations with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $filters = [
            'status' => $request->string('status')->toString() ?: 'all',
            'quality' => $request->string('quality')->toString() ?: 'all',
            'size' => $request->string('size')->toString() ?: 'all',
            'search' => $request->string('search')->toString(),
        ];

        $paginator = $this->generationQuery->getPaginated(
            $user,
            $filters,
            (int) $request->input('per_page', 15)
        );

        return response()->json([
            'data' => GenerationResource::collection($paginator->items())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'summary' => $this->generationQuery->getSummaryStats($user),
        ]);
    }

    /**
     * Retrieve a single generation belonging to the authenticated API user.
     */
    public function show(Request $request, Generation $generation): JsonResponse
    {
        if ($generation->user_id !== $request->user()->id) {
            abort(404);
        }

        if ($request->boolean('sync')) {
            $generation = $this->generationQuery->reconcileFromUpstream($generation, force: true);
        }

        return response()->json([
            'data' => (new GenerationResource($generation))->resolve($request),
        ]);
    }

    /**
     * Submit an asynchronous image generation request.
     */
    public function store(CreateGenerationRequest $request): JsonResponse
    {
        $result = $this->submissionService->submit(
            $request->user(),
            $request->toSubmissionData()
        );

        $generations = $result['generations'];
        $isReplay = $result['is_idempotent_replay'];

        $responseData = $generations->count() === 1
            ? new GenerationResource($generations->first())
            : GenerationResource::collection($generations);

        $response = response()->json([
            'data' => $responseData,
            'is_idempotent_replay' => $isReplay,
            'credit_balance' => $result['credit_balance'] ?? (string) $request->user()->credit_balance,
        ], $isReplay ? 200 : 202);

        if ($isReplay) {
            $response->header('Idempotent-Replayed', 'true');
        }

        return $response;
    }
}
