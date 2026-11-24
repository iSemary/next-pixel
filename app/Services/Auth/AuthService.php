<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Passport\Token;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register a new user account.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): User
    {
        return $this->userRepository->create($data);
    }

    /**
     * Attempt to authenticate with user credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        return Auth::attempt($credentials, $remember);
    }

    /**
     * Create and return a Passport personal access token for a user.
     */
    public function issuePassportToken(User $user, string $tokenName = 'authToken'): string
    {
        return $user->createToken($tokenName)->accessToken;
    }

    /**
     * Revoke all active or current Passport tokens for a user.
     */
    public function revokePassportToken(User $user): void
    {
        $token = $user->token();
        if ($token instanceof Token) {
            $token->revoke();
        } else {
            $user->tokens()->update(['revoked' => true]);
        }
    }

    /**
     * Handle API user registration and issue access token.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string, token_type: string}
     */
    public function registerApi(array $data): array
    {
        $user = $this->register($data);
        $token = $this->issuePassportToken($user);

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Handle API user login and issue access token if credentials are valid.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{user: User, token: string, token_type: string}|null
     */
    public function loginApi(array $credentials): ?array
    {
        if (! $this->attempt($credentials)) {
            return null;
        }

        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user) {
            return null;
        }

        $token = $this->issuePassportToken($user);

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Revoke API tokens for user upon logout.
     */
    public function logoutApi(User $user): void
    {
        $this->revokePassportToken($user);
    }

    /**
     * Handle Web user login and session regeneration.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function loginWeb(array $credentials, bool $remember = false): bool
    {
        if (! $this->attempt($credentials, $remember)) {
            return false;
        }

        if (Session::isStarted()) {
            Session::regenerate();
        }

        return true;
    }

    /**
     * Handle Web user registration, login user, and regenerate session.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerAndLoginWeb(array $data): User
    {
        $user = $this->register($data);

        Auth::guard('web')->login($user);

        if (Session::isStarted()) {
            Session::regenerate();
        }

        return $user;
    }

    /**
     * Log out from the web session.
     */
    public function logoutWeb(): void
    {
        Auth::guard('web')->logout();

        if (Session::isStarted()) {
            Session::invalidate();
            Session::regenerateToken();
        }
    }
}
