<?php

namespace App\Policies;

use App\Models\Mairie;
use App\Models\User;

/**
 * Mirroir de CommissariatPolicy (W1, le modèle — CLAUDE.md §4.10) : chaque action reflète directement la
 * permission du manifeste. L'invariant « aucun utilisateur rattaché » pour la suppression vit dans le
 * contrôleur, pas ici (Gate::before contourne l'autorisation pour le superadmin, D16).
 */
class MairiePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('mairies.view');
    }

    public function view(User $user, Mairie $mairie): bool
    {
        return $user->can('mairies.view');
    }

    public function create(User $user): bool
    {
        return $user->can('mairies.create');
    }

    public function update(User $user, Mairie $mairie): bool
    {
        return $user->can('mairies.update');
    }

    public function delete(User $user, Mairie $mairie): bool
    {
        return $user->can('mairies.delete');
    }
}
