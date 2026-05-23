<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use App\Support\Rbac;

class PostPolicy
{
    public function create(User $user): bool
    {
        return Rbac::isAdmin(Rbac::roleNameFromRequest(request(), $user));
    }

    public function update(User $user, Post $post): bool
    {
        return Rbac::isAdmin(Rbac::roleNameFromRequest(request(), $user));
    }

    public function delete(User $user, Post $post): bool
    {
        return Rbac::isAdmin(Rbac::roleNameFromRequest(request(), $user));
    }

    /**
     * Admin (any post) ou gestor com user.post_id === post.id.
     */
    public function manageStock(User $user, Post $post): bool
    {
        $roleName = Rbac::roleNameFromRequest(request(), $user);

        if (Rbac::isAdmin($roleName)) {
            return true;
        }

        return Rbac::isGestor($roleName) && (int) $user->post_id === (int) $post->id;
    }
}
