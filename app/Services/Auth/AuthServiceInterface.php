<?php

namespace App\Services\Auth;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Register a new user account.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): User;

    /**
     * Attempt to authenticate with user credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function attempt(array $credentials, bool $remember = false): bool;

    /**
     * Create and return a Passport personal access token for a user.
     */
    public function issuePassportToken(User $user, string $tokenName = 'authToken'): string;

    /**
     * Revoke all active or current Passport tokens for a user.
     */
    public function revokePassportToken(User $user): void;

    /**
     * Handle API user registration and issue access token.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string, token_type: string}
     */
    public function registerApi(array $data): array;

    /**
     * Handle API user login and issue access token if credentials are valid.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{user: User, token: string, token_type: string}|null
     */
    public function loginApi(array $credentials): ?array;

    /**
     * Revoke API tokens for user upon logout.
     */
    public function logoutApi(User $user): void;

    /**
     * Handle Web user login and session regeneration.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function loginWeb(array $credentials, bool $remember = false): bool;

    /**
     * Handle Web user registration, login user, and regenerate session.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerAndLoginWeb(array $data): User;

    /**
     * Log out from the web session.
     */
    public function logoutWeb(): void;
}
