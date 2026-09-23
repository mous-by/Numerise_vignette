<?php

namespace App\Policies;

use App\Models\MotoRetrouvee;
use App\Models\User;

/**
 * Chaque action reflète directement la permission du manifeste. Pas de `delete` : le cahier ne prévoit que
 * lister et modifier pour ce module (contrairement à W7/W8/W9).
 */
class MotoRetrouveePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('motos-retrouvees.view');
    }

    public function view(User $user, MotoRetrouvee $motoRetrouvee): bool
    {
        return $user->can('motos-retrouvees.view');
    }

    public function create(User $user): bool
    {
        return $user->can('motos-retrouvees.create');
    }

    public function update(User $user, MotoRetrouvee $motoRetrouvee): bool
    {
        return $user->can('motos-retrouvees.update');
    }
}
