<?php

namespace App\Http\Middleware;

use App\Support\Rbac;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Roles may be provided as comma-separated list, e.g. "admin,gestor".
     * For "admin", also accepts Super Admin and equivalent roles (via DB or JWT).
     */
    public function handle(Request $request, Closure $next, ?string $roles = null)
    {
        $user = Auth::user() ?? $request->user();

        if (! $user && ! $request->attributes->get('jwt_payload')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (! $roles) {
            return $next($request);
        }

        $allowed = array_map('trim', explode(',', $roles));
        $roleName = Rbac::roleNameFromRequest($request, $user);

        $matches = false;
        foreach ($allowed as $allowedRole) {
            $key = strtolower($allowedRole);
            if ($key === 'admin' && Rbac::isAdmin($roleName)) {
                $matches = true;
                break;
            }
            if ($key === 'gestor' && Rbac::isGestor($roleName)) {
                $matches = true;
                break;
            }
            if ($roleName && strcasecmp($roleName, $allowedRole) === 0) {
                $matches = true;
                break;
            }
        }

        if (! $matches) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 403,
                'detail' => 'Required role: '.implode(' or ', $allowed).'; yours: '.($roleName ?? 'unknown'),
            ], 403);
        }

        return $next($request);
    }
}
