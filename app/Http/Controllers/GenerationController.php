<?php

namespace App\Http\Controllers;

use App\Http\Requests\Generations\CreateGenerationRequest;
use App\Http\Resources\GenerationResource;
use App\Models\Generation;
use App\Models\User;
use App\Services\Generations\GenerationQueryServiceInterface;
use App\Services\Generations\GenerationSubmissionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GenerationController extends Controller
{
    public function __construct(
        protected GenerationSubmissionServiceInterface $submissionService,
        protected GenerationQueryServiceInterface $generationQuery,
    ) {}

    /**
     * List or poll user generations as JSON for live studio and library updates.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $idsParam = $request->input('ids');
        if ($idsParam !== null && $idsParam !== '') {
            $rawIds = is_array($idsParam) ? $idsParam : explode(',', (string) $idsParam);
            $ids = array_values(array_unique(array_filter(array_map('intval', $rawIds), fn (int $id) => $id > 0)));

            $generations = $this->generationQuery->getByIdsForPolling($user, array_slice($ids, 0, 50));

            return response()->json([
                'generations' => GenerationResource::collection($generations)->resolve($request),
                'summary' => $this->generationQuery->getSummaryStats($user),
                'credit_balance' => (string) $user->fresh()?->credit_balance,
            ]);
        }

        $filters = [
            'status' => $request->string('status')->toString() ?: 'all',
            'quality' => $request->string('quality')->toString() ?: 'all',
            'size' => $request->string('size')->toString() ?: 'all',
            'search' => $request->string('search')->toString(),
        ];

        $paginator = $this->generationQuery->getPaginated(
            $user,
            $filters,
            (int) $request->input('per_page', 12)
        );

        return response()->json([
            'generations' => GenerationResource::collection($paginator->items())->resolve($request),
            'summary' => $this->generationQuery->getSummaryStats($user),
            'credit_balance' => (string) $user->fresh()?->credit_balance,
        ]);
    }

    /**
     * Retrieve a single generation belonging to the authenticated user.
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
            'generation' => (new GenerationResource($generation))->resolve($request),
            'credit_balance' => (string) $request->user()->fresh()?->credit_balance,
        ]);
    }

    /**
     * Submit an asynchronous image generation request from the Web Studio.
     */
    public function store(CreateGenerationRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->submissionService->submit(
            $request->user(),
            $request->toSubmissionData()
        );

        $generations = $result['generations'];
        $isReplay = $result['is_idempotent_replay'];

        if ($request->wantsJson()) {
            $response = response()->json([
                'generations' => GenerationResource::collection($generations)->resolve($request),
                'is_idempotent_replay' => $isReplay,
                'credit_balance' => $result['credit_balance'] ?? (string) $request->user()->credit_balance,
                'message' => 'Generation submitted successfully.',
            ], $isReplay ? 200 : 202);

            if ($isReplay) {
                $response->header('Idempotent-Replayed', 'true');
            }

            return $response;
        }

        return redirect()->back()->with('success', 'Generation submitted successfully.');
    }
}
