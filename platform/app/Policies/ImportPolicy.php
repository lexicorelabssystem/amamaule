<?php

namespace App\Policies;

use App\Models\Import;
use App\Models\User;

class ImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('imports.create');
    }

    public function view(User $user, Import $import): bool
    {
        return $user->id === $import->user_id || $user->can('imports.create');
    }

    public function create(User $user): bool
    {
        return $user->can('imports.create');
    }

    public function update(User $user, Import $import): bool
    {
        return $user->id === $import->user_id || $user->can('imports.create');
    }

    public function delete(User $user, Import $import): bool
    {
        return $user->id === $import->user_id || $user->can('imports.create');
    }

    public function process(User $user, Import $import): bool
    {
        return $user->id === $import->user_id || $user->can('imports.create');
    }
}
