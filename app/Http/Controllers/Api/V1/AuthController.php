<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Auth\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $authService
    ) {}

    /**
     * Register a new user and generate a Passport access token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->registerApi($request->userData());

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $result['user'],
            'token' => $result['token'],
            'token_type' => $result['token_type'],
        ], Response::HTTP_CREATED);
    }

    /**
     * Authenticate an existing user and issue a Passport access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->loginApi($request->credentials());

        if (! $result) {
            return response()->json([
                'message' => __('auth.failed'),
                'errors' => [
                    'email' => [__('auth.failed')],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $result['user'],
            'token' => $result['token'],
            'token_type' => $result['token_type'],
        ]);
    }

    /**
     * Return authenticated user profile.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Revoke Passport access token and log out.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $this->authService->logoutApi($user);
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
