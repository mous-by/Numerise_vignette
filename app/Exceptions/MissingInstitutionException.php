<?php

namespace App\Exceptions;

use DomainException;

/**
 * Un modèle cloisonné (BelongsToCommissariat / BelongsToMairie) se crée toujours au nom de l'institution de
 * l'acteur : un compte sans institution (superadmin, admin national) ne peut pas en être l'auteur, même s'il
 * contourne la permission (Gate::before, D16 — l'autorisation, pas la logique métier). Jamais atteinte en usage
 * normal : seuls les rôles avec institution ont ces permissions par défaut.
 */
class MissingInstitutionException extends DomainException
{
    public function __construct(string $model)
    {
        parent::__construct("Impossible de créer un(e) {$model} : l'auteur n'a aucune institution de rattachement.");
    }
}
