<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        protected User $model
    ) {}

    /**
     * Find a user by ID.
     */
    public function findById(int|string $id): ?User
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()
            ->where('email', strtolower(trim($email)))
            ->first();
    }

    /**
     * Create a new user with the given attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        if (isset($data['password']) && ! Hash::isHashed($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        return $this->model->newQuery()->create($data);
    }

    /**
     * Update an existing user's attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): bool
    {
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
            if ($user->email !== $data['email']) {
                $user->email_verified_at = null;
            }
        }

        return $user->update($data);
    }

    /**
     * Delete a user.
     */
    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }

    /**
     * Update a user's password.
     */
    public function updatePassword(User $user, string $newPassword): bool
    {
        return $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Get the total count of users.
     */
    public function count(): int
    {
        return $this->model->newQuery()->count();
    }

    /**
     * Get the count of active (non-revoked) personal access tokens for the given user.
     */
    public function countActiveTokens(User $user): int
    {
        return $user->tokens()->where('revoked', false)->count();
    }
}
