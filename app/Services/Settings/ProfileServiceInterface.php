<?php

namespace App\Services\Settings;

use App\Models\User;

interface ProfileServiceInterface
{
    /**
     * Update user profile information.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): bool;

    /**
     * Update user password.
     */
    public function updatePassword(User $user, string $newPassword): bool;

    /**
     * Permanently delete user account, invalidate session, and revoke API tokens.
     */
    public function deleteAccount(User $user): void;
}
