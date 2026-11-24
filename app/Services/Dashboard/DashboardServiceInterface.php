<?php

namespace App\Services\Dashboard;

use App\Models\User;

interface DashboardServiceInterface
{
    /**
     * Get dashboard metrics and statistics for the given user.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(User $user): array;
}
