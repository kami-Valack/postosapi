<?php

namespace App\Support;

final class RoleIds
{
    public const SUPER_ADMIN_PREMIUM = 1;

    public const SUPER_ADMIN = 2;

    public const ADMIN = 3;

    public const GESTOR = 4;

    /**
     * @return list<int>
     */
    public static function adminIds(): array
    {
        return array_keys(array_filter(
            config('roles.definitions', []),
            fn (array $role): bool => ($role['type'] ?? '') === 'admin'
        ));
    }

    /**
     * @return list<int>
     */
    public static function gestorIds(): array
    {
        return array_keys(array_filter(
            config('roles.definitions', []),
            fn (array $role): bool => ($role['type'] ?? '') === 'gestor'
        ));
    }

    /**
     * @return list<int>
     */
    public static function allIds(): array
    {
        return array_map('intval', array_keys(config('roles.definitions', [])));
    }

    public static function name(int $roleId): ?string
    {
        return config("roles.definitions.{$roleId}.name");
    }

    public static function isAdminId(?int $roleId): bool
    {
        return $roleId !== null && in_array($roleId, self::adminIds(), true);
    }

    public static function isGestorId(?int $roleId): bool
    {
        return $roleId !== null && in_array($roleId, self::gestorIds(), true);
    }

    /**
     * @return array<int, array{name: string, type: string, description: string}>
     */
    public static function catalog(): array
    {
        $definitions = config('roles.definitions', []);
        $catalog = [];
        foreach ($definitions as $id => $role) {
            $catalog[(int) $id] = [
                'id' => (int) $id,
                'name' => $role['name'],
                'type' => $role['type'],
                'description' => $role['description'] ?? '',
            ];
        }

        return $catalog;
    }
}
