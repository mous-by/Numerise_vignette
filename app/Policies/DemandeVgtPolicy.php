<?php

namespace App\Policies;

use App\Enums\DemandeVgtStatus;
use App\Models\DemandeVgt;
use App\Models\User;

/**
 * Le cloisonnement (visible du commissariat qui a déposé et de la mairie de retrait) est déjà assuré en amont
 * par DemandeVgt::scopeVisibleTo() ; la Policy ajoute les règles propres à chaque action.
 */
class DemandeVgtPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('demandes-vgt.view');
    }

    public function view(User $user, DemandeVgt $demandeVgt): bool
    {
        return $user->can('demandes-vgt.view');
    }

    public function create(User $user): bool
    {
        return $user->can('demandes-vgt.create');
    }

    /** Le commissaire ne modifie que sa propre demande, et seulement tant qu'elle est rejetée (à corriger). */
    public function update(User $user, DemandeVgt $demandeVgt): bool
    {
        return $user->can('demandes-vgt.update')
            && $demandeVgt->commissariat_id === $user->commissariat_id
            && $demandeVgt->status === DemandeVgtStatus::Rejetee;
    }

    /** La mairie valide ou rejette une demande encore en attente, adressée à sa propre mairie. */
    public function validateRequest(User $user, DemandeVgt $demandeVgt): bool
    {
        return $user->can('demandes-vgt.validate')
            && $demandeVgt->mairie_id === $user->mairie_id
            && $demandeVgt->status === DemandeVgtStatus::EnAttente;
    }

    public function manageTarifs(User $user): bool
    {
        return $user->can('demandes-vgt.manage_tarifs');
    }
}
