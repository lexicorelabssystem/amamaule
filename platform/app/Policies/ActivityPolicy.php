<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('activities.view_any') || $user->can('activities.create_own');
    }

    public function view(User $user, Activity $activity): bool
    {
        if ($user->can('activities.view_any')) {
            return true;
        }

        return $user->can('activities.edit_own') && $activity->artist->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('activities.create_own');
    }

    public function update(User $user, Activity $activity): bool
    {
        if ($user->can('activities.view_any')) {
            return true;
        }

        return $user->can('activities.edit_own') && $activity->artist->user_id === $user->id;
    }

    public function delete(User $user, Activity $activity): bool
    {
        if ($user->can('activities.view_any')) {
            return true;
        }

        return $user->can('activities.edit_own') && $activity->artist->user_id === $user->id;
    }

    public function publish(User $user, Activity $activity): bool
    {
        return $user->can('activities.publish');
    }

    public function archive(User $user, Activity $activity): bool
    {
        return $user->can('activities.archive') || $user->can('activities.publish');
    }
}
