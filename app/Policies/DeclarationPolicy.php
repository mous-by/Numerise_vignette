<?php

namespace App\Policies;

use App\Models\Declaration;
use App\Models\User;

/**
 * Chaque action reflète directement la permission du manifeste : le cloisonnement (un commissaire ne voit que
 * les déclarations de son propre commissariat) est déjà assuré en amont par BelongsToCommissariat, fail-closed.
 */
class DeclarationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('declarations.view');
    }

    public function view(User $user, Declaration $declaration): bool
    {
        return $user->can('declarations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('declarations.create');
    }

    public function update(User $user, Declaration $declaration): bool
    {
        return $user->can('declarations.update');
    }

    public function delete(User $user, Declaration $declaration): bool
    {
        return $user->can('declarations.delete');
    }
}
