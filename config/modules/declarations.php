<?php

/*
| Manifeste du module « declarations » (W9, D6, D7). Vol, braquage ou autre (cahier §5 Cas 2, §9 Déclaration) :
| lieu, date, circonstances, moto concernée. Une déclaration de vol ou de braquage marque la moto liée « Volée »
| (Moto::recalculateStolenStatus()). Cloisonné par commissariat (Declaration::class utilise BelongsToCommissariat),
| comme les propriétaires et les motos (W7, W8) : la lecture nationale du statut « Volée » d'une moto viendra
| avec le contrôle de police (W11), pas avec cet écran.
*/

return [
    'label' => 'Déclarations',

    'permissions' => [
        'view' => 'Voir les déclarations',
        'create' => 'Créer une déclaration',
        'update' => 'Modifier une déclaration',
        'delete' => 'Supprimer une déclaration',
    ],

    'reserved' => [],

    'roles' => [
        'commissaire' => [
            'defaults' => ['view', 'create', 'update', 'delete'],
            'optional' => [],
        ],
    ],

    'navigation' => [
        ['label' => 'Déclarations', 'icon' => 'bx bx-error-alt', 'route' => 'declarations.index', 'permission' => 'declarations.view', 'active' => 'declarations.*', 'order' => 60],
    ],
];
