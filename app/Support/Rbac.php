<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class Rbac
{
    /** Papéis com permissão de administração (criar postos, associar utilizadores, etc.). */
    private const ADMIN_ALIASES = [
        'admin',
        'administrator',
        'superadmin',
        'super_admin',
    ];

    private const GESTOR_ALIASES = [
        'gestor',
        'manager',
    ];

    public static function roleNameFromRequest(Request $request, ?User $user = null): ?string
    {
        $user ??= $request->user();

        if ($user?->role?->name) {
            return $user->role->name;
        }

        $payload = $request->attributes->get('jwt_payload');

        if (is_object($payload)) {
            if (isset($payload->role->name)) {
                return (string) $payload->role->name;
            }
            if (isset($payload->role) && is_string($payload->role)) {
                return $payload->role;
            }
        }

        if (is_array($payload)) {
            if (isset($payload['role']['name'])) {
                return (string) $payload['role']['name'];
            }
            if (isset($payload['role']) && is_string($payload['role'])) {
                return $payload['role'];
            }
        }

        return null;
    }

    public static function normalizeRoleName(?string $roleName): ?string
    {
        if ($roleName === null || $roleName === '') {
            return null;
        }

        return strtolower(str_replace([' ', '-'], '_', trim($roleName)));
    }

    public static function isAdmin(?string $roleName): bool
    {
        $normalized = self::normalizeRoleName($roleName);

        return $normalized !== null && in_array($normalized, self::ADMIN_ALIASES, true);
    }

    public static function isGestor(?string $roleName): bool
    {
        $normalized = self::normalizeRoleName($roleName);

        return $normalized !== null && in_array($normalized, self::GESTOR_ALIASES, true);
    }

    public static function isAdminRequest(Request $request, ?User $user = null): bool
    {
        return self::isAdmin(self::roleNameFromRequest($request, $user));
    }

    public static function isGestorRequest(Request $request, ?User $user = null): bool
    {
        return self::isGestor(self::roleNameFromRequest($request, $user));
    }
}
