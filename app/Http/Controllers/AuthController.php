<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $auditLogger->record(
                request: $request,
                activity: 'failed_login',
                description: 'Login failed.',
                metadata: ['email' => $request->string('email')->toString()],
                status: 'failure',
            );

            return response()->json([
                'message' => __('auth.failed'),
                'errors' => [
                    'email' => [__('auth.failed')],
                ],
            ], 422);
        }

        /** @var User $user */
        $user = $request->user();

        if (! $user->isActive()) {
            $auditLogger->record(
                request: $request,
                activity: 'failed_login',
                description: 'Inactive user attempted to login.',
                metadata: ['email' => $user->email],
                status: 'failure',
                user: $user,
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Your account is inactive.',
            ], 403);
        }

        $request->session()->regenerate();

        $auditLogger->record(
            request: $request,
            activity: 'login',
            description: 'User logged in.',
            user: $user,
        );

        return response()->json([
            'message' => 'Logged in.',
            'user' => $user->load('role:id,name'),
        ]);
    }

    public function logout(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $auditLogger->record(
            request: $request,
            activity: 'logout',
            description: 'User logged out.',
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('role:id,name'));
    }
}
