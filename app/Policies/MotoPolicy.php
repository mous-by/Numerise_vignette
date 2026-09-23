<?php

namespace App\Policies;

use App\Models\Moto;
use App\Models\User;

/**
 * Chaque action reflète directement la permission du manifeste : le cloisonnement (un commissaire ne voit que
 * les motos de son propre commissariat) est déjà assuré en amont par BelongsToCommissariat, fail-closed.
 */
class MotoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('motos.view');
    }

    public function view(User $user, Moto $moto): bool
    {
        return $user->can('motos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('motos.create');
    }

    public function update(User $user, Moto $moto): bool
    {
        return $user->can('motos.update');
    }

    public function delete(User $user, Moto $moto): bool
    {
        return $user->can('motos.delete');
    }
}
