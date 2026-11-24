<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Find a user by ID.
     */
    public function findById(int|string $id): ?User;

    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Create a new user with the given attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * Update an existing user's attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): bool;

    /**
     * Delete a user.
     */
    public function delete(User $user): bool;

    /**
     * Update a user's password.
     */
    public function updatePassword(User $user, string $newPassword): bool;

    /**
     * Get the total count of users.
     */
    public function count(): int;

    /**
     * Get the count of active (non-revoked) personal access tokens for the given user.
     */
    public function countActiveTokens(User $user): int;
}
