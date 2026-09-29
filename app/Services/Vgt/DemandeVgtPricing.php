<?php

namespace App\Services\Vgt;

use App\Models\Moto;
use App\Models\TarifVgt;

/**
 * Tarif d'une demande de VGT (W11) : tarif de base selon le genre de la moto, majoration fixe si l'année demandée
 * est déjà passée. Partagé par l'écran Web (commissaire) et l'API publique de la population (D32).
 */
class DemandeVgtPricing
{
    /**
     * @return array{base_amount: int, is_late: bool, surcharge_amount: int}
     */
    public function for(Moto $moto, int $vgtYear): array
    {
        $isLate = $vgtYear < (int) date('Y');

        return [
            'base_amount' => TarifVgt::forGenre($moto->type_or_brand)->amount,
            'is_late' => $isLate,
            'surcharge_amount' => $isLate ? (int) config('vgt.late_surcharge_amount') : 0,
        ];
    }
}
