<?php

namespace App\Services\Reports;

interface AdminReportServiceInterface
{
    /**
     * Build the operator financial report across UTC days and users, reconciled against Pixelforge GET /v1/usage.
     *
     * @return array<string, mixed>
     */
    public function getReport(?string $startDate = null, ?string $endDate = null): array;
}
