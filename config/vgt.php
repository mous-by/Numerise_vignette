<?php

/*
| Réglages VGT (W11). Le tarif par genre est configurable en base (table tarifs_vgt, gérée depuis l'écran
| Demandes VGT) — voir App\Models\TarifVgt. La majoration pour arriéré, elle, est un montant fixe unique
| (cahier : « une majoration fixe »), configurable ici plutôt qu'en base, faute de variation par genre demandée.
*/

return [
    // Majoration fixe (FCFA) appliquée quand l'année de VGT demandée est déjà passée au moment du dépôt
    // (PROPOSITION TECHNIQUE : la pratique malienne actuelle n'a pas de règle équivalente documentée).
    'late_surcharge_amount' => (int) env('VGT_LATE_SURCHARGE_AMOUNT', 2000),
];
