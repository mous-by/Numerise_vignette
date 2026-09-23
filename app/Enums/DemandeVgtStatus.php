<?php

namespace App\Enums;

/**
 * Statut d'une demande de VGT (W11). Le cahier ne détaille pas ce workflow (point ouvert client) : proposition
 * technique du développeur — en_attente (déposée par le commissaire) → validee ou rejetee (mairie, motif
 * obligatoire) ; une demande rejetee reste modifiable par le commissaire, qui peut la corriger et la resoumettre
 * (repasse à en_attente). Le paiement (W12) et le retrait physique (W13) sont les étapes suivantes, hors
 * périmètre de ce statut.
 */
enum DemandeVgtStatus: string
{
    case EnAttente = 'en_attente';
    case Validee = 'validee';
    case Rejetee = 'rejetee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Validee => 'Validée',
            self::Rejetee => 'Rejetée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::EnAttente => 'bg-light text-dark',
            self::Validee => 'bg-success-subtle text-success',
            self::Rejetee => 'bg-danger-subtle text-danger',
        };
    }
}
