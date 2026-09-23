<?php

namespace App\Policies;

use App\Models\Commissariat;
use App\Models\User;

/**
 * Modèle pour les policies à venir (CLAUDE.md §4.10) : chaque action reflète directement la permission du manifeste.
 * L'invariant « aucun utilisateur rattaché » pour la suppression n'est PAS ici : `Gate::before` (D16) contourne
 * l'autorisation pour le superadmin, donc une règle placée dans la policy ne s'appliquerait jamais à lui. Cette
 * règle est de la logique métier (jamais contournée, même par le superadmin) : elle vit dans le contrôleur.
 */
class CommissariatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('commissariats.view');
    }

    public function view(User $user, Commissariat $commissariat): bool
    {
        return $user->can('commissariats.view');
    }

    public function create(User $user): bool
    {
        return $user->can('commissariats.create');
    }

    public function update(User $user, Commissariat $commissariat): bool
    {
        return $user->can('commissariats.update');
    }

    public function delete(User $user, Commissariat $commissariat): bool
    {
        return $user->can('commissariats.delete');
    }
}
