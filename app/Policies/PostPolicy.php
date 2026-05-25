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

    public function manageOperational(User $user, Post $post): bool
    {
        return $this->manageStock($user, $post);
    }

    /** RN-G-001: apenas gestor do posto confirma preços decretados. */
    public function confirm(User $user, Post $post): bool
    {
        $roleName = Rbac::roleNameFromRequest(request(), $user);

        return Rbac::isGestor($roleName) && (int) $user->post_id === (int) $post->id;
    }

    /** RN-G-002: promoções locais no posto do gestor. */
    public function managePromotions(User $user, Post $post): bool
    {
        return $this->manageStock($user, $post);
    }

    /** RN-G-004: alertas preditivos de rutura. */
    public function viewStockAlerts(User $user, Post $post): bool
    {
        return $this->manageStock($user, $post);
    }

    /** RN-G-007: campanhas geolocalizadas. */
    public function manageCampaigns(User $user, Post $post): bool
    {
        return $this->manageStock($user, $post);
    }

    /** RN-G-008: sincronização offline. */
    public function offlineSync(User $user, Post $post): bool
    {
        return $this->manageStock($user, $post);
    }

    /** RN-G-006: reportar incidentes no próprio posto (gestor) ou admin consultar. */
    public function reportIncident(User $user, Post $post): bool
    {
        $roleName = Rbac::roleNameFromRequest(request(), $user);

        if (Rbac::isAdmin($roleName)) {
            return true;
        }

        return Rbac::isGestor($roleName) && (int) $user->post_id === (int) $post->id;
    }
}
