<?php

namespace App\Http\Controllers;

use App\Http\Resources\GenerationResource;
use App\Models\User;
use App\Services\Generations\GenerationQueryServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    public function __construct(
        protected GenerationQueryServiceInterface $generationQuery,
    ) {}

    /**
     * Display the library page with filters, summary stats, and paginated generations.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $filters = [
            'status' => $request->string('status')->toString() ?: 'all',
            'quality' => $request->string('quality')->toString() ?: 'all',
            'size' => $request->string('size')->toString() ?: 'all',
            'search' => $request->string('search')->toString(),
        ];

        $paginator = $this->generationQuery->getPaginated($user, $filters, 12);

        return Inertia::render('Library', [
            'generations' => $paginator->through(
                fn ($generation) => (new GenerationResource($generation))->resolve($request)
            ),
            'filters' => $filters,
            'summary' => $this->generationQuery->getSummaryStats($user),
        ]);
    }
}
