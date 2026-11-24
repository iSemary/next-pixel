<?php

namespace App\Services\Settings;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\AuthServiceInterface;

class ProfileService implements ProfileServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected AuthServiceInterface $authService
    ) {}

    /**
     * Update user profile information.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): bool
    {
        return $this->userRepository->update($user, $data);
    }

    /**
     * Update user password.
     */
    public function updatePassword(User $user, string $newPassword): bool
    {
        return $this->userRepository->updatePassword($user, $newPassword);
    }

    /**
     * Permanently delete user account, invalidate session, and revoke API tokens.
     */
    public function deleteAccount(User $user): void
    {
        $this->authService->logoutWeb();
        $this->authService->revokePassportToken($user);
        $this->userRepository->delete($user);
    }
}
