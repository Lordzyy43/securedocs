<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get list of active users for dropdown selection (e.g. for sharing documents)
     * Accessible by all authenticated users.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->where('id', '!=', $request->user()->id)
            ->where('status', UserStatus::ACTIVE->value)
            ->whereHas('role', fn ($query) => $query->where('name', UserRole::USER->value))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    /**
     * Get all users with roles and pagination.
     * Restricted to Admins only.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('role:id,name')
            ->orderBy('name')
            ->paginate(15);

        return response()->json($users);
    }

    /**
     * Create a new user from Admin panel.
     * Restricted to Admins only.
     */
    public function adminStore(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'status' => UserStatus::ACTIVE->value,
        ]);

        $auditLogger->record(
            request: $request,
            activity: 'user_management',
            description: "User '{$user->email}' created.",
            metadata: ['created_user_id' => $user->id],
        );

        return response()->json($user->load('role:id,name'), 201);
    }

    /**
     * Update user details from Admin panel.
     * Restricted to Admins only.
     */
    public function adminUpdate(Request $request, User $user, AuditLogger $auditLogger): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        // Prevent admin from changing their own role (avoid self-lockout)
        if ($user->id === $request->user()->id && (int) $validated['role_id'] !== $user->role_id) {
            return response()->json([
                'message' => 'Anda tidak diperbolehkan mengubah role akun Anda sendiri.',
            ], 403);
        }

        $user->update($validated);

        $auditLogger->record(
            request: $request,
            activity: 'user_management',
            description: "User '{$user->email}' updated.",
            metadata: ['updated_user_id' => $user->id],
        );

        return response()->json($user->load('role:id,name'));
    }

    /**
     * Toggle active/inactive status of a user.
     * Restricted to Admins only.
     */
    public function toggleStatus(Request $request, User $user, AuditLogger $auditLogger): JsonResponse
    {
        // Prevent admin from changing their own status (avoid self-lockout)
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak diperbolehkan menonaktifkan akun Anda sendiri.',
            ], 403);
        }

        $newStatus = $user->status === UserStatus::ACTIVE->value
            ? UserStatus::INACTIVE->value
            : UserStatus::ACTIVE->value;

        $user->update([
            'status' => $newStatus,
        ]);

        $auditLogger->record(
            request: $request,
            activity: 'user_management',
            description: "User '{$user->email}' status toggled to {$newStatus}.",
            metadata: ['target_user_id' => $user->id, 'new_status' => $newStatus],
        );

        return response()->json($user->load('role:id,name'));
    }
}
