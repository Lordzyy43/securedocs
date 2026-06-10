<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
  public function handle(Request $request, Closure $next, string $roles): Response
  {
    $allowedRoles = array_filter(array_map('trim', explode('|', $roles)));
    $currentRole = $request->user()?->role?->name;

    if (! $currentRole || ! in_array($currentRole, $allowedRoles, true)) {
      abort(403);
    }

    return $next($request);
  }
}
