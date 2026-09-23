<?php

/*
| Manifeste du module « motos » (W8, D6, D7). Saisies par le commissaire lors de l'enregistrement initial
| (cahier §5 Cas 1, §9 Accueil) : matricule, couleur, genre ou marque, année de la vignette, attestation de
| vente (vendeur, témoin). Cloisonné par commissariat (Moto::class utilise BelongsToCommissariat), comme les
| propriétaires (W7) : réservé au commissaire tant qu'aucun module (déclarations, contrôle) n'a besoin d'une
| lecture nationale.
|
| Identification de la moto : le cahier ne mentionne jamais de châssis, seulement le matricule (§5, §9, les
| quatre écrans qui identifient une moto — accueil, déclaration, demande VGT, moto retrouvée — n'utilisent que
| lui) : matricule unique au niveau national (pas seulement par commissariat), résolu directement à partir du
| cahier sans attendre le client.
*/

return [
    'label' => 'Motos',

    'permissions' => [
        'view' => 'Voir les motos',
        'create' => 'Créer une moto',
        'update' => 'Modifier une moto',
        'delete' => 'Supprimer une moto',
    ],

    'reserved' => [],

    'roles' => [
        'commissaire' => [
            'defaults' => ['view', 'create', 'update', 'delete'],
            'optional' => [],
        ],
    ],

    'navigation' => [
        ['label' => 'Motos', 'icon' => 'bx bx-cycling', 'route' => 'motos.index', 'permission' => 'motos.view', 'active' => 'motos.*', 'order' => 50],
    ],
];
