<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get dashboard metrics and statistics for the given user.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toFormattedDateString() ?? '',
            ],
            'activeTokensCount' => $this->userRepository->countActiveTokens($user),
            'totalUsers' => $this->userRepository->count(),
            'authDriver' => 'passport',
        ];
    }
}
