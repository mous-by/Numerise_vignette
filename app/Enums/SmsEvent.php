<?php

namespace App\Enums;

/**
 * Événement à l'origine d'un SMS (W14, cahier §5 Cas 2, §8, §9). Pas d'enum SQL : colonne string.
 */
enum SmsEvent: string
{
    case MotoRetrouvee = 'moto_retrouvee';
    case PaiementConfirme = 'paiement_confirme';
    case DemandeRecue = 'demande_recue';

    public function label(): string
    {
        return match ($this) {
            self::MotoRetrouvee => 'Moto retrouvée',
            self::PaiementConfirme => 'Paiement confirmé',
            self::DemandeRecue => 'Demande reçue',
        };
    }
}
