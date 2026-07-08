<?php

namespace App\Policies;

use App\Models\Artist;
use App\Models\User;

class ArtistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('artists.view_any') || $user->can('artists.view_own');
    }

    public function view(User $user, Artist $artist): bool
    {
        if ($user->can('artists.view_any')) {
            return true;
        }

        return $user->can('artists.view_own') && $artist->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('artists.create');
    }

    public function update(User $user, Artist $artist): bool
    {
        if ($user->can('artists.edit_any')) {
            return true;
        }

        return $user->can('artists.edit_own') && $artist->user_id === $user->id;
    }

    public function delete(User $user, Artist $artist): bool
    {
        return $user->can('artists.delete') || $user->can('artists.edit_any');
    }

    public function submit(User $user, Artist $artist): bool
    {
        return ($user->can('artists.edit_own') && $artist->user_id === $user->id)
            || $user->can('artists.edit_any');
    }

    public function review(User $user, Artist $artist): bool
    {
        return $user->can('artists.review');
    }

    public function approve(User $user, Artist $artist): bool
    {
        return $user->can('artists.approve');
    }

    public function reject(User $user, Artist $artist): bool
    {
        return $user->can('artists.reject');
    }

    public function archive(User $user, Artist $artist): bool
    {
        return $user->can('artists.archive');
    }
}
