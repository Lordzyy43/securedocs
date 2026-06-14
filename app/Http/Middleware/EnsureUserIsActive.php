<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isActive()) {
            return $next($request);
        }

        app(AuditLogger::class)->record(
            request: $request,
            activity: 'inactive_session_blocked',
            description: 'Inactive user attempted to access an authenticated route.',
            status: 'failure',
            user: $user,
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Your account is inactive.',
        ], JsonResponse::HTTP_FORBIDDEN);
    }
}
