<?php

namespace App\Http\Controllers;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
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

        $supportedSizes = [
            [
                'id' => ImageSize::Square->value,
                'value' => ImageSize::Square->value,
                'label' => '1024 × 1024',
                'aspectRatio' => '1:1',
                'name' => 'Square',
            ],
            [
                'id' => ImageSize::Landscape->value,
                'value' => ImageSize::Landscape->value,
                'label' => '1536 × 1024',
                'aspectRatio' => '3:2',
                'name' => 'Landscape',
            ],
            [
                'id' => ImageSize::Portrait->value,
                'value' => ImageSize::Portrait->value,
                'label' => '1024 × 1536',
                'aspectRatio' => '2:3',
                'name' => 'Portrait',
            ],
        ];

        $supportedQualities = [
            [
                'id' => ImageQuality::Low->value,
                'value' => ImageQuality::Low->value,
                'name' => 'Low',
                'description' => 'Fast drafts with lowest token usage',
            ],
            [
                'id' => ImageQuality::Medium->value,
                'value' => ImageQuality::Medium->value,
                'name' => 'Medium',
                'description' => 'Balanced quality and cost',
            ],
            [
                'id' => ImageQuality::High->value,
                'value' => ImageQuality::High->value,
                'name' => 'High',
                'description' => 'Maximum photorealistic detail',
            ],
        ];

        // Testing keywords are only provided in non-production environments
        $testingKeywords = ! app()->isProduction() ? [
            '[fail]',
            '[slow]',
            '[duplicate]',
            '[blocked]',
            '[overloaded]',
            '[succeed]',
        ] : [];

        return Inertia::render('Dashboard', [
            'metrics' => $this->dashboardService->getMetrics($user),
            'supportedSizes' => $supportedSizes,
            'supportedQualities' => $supportedQualities,
            'testingKeywords' => $testingKeywords,
        ]);
    }
}
