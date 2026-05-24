<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

class JwtUserSyncService
{
    /**
     * Cria ou atualiza utilizador local a partir do JWT emitido pelo serviço de auth externo.
     * O papel local é resolvido pelo **nome** do JWT, contra os papéis definidos em config/roles.php.
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
        $roleId = $this->resolveRoleIdFromName($data['role'] ?? null);

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

    /**
     * Mapeia o nome do papel no JWT para roles.id local (seed/config).
     * Não usa o role.id do auth externo.
     */
    private function resolveRoleIdFromName(mixed $roleClaim): ?int
    {
        if ($roleClaim === null) {
            return null;
        }

        $roleName = null;

        if (is_object($roleClaim)) {
            $roleName = $roleClaim->name ?? null;
        } elseif (is_array($roleClaim)) {
            $roleName = $roleClaim['name'] ?? null;
        } elseif (is_string($roleClaim)) {
            $roleName = $roleClaim;
        }

        if (! $roleName) {
            return null;
        }

        return Role::query()->where('name', $roleName)->value('id');
    }
}
