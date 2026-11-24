<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reports\AdminReportRequest;
use App\Services\Reports\AdminReportServiceInterface;
use Inertia\Inertia;
use Inertia\Response;

class AdminReportController extends Controller
{
    public function __construct(
        protected AdminReportServiceInterface $adminReportService,
    ) {}

    /**
     * Display the operator Admin Report page with daily & per-user spend, provider cost, and profit.
     */
    public function index(AdminReportRequest $request): Response
    {
        $validated = $request->validated();

        $report = $this->adminReportService->getReport(
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
        );

        return Inertia::render('Reports/AdminReport', $report);
    }
}
