<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PasswordResetController extends Controller
{
    public function __construct(
        protected PasswordResetServiceInterface $passwordResetService
    ) {}

    /**
     * Display the password reset link request view.
     */
    public function showForgotPassword(): Response
    {
        return Inertia::render('auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): RedirectResponse|JsonResponse
    {
        $status = $this->passwordResetService->sendResetLink($request->validated('email'));

        if ($this->passwordResetService->isResetLinkSent($status)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => __($status),
                ]);
            }

            return back()->with('status', __($status));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => __($status),
                'errors' => [
                    'email' => [__($status)],
                ],
            ], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        return back()->withErrors(['email' => __($status)]);
    }

    /**
     * Display the password reset view.
     */
    public function showResetPassword(Request $request, string $token): Response
    {
        return Inertia::render('auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function resetPassword(ResetPasswordRequest $request): RedirectResponse|JsonResponse
    {
        $status = $this->passwordResetService->resetPassword($request->validated());

        if ($this->passwordResetService->isPasswordReset($status)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => __($status),
                ]);
            }

            return redirect()->route('login')->with('status', __($status));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => __($status),
                'errors' => [
                    'email' => [__($status)],
                ],
            ], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
