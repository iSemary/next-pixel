<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Dashboard\DashboardServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardServiceInterface $dashboardService
    ) {}

    /**
     * Display the dashboard index page.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'metrics' => $this->dashboardService->getMetrics($user),
        ]);
    }
}
