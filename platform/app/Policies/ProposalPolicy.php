<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('proposals.view_any') || $user->can('proposals.create_own');
    }

    public function view(User $user, Proposal $proposal): bool
    {
        if ($user->can('proposals.view_any')) {
            return true;
        }

        return $user->can('proposals.edit_own') && $proposal->artist->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('proposals.create_own');
    }

    public function update(User $user, Proposal $proposal): bool
    {
        return $user->can('proposals.edit_own')
            && $proposal->artist->user_id === $user->id
            && in_array($proposal->status, [Proposal::STATUS_DRAFT, Proposal::STATUS_NEEDS_CHANGES], true);
    }

    public function delete(User $user, Proposal $proposal): bool
    {
        return $user->can('proposals.edit_own')
            && $proposal->artist->user_id === $user->id
            && $proposal->isDraft();
    }

    public function submit(User $user, Proposal $proposal): bool
    {
        return ($user->can('proposals.edit_own') && $proposal->artist->user_id === $user->id)
            || $user->can('proposals.edit_any');
    }

    public function review(User $user, Proposal $proposal): bool
    {
        return $user->can('proposals.review');
    }

    public function approve(User $user, Proposal $proposal): bool
    {
        return $user->can('proposals.approve');
    }

    public function reject(User $user, Proposal $proposal): bool
    {
        return $user->can('proposals.reject');
    }

    public function requestChanges(User $user, Proposal $proposal): bool
    {
        return $user->can('proposals.review') || $user->can('proposals.approve');
    }
}
