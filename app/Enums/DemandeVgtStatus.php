<?php

namespace App\Enums;

/**
 * Statut d'une demande de VGT (W11, W12, W13). Le cahier ne détaille pas ce workflow (point ouvert client) :
 * proposition technique du développeur — en_attente (déposée par le commissaire) → validee ou rejetee (mairie,
 * motif obligatoire ; une demande rejetee reste modifiable par le commissaire, qui peut la corriger et la
 * resoumettre, repasse à en_attente) → payee (mairie confirme la réception du paiement, cahier §4 « réception
 * des preuves de paiement ») → retiree (mairie confirme la remise de la carte physique, cahier §9 Retrait VGT).
 */
enum DemandeVgtStatus: string
{
    case EnAttente = 'en_attente';
    case Validee = 'validee';
    case Rejetee = 'rejetee';
    case Payee = 'payee';
    case Retiree = 'retiree';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Validee => 'Validée',
            self::Rejetee => 'Rejetée',
            self::Payee => 'Payée',
            self::Retiree => 'Retirée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::EnAttente => 'bg-light text-dark',
            self::Validee => 'bg-success-subtle text-success',
            self::Rejetee => 'bg-danger-subtle text-danger',
            self::Payee => 'bg-primary-subtle text-primary',
            self::Retiree => 'bg-secondary-subtle text-secondary',
        };
    }
}
