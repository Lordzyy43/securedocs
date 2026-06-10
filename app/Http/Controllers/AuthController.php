<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $remember = $request->boolean('remember');
        $email = $request->string('email')->toString();
        $password = $request->string('password')->toString();

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $auditLogger->record(
                request: $request,
                activity: 'failed_login',
                description: 'Login failed.',
                metadata: ['email' => $email],
                status: 'failure',
            );

            return response()->json([
                'message' => __('auth.failed'),
                'errors' => [
                    'email' => [__('auth.failed')],
                ],
            ], 422);
        }

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

        Auth::login($user, $remember);

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

    public function updateProfile(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        $auditLogger->record(
            request: $request,
            activity: 'profile_update',
            description: 'User updated profile information.',
            user: $user,
        );

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $user->load('role:id,name'),
        ]);
    }

    public function changePassword(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Password saat ini salah.',
                'errors' => [
                    'current_password' => ['Password saat ini salah.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $auditLogger->record(
            request: $request,
            activity: 'password_change',
            description: 'User changed password.',
            user: $user,
        );

        return response()->json([
            'message' => 'Password berhasil diubah.',
        ]);
    }
}

