<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PriceConfirmationPolicy
{
    /**
     * Determine if the given user can confirm prices for the given post.
     */
    public function confirm(User $user, Post $post): bool
    {
        // Only allow if the user belongs to the same post.
        return (int) $user->post_id === (int) $post->id;
    }
}
