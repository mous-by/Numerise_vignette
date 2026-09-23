<?php

namespace App\Policies;

use App\Models\Proprietaire;
use App\Models\User;

/**
 * Chaque action reflète directement la permission du manifeste : le cloisonnement (un commissaire ne voit que
 * les propriétaires de son propre commissariat) est déjà assuré en amont par BelongsToCommissariat, qui filtre
 * fail-closed avant même que la Policy ne soit consultée.
 */
class ProprietairePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('proprietaires.view');
    }

    public function view(User $user, Proprietaire $proprietaire): bool
    {
        return $user->can('proprietaires.view');
    }

    public function create(User $user): bool
    {
        return $user->can('proprietaires.create');
    }

    public function update(User $user, Proprietaire $proprietaire): bool
    {
        return $user->can('proprietaires.update');
    }

    public function delete(User $user, Proprietaire $proprietaire): bool
    {
        return $user->can('proprietaires.delete');
    }
}
