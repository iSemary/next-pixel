<?php

namespace App\Services\Auth;

interface PasswordResetServiceInterface
{
    /**
     * Send password reset link to user email.
     */
    public function sendResetLink(string $email): string;

    /**
     * Reset the user password with token and credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function resetPassword(array $credentials): string;

    /**
     * Determine if a status code indicates a successful reset link sent.
     */
    public function isResetLinkSent(string $status): bool;

    /**
     * Determine if a status code indicates a successful password reset.
     */
    public function isPasswordReset(string $status): bool;
}
