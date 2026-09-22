<?php

namespace App\Policies;

use App\Models\Information;
use App\Models\User;

/**
 * La liste est publique par nature (voir le manifeste) : seules la modification et la suppression restent
 * réservées à l'auteur, de son propre commissariat — pas un plafond de rôle (D9) mais une propriété d'auteur.
 */
class InformationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('informations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('informations.create');
    }

    public function update(User $user, Information $information): bool
    {
        return $user->can('informations.update') && $user->commissariat_id === $information->commissariat_id;
    }

    public function delete(User $user, Information $information): bool
    {
        return $user->can('informations.delete') && $user->commissariat_id === $information->commissariat_id;
    }
}
