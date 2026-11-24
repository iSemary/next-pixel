<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Password;

class PasswordResetService implements PasswordResetServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Send password reset link to user email.
     */
    public function sendResetLink(string $email): string
    {
        return Password::broker()->sendResetLink(['email' => $email]);
    }

    /**
     * Reset the user password with token and credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function resetPassword(array $credentials): string
    {
        return Password::broker()->reset(
            $credentials,
            function (User $user, string $password): void {
                $this->userRepository->updatePassword($user, $password);
            }
        );
    }

    /**
     * Determine if a status code indicates a successful reset link sent.
     */
    public function isResetLinkSent(string $status): bool
    {
        return $status === Password::RESET_LINK_SENT;
    }

    /**
     * Determine if a status code indicates a successful password reset.
     */
    public function isPasswordReset(string $status): bool
    {
        return $status === Password::PASSWORD_RESET;
    }
}
