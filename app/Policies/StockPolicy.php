<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class StockPolicy
{
    /**
     * Determine if the given user can update stock for the given post.
     */
    public function update(User $user, Post $post): bool
    {
        return (int) $user->post_id === (int) $post->id;
    }
}
