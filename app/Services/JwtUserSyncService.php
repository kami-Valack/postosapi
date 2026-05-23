<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

class JwtUserSyncService
{
    /**
     * Cria ou atualiza utilizador local a partir do JWT emitido pelo serviço de auth externo.
     * Preserva post_id definido nesta API (associação gestor ↔ posto).
     */
    public function syncFromPayload(object|array $payload): User
    {
        $data = is_object($payload) ? (array) $payload : $payload;

        $authUserId = $data['sub'] ?? null;
        if ($authUserId === null || $authUserId === '') {
            throw new \InvalidArgumentException('JWT missing sub claim');
        }

        $authUserId = (int) $authUserId;
        $email = (string) ($data['email'] ?? 'user'.$authUserId.'@auth.local');
        $name = (string) ($data['name'] ?? $data['email'] ?? 'User '.$authUserId);
        $roleId = $this->resolveRoleId($data['role'] ?? null);

        $user = User::query()->where('auth_user_id', $authUserId)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            $user = new User;
            $user->auth_user_id = $authUserId;
            $user->password = bcrypt(Str::password(32));
            $user->post_id = null;
        }

        $user->auth_user_id = $authUserId;
        $user->email = $email;
        $user->name = $name;

        if ($roleId !== null) {
            $user->role_id = $roleId;
        }

        $user->save();

        return $user->load(['role', 'post']);
    }

    private function resolveRoleId(mixed $roleClaim): ?int
    {
        if ($roleClaim === null) {
            return null;
        }

        $roleName = null;
        $externalRoleId = null;

        if (is_object($roleClaim)) {
            $roleName = $roleClaim->name ?? null;
            $externalRoleId = $roleClaim->id ?? null;
        } elseif (is_array($roleClaim)) {
            $roleName = $roleClaim['name'] ?? null;
            $externalRoleId = $roleClaim['id'] ?? null;
        } elseif (is_string($roleClaim)) {
            $roleName = $roleClaim;
        }

        if ($roleName) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName],
                ['permissions' => null]
            );

            return $role->id;
        }

        if ($externalRoleId !== null) {
            return Role::query()->find((int) $externalRoleId)?->id;
        }

        return null;
    }
}
