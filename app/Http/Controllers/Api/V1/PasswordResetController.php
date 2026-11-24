<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetController extends Controller
{
    public function __construct(
        protected PasswordResetServiceInterface $passwordResetService
    ) {}

    /**
     * Handle an incoming password reset link request.
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->passwordResetService->sendResetLink($request->validated('email'));

        if ($this->passwordResetService->isResetLinkSent($status)) {
            return response()->json([
                'status' => __($status),
            ]);
        }

        return response()->json([
            'message' => __($status),
            'errors' => [
                'email' => [__($status)],
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Handle an incoming new password request.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->passwordResetService->resetPassword($request->validated());

        if ($this->passwordResetService->isPasswordReset($status)) {
            return response()->json([
                'status' => __($status),
            ]);
        }

        return response()->json([
            'message' => __($status),
            'errors' => [
                'email' => [__($status)],
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
